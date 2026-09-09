const domUtils = (() => {
    function getElement(selectorOrElement) {
        return typeof selectorOrElement === 'string' 
            ? document.querySelector(selectorOrElement) 
            : (selectorOrElement[0] || selectorOrElement);
    };

    return {
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                console.error('Falha ao copiar para o clipboard:', err);
                return false;
            }
        },
        isElementInViewport(selectorOrElement) {
            const el = getElement(selectorOrElement);
            if (!el) return false;

            const rect = el.getBoundingClientRect();

            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },
        scrollToElement(selectorOrElement, offset=0) {
            const el = getElement(selectorOrElement);
            if (!el) return;

            const elementPosition = el.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    };
})();
