/**
 * Estados do agendamento e da rota — FONTE ÚNICA no cliente.
 *
 * Existia duplicação real: os mapas viviam no backoffice (`bo.utils.js`) e estavam
 * repetidos, iguais, na área do cliente (`main/js/components/appointments.js`) — as
 * classes CSS e as chaves eram as mesmas, e os rótulos já divergiam em 3 estados.
 *
 * O rótulo varia com o público (o gestor tem mais contexto do que o cliente), mas num
 * só sítio: `audience` = "admin" | "customer" (omissão "customer").
 */
const bookingStatusUtils = (() => {
    const BOOKING_LABELS = {
        pendente_aceitacao_funcionarios:   { admin: "Aguarda aceitação (funcionários)",    customer: "Aguarda aceitação" },
        pendente_validacao_logistica_loja: { admin: "Pendente validação logística (loja)", customer: "Pendente validação (loja)" },
        totalmente_aceite_funcionarios:    { admin: "Totalmente aceite por funcionários",  customer: "Totalmente aceite" },
        confirmado:                        { admin: "Confirmado",                          customer: "Confirmado" },
        recusado:                          { admin: "Recusado",                            customer: "Recusado" },
        cancelado:                         { admin: "Cancelado",                           customer: "Cancelado" },
        executado:                         { admin: "Executado",                           customer: "Executado" },
        concluido:                         { admin: "Concluído",                           customer: "Concluído" }
    };

    const BOOKING_CLASSES = {
        pendente_aceitacao_funcionarios:   "bg-secondary",
        pendente_validacao_logistica_loja: "bg-warning text-dark",
        totalmente_aceite_funcionarios:    "bg-info text-dark",
        confirmado:                        "bg-success",
        recusado:                          "bg-danger",
        cancelado:                         "bg-dark",
        executado:                         "bg-primary",
        concluido:                         "bg-primary"
    };

    const ROUTE_LABELS = {
        planeada:    "Planeada",
        aprovada:    "Aprovada",
        recusada:    "Recusada",
        em_execucao: "Em execução",
        concluida:   "Concluída"
    };

    const ROUTE_CLASSES = {
        planeada:    "bg-secondary",
        aprovada:    "bg-success",
        recusada:    "bg-danger",
        em_execucao: "bg-primary",
        concluida:   "bg-primary"
    };

    /** Achata {estado: {admin, customer}} em {estado: rótulo} — para <select>s de filtro. */
    function flatLabels(labels, audience) {
        return Object.fromEntries(
            Object.entries(labels).map(([status, value]) =>
                [status, typeof value === "string" ? value : (value[audience] ?? value.customer)]
            )
        );
    }

    return {
        booking: {
            label: (status, audience = "customer") => {
                const entry = BOOKING_LABELS[status];
                if (!entry) return status || "-";
                return entry[audience] ?? entry.customer;
            },
            className: (status) => BOOKING_CLASSES[status] || "bg-secondary",
            labels: (audience = "customer") => flatLabels(BOOKING_LABELS, audience)
        },
        route: {
            label: (status) => ROUTE_LABELS[status] || status || "-",
            className: (status) => ROUTE_CLASSES[status] || "bg-secondary",
            labels: ROUTE_LABELS
        }
    };
})();