/**
 * Indicadores públicos do site — fonte única no cliente.
 *
 * Ordem de leitura: valor **contado na BD** (`?action=site-stats`) e, quando ainda não há
 * dados, o valor **documental** de `window.SITE_CONFIG.statsFallback` (definido em
 * `app/config/config.php`). Nenhum componente deve inventar números nem chamar a API
 * directamente para este efeito.
 */
const siteStats = (() => {
    const DEFAULT_FALLBACK = { services: 35, categories: 3, cities: 10, team: 6, customers: 0, reviews: 0, minPrice: 5.01 };

    /**
     * Chaves cujo valor é **dinheiro**: guardam-se em bruto (sem IVA) ou já com IVA, mas mostram-se
     * sempre formatadas (`generalUtils.formatCurrency`).
     */
    const CURRENCY_KEYS = ["minPrice"];

    /** Chaves calculadas a partir de outra chave contada, em vez de contadas directamente. */
    const DERIVED = {
        // Preço mais baixo: a BD guarda o valor base (sem IVA) → apresenta-se com IVA (D-16 · RN-36).
        minPrice: counted => {
            const net = Number(counted?.minPriceNet ?? 0);
            return net > 0 ? vatUtils.gross(net) : null;
        }
    };

    let pending = null;

    function fallback() {
        return { ...DEFAULT_FALLBACK, ...(window.SITE_CONFIG?.statsFallback || {}) };
    }

    /** Chaves em que a contagem da BD ainda não é completa (config do servidor). */
    function documental() {
        return window.SITE_CONFIG?.statsDocumental || [];
    }

    /**
     * Junta o contado com o documental: só recorre ao fallback quando a contagem é 0 ou
     * quando a chave está marcada como documental (BD ainda incompleta nessa chave).
     */
    function merge(counted) {
        const base = fallback();
        const documentalKeys = documental();

        const stats = Object.keys(base).reduce((stats, key) => {
            const value = Number(counted?.[key] ?? 0);
            const contar = value > 0 && !documentalKeys.includes(key);
            stats[key] = contar ? value : base[key];
            return stats;
        }, {});

        // Valores derivados (ex.: preço mais baixo com IVA) sobrepõem-se quando há contagem.
        Object.keys(DERIVED).forEach(key => {
            const derivado = DERIVED[key](counted);
            if (derivado !== null && !documentalKeys.includes(key)) {
                stats[key] = derivado;
            }
        });

        return stats;
    }

    /** Carrega uma única vez por página e devolve sempre um objecto completo. */
    function load() {
        if (!pending) {
            pending = API.stats.summary()
                .then(response => merge(response?.stats))
                .catch(() => fallback());
        }

        return pending;
    }

    /**
     * Escreve os valores em todos os elementos `[data-site-stat="<chave>"]` da página.
     * O HTML traz o valor documental, pelo que sem JS os números mantêm-se corretos —
     * esta rotina só os substitui pelo valor real contado na BD.
     */
    async function apply($root = $(document)) {
        const stats = await load();

        $root.find('[data-site-stat]').each(function () {
            const key = $(this).data('siteStat');
            const value = stats?.[key];
            if (value === undefined || value === null) return;

            $(this).text(CURRENCY_KEYS.includes(key) ? generalUtils.formatCurrency(value) : value);
        });

        return stats;
    }

    // Preenchimento automático dos indicadores presentes na página.
    $(() => {
        apply();
    });

    return { load, apply, merge, fallback, documental };
})();