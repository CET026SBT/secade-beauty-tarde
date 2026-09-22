const generalUtils = (() => {
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
        },
        removeAccents(str) {
            return String(str ?? '')
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        },
        camelCase(str) {
            return generalUtils.removeAccents(str)
                .toLowerCase()
                .replace(/[^a-zA-Z0-9]+(.)/g, (match, chr) => chr.toUpperCase());
        },
        pascalCase(str) {
            const camel = generalUtils.camelCase(str);
            return camel.charAt(0).toUpperCase() + camel.slice(1);
        },
        kebabCase(str) {
            return generalUtils.removeAccents(str)
                .trim()
                .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
                .toLowerCase()
                .replace(/[\s_]+/g, '-')
                .replace(/[^a-z0-9-]/g, '');
        },
        slugify(str) {
            return generalUtils.kebabCase(str);
        },
        formatCurrency(value) {
            const num = Number(value ?? 0);
            return `${num.toFixed(2).replace('.', ',')} €`;
        },
        formatDuration(minutes) {
            const total = Number(minutes ?? 0);
            const hours = Math.floor(total / 60);
            const mins = total % 60;

            if (hours <= 0) return `${mins} min`;
            if (mins === 0) return `${hours} h`;
            return `${hours} h ${mins} min`;
        },
        formatDateTime(value) {
            if (!value) return '';
            const date = new Date(String(value).replace(' ', 'T'));
            if (isNaN(date.getTime())) return value;

            return date.toLocaleString('pt-PT', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        }
    };
})();
