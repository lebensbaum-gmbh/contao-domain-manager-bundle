(() => {
    const initialize = () => {
        document.querySelectorAll('.domain-manager-inline-action').forEach((form) => {
            if (form.dataset.dmActionFeedbackInitialized === '1') {
                return;
            }

            const button = form.querySelector('button[type="submit"]');
            if (!button) {
                return;
            }

            form.dataset.dmActionFeedbackInitialized = '1';

            form.addEventListener('submit', (event) => {
                if (form.dataset.dmActionRunning === '1') {
                    event.preventDefault();
                    return;
                }

                form.dataset.dmActionRunning = '1';
                form.setAttribute('aria-busy', 'true');
                button.setAttribute('aria-disabled', 'true');
                button.style.pointerEvents = 'none';

                const label = button.textContent.trim();
                button.textContent = label.endsWith('…') ? label : `${label} …`;
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
