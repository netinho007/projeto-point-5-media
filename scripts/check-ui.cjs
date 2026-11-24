const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.POINT_PLAYWRIGHT || 'playwright');

async function verifyThemeToggle(page, theme, errors) {
  const button = page.locator('[data-theme-toggle]').first();
  if (!await button.count() || !await button.isVisible()) return;
  const alternative = theme === 'light' ? 'dark' : 'light';
  await button.click();
  const switched = await page.evaluate(() => ({ theme: document.documentElement.dataset.theme, stored: localStorage.getItem('point-theme') }));
  if (switched.theme !== alternative || switched.stored !== alternative) errors.push('Theme toggle did not switch and persist');
  await button.click();
  if (await page.evaluate(() => document.documentElement.dataset.theme) !== theme) errors.push('Theme toggle did not restore');
}

(async () => {
  const root = path.resolve(__dirname, '..');
  const output = path.join(root, 'storage', 'app', 'qa');
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, channel: process.env.POINT_BROWSER_CHANNEL || 'msedge' });
  const results = [];
  const cases = [
    { name: 'vagas', url: '/' },
    { name: 'login', url: '/entrar' },
    { name: 'cadastro', url: '/cadastro' },
    { name: 'recuperacao', url: '/esqueci-a-senha' },
    { name: 'admin-login', url: '/admin/login' },
    { name: 'admin-throttled', url: '/admin/login', file: 'admin-throttled.html', countdown: true },
    { name: 'candidate-throttled', url: '/entrar', file: 'candidate-throttled.html', countdown: true },
    { name: 'admin-report', file: 'admin-report.html' },
    { name: 'candidate-portal', file: 'candidate-portal.html' },
  ];
  try {
  for (const viewport of [{ width: 1440, height: 950 }, { width: 390, height: 844 }]) {
    for (const theme of ['light', 'dark']) {
      const context = await browser.newContext({ viewport });
      await context.addInitScript((theme) => localStorage.setItem('point-theme', theme), theme);
      await context.route('**/admin/analyses/engine', (route) => route.fulfill({ contentType: 'application/json', body: JSON.stringify({ ready: true, model: 'qwen3:4b', message: 'Modelo local disponível' }) }));
      for (const item of cases) {
        const page = await context.newPage();
        const fixtureHtml = item.file ? fs.readFileSync(path.join(output, item.file), 'utf8') : null;
        if (item.countdown) {
          const retryAt = await page.evaluate((html) => Number(new DOMParser().parseFromString(html, 'text/html').querySelector('[data-login-retry-at]').dataset.loginRetryAt), fixtureHtml);
          await page.clock.install({ time: new Date(retryAt * 1000 - 60000) });
        }
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        const response = await page.goto('http://127.0.0.1:8000' + (item.url || '/'), { waitUntil: 'domcontentloaded' });
        if (fixtureHtml) await page.setContent(fixtureHtml, { waitUntil: 'load' });
        await page.evaluate(async () => { await document.fonts.ready; });
        await page.waitForTimeout(250);
        if (item.countdown) {
          const countdown = page.locator('[data-login-retry-at]');
          const before = Number((await countdown.textContent()).match(/\d+/)?.[0]);
          await page.clock.runFor(1000);
          const after = Number((await countdown.textContent()).match(/\d+/)?.[0]);
          if (!Number.isFinite(before) || !Number.isFinite(after) || after >= before || after <= 0) errors.push('Login countdown did not update');
          if (await page.locator('input[name="password"]').inputValue() !== '') errors.push('Password was retained in login form');
        }
        const metrics = await page.evaluate(() => ({
          width: innerWidth,
          pageWidth: document.documentElement.scrollWidth,
          theme: document.documentElement.dataset.theme,
          heading: document.querySelector('h1')?.textContent,
          font: getComputedStyle(document.body).fontFamily,
          background: getComputedStyle(document.body).backgroundColor,
        }));
        if (metrics.pageWidth > metrics.width + 2) errors.push(`Horizontal overflow: ${metrics.pageWidth} > ${metrics.width}`);
        if (metrics.theme !== theme) errors.push(`Wrong theme: ${metrics.theme}`);
        if (response.status() !== 200) errors.push(`HTTP ${response.status()}`);
        const suffix = `${item.name}-${viewport.width}-${theme}`;
        await page.screenshot({ path: path.join(output, suffix + '.png'), fullPage: true });
        if (item.countdown) {
          const countdown = page.locator('[data-login-retry-at]');
          await page.clock.setSystemTime(new Date(Number(await countdown.getAttribute('data-login-retry-at')) * 1000 + 1000));
          await page.clock.runFor(1000);
          if (!(await countdown.textContent()).includes('já pode tentar')) errors.push('Login countdown did not expire');
        }
        const toggle = page.locator('[data-nav-toggle]').first();
        if (viewport.width === 390 && ['vagas', 'admin-report', 'candidate-portal'].includes(item.name) && (!await toggle.count() || !await toggle.isVisible())) errors.push('Mobile menu button is not visible');
        if (viewport.width === 390 && await toggle.count() && await toggle.isVisible()) {
          await toggle.click();
          await page.waitForTimeout(300);
          if (await toggle.getAttribute('aria-expanded') !== 'true') errors.push('Menu did not open');
          await page.screenshot({ path: path.join(output, suffix + '-menu.png'), fullPage: false });
          if (item.name !== 'admin-report') await verifyThemeToggle(page, theme, errors);
          await page.keyboard.press('Escape');
          if (await toggle.getAttribute('aria-expanded') !== 'false') errors.push('Menu did not close');
        }
        await verifyThemeToggle(page, theme, errors);
        results.push({ case: suffix, ...metrics, errors });
        await page.close();
      }
      await context.close();
    }
  }
  } finally {
    await browser.close();
  }
  fs.writeFileSync(path.join(output, 'ui-results.json'), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 2));
  if (results.some((item) => item.errors.length)) process.exitCode = 1;
})().catch((error) => { console.error(error); process.exitCode = 1; });
