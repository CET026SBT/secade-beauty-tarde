/**
 * Utilitário de IVA (D-16 · RN-36).
 *
 * Regra do projeto: o catálogo guarda o preço BASE TRIBUTÁVEL (sem IVA) em `servico.preco_base`;
 * tudo o que é apresentado ao cliente passa por aqui. É a única conversão do lado do cliente —
 * nenhum componente deve multiplicar por 1,23 nem formatar IVA à mão.
 *
 * A taxa vem de `window.SITE_CONFIG.ivaRate` (definida em `app/config/config.php` → `IVA_RATE`),
 * com o valor de 23 % como salvaguarda.
 */
const vatUtils = (() => {
    const DEFAULT_RATE = 0.23;

    function rate() {
        const configured = Number(window.SITE_CONFIG?.ivaRate);
        return (configured > 0 && configured < 1) ? configured : DEFAULT_RATE;
    }

    function round(value) {
        return Math.round(Number(value ?? 0) * 100) / 100;
    }

    function percentLabel() {
        return `${Math.round(rate() * 1000) / 10}%`;
    }

    /** Acréscimo de IVA sobre um valor base (sem IVA). */
    function vatAmount(netValue) {
        return round(Number(netValue ?? 0) * rate());
    }

    /** Valor com IVA a partir do valor base (sem IVA) — é o que se mostra ao cliente. */
    function gross(netValue) {
        const net = Number(netValue ?? 0);
        return round(net + vatAmount(net));
    }

    /** Base tributável a partir de um valor com IVA (para conferência do que está gravado). */
    function net(grossValue) {
        return round(Number(grossValue ?? 0) / (1 + rate()));
    }

    return {
        rate,
        percentLabel,
        vatAmount,
        gross,
        net
    };
})();