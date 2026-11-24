<div id="site-loader" class="site-loader is-hidden" role="status" aria-live="polite" aria-label="Carregando página">
    <div class="site-loader-content">
        <div class="loader-symbol" aria-hidden="true"><span></span><b></b></div>
        <strong>Carregando</strong>
        <div class="loader-progress" aria-hidden="true"><i></i></div>
    </div>
</div>
<script>
(() => {
    const loader = document.getElementById('site-loader');
    if (!loader) return;
    let timer;
    const hide = () => { clearTimeout(timer); loader.classList.add('is-hidden'); };
    const show = () => { timer = setTimeout(() => loader.classList.remove('is-hidden'), 350); setTimeout(hide, 12000); };
    document.addEventListener('DOMContentLoaded', hide, { once: true });
    window.addEventListener('pageshow', hide);
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
        const href = link.getAttribute('href') || '';
        if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        if (link.origin && link.origin !== window.location.origin) return;
        if (/curriculo|resume|arquivo|anexo|attachment|baixar|download/i.test(link.pathname)) return;
        if (link.pathname === location.pathname && link.search === location.search && link.hash) return;
        show();
    });
    document.addEventListener('submit', (event) => { queueMicrotask(() => { if (!event.defaultPrevented) show(); }); });
})();
</script>
