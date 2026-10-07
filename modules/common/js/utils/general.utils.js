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
        /** Valor formatado com IVA incluído (usa `vatUtils` — D-16 · RN-36). */
        formatCurrencyWithVat(netValue) {
            return generalUtils.formatCurrency(vatUtils.gross(netValue));
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
        },
        /**
         * Converte um valor tecnico (snake_case, enum cru ou camelCase) em texto
         * legivel. Ex.: "pendente_alocacao" -> "Pendente alocacao"; "sinalPago"
         * -> "Sinal pago". Nao inventa texto: so espaca e capitaliza.
         */
        humanize(value) {
            if (value === null || value === undefined) return '';

            const spaced = String(value)
                .replace(/[_-]+/g, ' ')
                .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
                .replace(/\s+/g, ' ')
                .trim();

            return spaced ? spaced.charAt(0).toUpperCase() + spaced.slice(1) : '';
        },
        /**
         * Desloca a janela ate ao elemento e realca-o temporariamente (fade-out).
         * Ponto de entrada partilhado dos avisos/lembretes que apontam para um card
         * (ex.: agendamento/rota) — convencao #rotaDinamica.
         */
        highlightAndScroll(selectorOrElement, options = {}) {
            const el = getElement(selectorOrElement);
            if (!el) return;

            const { offset = 0, duration = 2000, className = 'is-highlighted' } = options;

            generalUtils.scrollToElement(el, offset);

            el.classList.remove(className);
            void el.offsetWidth;
            el.classList.add(className);

            window.setTimeout(() => el.classList.remove(className), duration);
        },
        /** Referencia a lib do Swal (local: modules/common/lib/sweetalert). */
        dialog() {
            return (typeof window !== 'undefined' && (window.Swal || window.Sweetalert2)) || null;
        },
        /** Confirmacao (Swal). Devolve `true` se o utilizador confirmar. */
        async confirmDialog({ title, text = '', icon = 'question', confirmButtonText = 'Confirmar', cancelButtonText = 'Cancelar' } = {}) {
            const swal = generalUtils.dialog();
            if (!swal) return window.confirm(text || title || '');

            const result = await swal.fire({
                title, text, icon,
                showCancelButton: true,
                confirmButtonText, cancelButtonText,
                reverseButtons: true
            });
            return Boolean(result.isConfirmed);
        },
        /** Alerta (Swal). */
        alertDialog({ title, text = '', icon = 'info' } = {}) {
            const swal = generalUtils.dialog();
            if (!swal) { window.alert(text || title || ''); return; }
            swal.fire({ title, text, icon });
        },
        /** Alerta de erro — sugar sobre `alertDialog`. */
        errorDialog(message) {
            return generalUtils.alertDialog({ title: 'Ocorreu um erro', text: message, icon: 'error' });
        },
        /**  A modal de Bootstrap 5.0 não tem `getOrCreateInstance` (só existe a partir da 5.1);
             usar esta função evita o erro ao abrir bootstrap modals. */
        bsModalGetOrCreateInstance(element) {
            if (!element) return null;

            return bootstrap.Modal.getInstance(element) || new bootstrap.Modal(element);
        }
    };
})();
