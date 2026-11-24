(function () {
    const storageKey = 'point-theme';
    const root = document.documentElement;

    function currentTheme() {
        return root.dataset.theme === 'dark' ? 'dark' : 'light';
    }

    function setTheme(theme) {
        root.dataset.theme = theme;
        root.style.colorScheme = theme;
        localStorage.setItem(storageKey, theme);

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const nextTheme = theme === 'dark' ? 'light' : 'dark';
            const label = button.querySelector('[data-theme-label]');

            button.setAttribute('aria-label', `Alternar para modo ${nextTheme === 'dark' ? 'escuro' : 'claro'}`);
            button.setAttribute('title', `Alternar para modo ${nextTheme === 'dark' ? 'escuro' : 'claro'}`);

            if (label) {
                label.textContent = theme === 'dark' ? 'Escuro' : 'Claro';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTheme(currentTheme());

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
            });
        });
    });
})();
