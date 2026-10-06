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
        /**
         * Salta para um elemento e destaca-o temporariamente (moldura dourada
         * de largura variável). Utilitário único do `#rotaDinamica` e de outros
         * «aponta para aqui» (§24.7). Sem dependências de CSS — usa estilos inline.
         */
        highlightAndScroll(selectorOrElement, { offset = 90, duration = 2400 } = {}) {
            const el = getElement(selectorOrElement);
            if (!el) return;

            generalUtils.scrollToElement(el, offset);

            const previous = {
                boxShadow: el.style.boxShadow,
                transition: el.style.transition,
                borderRadius: el.style.borderRadius
            };

            el.style.transition = 'box-shadow .35s ease';
            el.style.borderRadius = '6px';
            el.style.boxShadow = '0 0 0 3px var(--bs-primary, #c9aa55)';

            window.setTimeout(() => {
                el.style.boxShadow = previous.boxShadow;
                window.setTimeout(() => {
                    el.style.transition = previous.transition;
                    el.style.borderRadius = previous.borderRadius;
                }, 400);
            }, duration);
        },
        removeAccents(str) {
            return String(str ?? '')
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        },
        /**
         * Lê um valor técnico (enum/chave) e devolve-o legível: `pendente_alocacao`
         * -> `Pendente alocacao`. Fonte única da apresentação de estados (substitui
         * os `replace('_',' ')` espalhados pelo código).
         */
        humanize(value) {
            const text = String(value ?? '').replace(/_+/g, ' ').trim();
            return text.charAt(0).toUpperCase() + text.slice(1);
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
         * Diálogo de confirmação (Q-05). Usa o Sweetalert2 servido localmente
         * (`modules/common/lib/sweetalert`) e cai no `window.confirm` se a lib não
         * estiver carregada. Devolve `Promise<boolean>`.
         */
        confirmDialog(options = {}) {
            const config = typeof options === 'string' ? { text: options } : (options || {});
            const swal = window.Sweetalert2;

            if (!swal) {
                return Promise.resolve(window.confirm(config.text || config.title || ''));
            }

            return swal.fire({
                icon: config.icon || 'question',
                title: config.title || undefined,
                text: config.text || '',
                confirmButtonText: config.confirmButtonText || 'Confirmar',
                cancelButtonText: config.cancelButtonText || 'Cancelar',
                showCancelButton: true,
                confirmButtonColor: '#c9a227',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => Boolean(result.isConfirmed));
        },
        /**
         * Diálogo informativo/erro (Q-05). Mesma política do `confirmDialog`.
         * Devolve `Promise` resolvida quando o utilizador fecha.
         */
        alertDialog(options = {}) {
            const config = typeof options === 'string' ? { text: options } : (options || {});
            const swal = window.Sweetalert2;

            if (!swal) {
                window.alert(config.text || config.title || '');
                return Promise.resolve();
            }

            return swal.fire({
                icon: config.icon || 'info',
                title: config.title || undefined,
                text: config.text || '',
                confirmButtonText: config.confirmButtonText || 'OK',
                confirmButtonColor: '#c9a227'
            });
        },
        bsModalGetOrCreateInstance() {
            const element = $("#appointmentDetailsModal")[0];
            if (!element) return null;

            return bootstrap.Modal.getInstance(element) || new bootstrap.Modal(element);
        }
    };
})();
