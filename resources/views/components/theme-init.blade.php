<script>
    (function () {
        try {
            const storedTheme = localStorage.getItem('point-theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = storedTheme || systemTheme;

            document.documentElement.dataset.theme = theme;
            document.documentElement.style.colorScheme = theme;
        } catch (error) {
            document.documentElement.dataset.theme = 'light';
        }
    })();
</script>
