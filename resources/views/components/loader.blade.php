<div id="site-loader" class="site-loader" role="status" aria-live="polite" aria-label="Carregando página">
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
    const hide = () => setTimeout(() => loader.classList.add('is-hidden'), 180);
    window.addEventListener('load', hide, { once: true });
    window.addEventListener('pageshow', hide);
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
        const href = link.getAttribute('href') || '';
        if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        if (link.origin && link.origin !== window.location.origin) return;
        loader.classList.remove('is-hidden');
    });
    document.addEventListener('submit', () => loader.classList.remove('is-hidden'));
})();
</script>
