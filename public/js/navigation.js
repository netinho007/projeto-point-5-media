(() => {
    if (window.lucide) window.lucide.createIcons();
    document.documentElement.classList.add('navigation-ready');
    const toggles = document.querySelectorAll('[data-nav-toggle]');
    const backdrop = document.createElement('button');
    backdrop.className = 'nav-backdrop';
    backdrop.hidden = true;
    backdrop.type = 'button';
    backdrop.setAttribute('aria-label', 'Fechar menu');
    document.body.append(backdrop);
    let opened = null;
    const close = (restoreFocus = false) => {
        if (!opened) return;
        document.getElementById(opened.getAttribute('aria-controls'))?.classList.remove('is-open');
        opened.setAttribute('aria-expanded', 'false');
        backdrop.hidden = true;
        if (restoreFocus) opened.focus();
        opened = null;
    };
    toggles.forEach((button) => button.addEventListener('click', () => {
        if (opened === button) { close(); return; }
        close();
        const menu = document.getElementById(button.getAttribute('aria-controls'));
        if (!menu) return;
        menu.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        opened = button;
        backdrop.hidden = !menu.classList.contains('admin-sidebar');
        if (!backdrop.hidden) menu.querySelector('a')?.focus();
    }));
    backdrop.addEventListener('click', () => close(true));
    document.querySelectorAll('[data-nav-close]').forEach((button) => button.addEventListener('click', () => close(true)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close(true);
        if (event.key !== 'Tab' || backdrop.hidden || !opened) return;
        const menu = document.getElementById(opened.getAttribute('aria-controls'));
        const focusable = [...menu.querySelectorAll('a, button')].filter((item) => item.offsetParent !== null);
        const first = focusable[0], last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
    window.addEventListener('resize', () => close());
})();
