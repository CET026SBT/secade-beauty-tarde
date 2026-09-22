const appointments = (() => {
    const state = {
        bookings: [],
        status: "",
        ratings: {},
        feedbackByBooking: {}
    };

    const STATUS_LABELS = {
        pendente_aceitacao_funcionarios: "Aguarda aceitação",
        pendente_validacao_logistica_loja: "Pendente validação (loja)",
        totalmente_aceite_funcionarios: "Totalmente aceite",
        confirmado: "Confirmado",
        recusado: "Recusado",
        cancelado: "Cancelado",
        executado: "Executado",
        concluido: "Concluído"
    };

    const STATUS_CLASSES = {
        pendente_aceitacao_funcionarios: "bg-secondary",
        pendente_validacao_logistica_loja: "bg-warning text-dark",
        totalmente_aceite_funcionarios: "bg-info text-dark",
        confirmado: "bg-success",
        recusado: "bg-danger",
        cancelado: "bg-dark",
        executado: "bg-primary",
        concluido: "bg-primary"
    };

    function filteredBookings() {
        if (!state.status) return state.bookings;
        return state.bookings.filter(booking => booking.status === state.status);
    }

    function serviceList(booking) {
        const services = booking.services || [];
        if (services.length === 0) return '<span class="text-muted">Sem serviços registados</span>';

        return services.map(service => {
            const person = service.personName ? ` <span class="badge bg-light text-dark">${generalUtils.escapeHtml(service.personName)}</span>` : "";
            return `<li>${generalUtils.escapeHtml(service.serviceName || "")}${person}
                        <span class="text-muted">· ${generalUtils.formatCurrency(service.price)}</span></li>`;
        }).join("");
    }

    function bookingCard(booking) {
        const label = STATUS_LABELS[booking.status] || booking.status;
        const className = STATUS_CLASSES[booking.status] || "bg-secondary";
        const isAmbulatory = booking.local === "carrinha_ambulante";
        const canReview = ["executado", "concluido"].includes(booking.status);

        return `<div class="border rounded p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="fw-bold">#${booking.id}</span>
                    <span class="badge ${className} ms-2">${generalUtils.escapeHtml(label)}</span>
                    <span class="badge bg-light text-dark ms-1">
                        <i class="bi ${isAmbulatory ? "bi-truck" : "bi-buildings"} me-1"></i>
                        ${isAmbulatory ? "Carrinha" : "Loja"}
                    </span>
                </div>
                <span class="fw-bold">${generalUtils.formatCurrency(booking.totalAmount)}</span>
            </div>
            <p class="text-muted small mb-2">
                <i class="bi bi-calendar-event me-1"></i>${generalUtils.formatDateTime(booking.dateTime)}
            </p>
            <ul class="list-unstyled small mb-0">${serviceList(booking)}</ul>
            ${canReview ? feedbackBlock(booking) : ""}
        </div>`;
    }

    function feedbackBlock(booking) {
        const feedback = state.feedbackByBooking[booking.id];

        if (feedback && feedback.rating) {
            return `<div class="alert alert-success small mt-3 mb-0">
                <div class="mb-1">${boStars(feedback.rating)} <strong>Avaliado</strong></div>
                ${feedback.comment ? `<span>${generalUtils.escapeHtml(feedback.comment)}</span>` : ""}
            </div>`;
        }

        return `<div class="border-top mt-3 pt-3" data-feedback-form="${booking.id}">
            <h6 class="small fw-bold text-uppercase mb-2">Avaliar este serviço</h6>
            <div class="d-flex align-items-center gap-1 mb-2 rating-picker" data-booking="${booking.id}">
                ${[1, 2, 3, 4, 5].map(value => `<button type="button" class="btn btn-link p-0 text-warning"
                    data-rate="${value}" title="${value} estrela(s)"><i class="far fa-star fs-5"></i></button>`).join("")}
            </div>
            <div class="mb-2">
                <textarea class="form-control form-control-sm" rows="2" maxlength="500"
                          placeholder="Deixe o seu comentário (opcional)" data-comment="${booking.id}"></textarea>
            </div>
            <button type="button" class="btn btn-sm btn-primary" data-submit-feedback="${booking.id}">
                <i class="bi bi-send me-1"></i> Enviar avaliação
            </button>
            <div class="invalid-feedback d-block text-danger small" data-feedback-error="${booking.id}"></div>
        </div>`;
    }

    function boStars(rating) {
        const value = Math.max(0, Math.min(5, Number(rating) || 0));
        let html = "";

        for (let i = 1; i <= 5; i++) {
            html += `<i class="fa${i <= value ? "s" : "r"} fa-star text-warning"></i>`;
        }

        return html;
    }

    function render() {
        const $list = $("#appointmentsList");
        const bookings = filteredBookings();

        $list.empty();

        if (bookings.length === 0) {
            $list.html(`<div class="text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted d-block mb-3"></i>
                <p class="text-muted mb-3">Ainda não existem agendamentos para este filtro.</p>
                <a href="${BASE_URL ?? ''}/agendar" class="btn btn-primary">Fazer uma marcação</a>
            </div>`);
            return;
        }

        for (const booking of bookings) {
            $list.append(bookingCard(booking));
        }
    }

    async function load() {
        const promise = API.booking.myBookings();
        const preloader = $("#appointmentsPage").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            state.bookings = response?.bookings || [];

            await loadFeedbackState();
            render();
        } catch (error) {
            $("#appointmentsError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar os agendamentos.");
        } finally {
            await preloader;
        }
    }

    async function loadFeedbackState() {
        try {
            const response = await API.feedback.myState();

            state.feedbackByBooking = {};

            for (const item of response?.items || []) {
                if (item.feedback) state.feedbackByBooking[item.bookingId] = item.feedback;
            }
        } catch (error) {
            state.feedbackByBooking = {};
        }
    }

    function syncRatingIcons(bookingId) {
        const rating = state.ratings[bookingId] || 0;

        $(`.rating-picker[data-booking="${bookingId}"] [data-rate]`).each(function () {
            const value = Number($(this).data("rate"));
            $(this).find("i").attr("class", `${value <= rating ? "fas" : "far"} fa-star fs-5`);
        });
    }

    async function submitFeedback(bookingId) {
        const rating = state.ratings[bookingId] || 0;
        const $error = $(`[data-feedback-error="${bookingId}"]`);

        $error.text("");

        if (rating < 1) {
            $error.text("Escolha uma classificação de 1 a 5 estrelas.");
            return;
        }

        const promise = API.feedback.create({
            bookingId: Number(bookingId),
            rating,
            comment: $(`[data-comment="${bookingId}"]`).val()
        });

        const preloader = $(`[data-feedback-form="${bookingId}"]`).preloader(".jq-overlay-process", promise);

        try {
            await promise;

            await loadFeedbackState();
            render();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            $error.text(errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível enviar a avaliação."));
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $(document).on("click", ".appt-filter", function () {
            state.status = $(this).data("status") || "";

            $(".appt-filter").removeClass("active");
            $(this).addClass("active");

            render();
        });

        $(document).on("click", "[data-rate]", function () {
            const bookingId = Number($(this).closest(".rating-picker").data("booking"));
            state.ratings[bookingId] = Number($(this).data("rate"));

            syncRatingIcons(bookingId);
            $(`[data-feedback-error="${bookingId}"]`).text("");
        });

        $(document).on("click", "[data-submit-feedback]", function () {
            submitFeedback($(this).data("submit-feedback"));
        });
    }

    $(() => {
        bindEvents();
        load();
    });

    return { state, load, render, submitFeedback };
})();