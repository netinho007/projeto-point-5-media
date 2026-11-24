(() => {
    document.querySelectorAll('[data-ai-health]').forEach(async (element) => {
        try {
            const response = await fetch(element.dataset.aiHealth, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('status');
            const state = await response.json();
            element.textContent = `${state.message} (${state.model})`;
            element.classList.toggle('engine-unavailable', !state.ready);
        } catch {
            element.textContent = 'Não foi possível consultar o motor local.';
        }
    });
    document.querySelectorAll('[data-analysis-status]').forEach((element) => {
        let errors = 0;
        const poll = async () => {
            if (document.hidden) { setTimeout(poll, 5000); return; }
            try {
                const response = await fetch(element.dataset.analysisStatus, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('status');
                const state = await response.json();
                if (['completed', 'failed'].includes(state.status)) { location.reload(); return; }
                const heading = element.querySelector('[role="status"] strong');
                if (heading && state.status === 'processing') heading.textContent = 'Analisando currículo';
                errors = 0;
            } catch { errors += 1; }
            setTimeout(poll, Math.min(30000, 5000 * (errors + 1)));
        };
        setTimeout(poll, 5000);
    });
})();
