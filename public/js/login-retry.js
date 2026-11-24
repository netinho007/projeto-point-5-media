(function () {
    document.querySelectorAll('[data-login-retry-at]').forEach((status) => {
        const deadline = Number(status.dataset.loginRetryAt) * 1000;
        if (!Number.isFinite(deadline)) return;
        let timer;
        function update() {
            const seconds = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
            status.textContent = seconds > 0
                ? `Tente novamente em ${seconds} ${seconds === 1 ? 'segundo' : 'segundos'}.`
                : 'Você já pode tentar entrar novamente.';
            if (seconds === 0) clearInterval(timer);
        }
        timer = setInterval(update, 1000);
        update();
    });
})();
