const testimonial = (() => {
    const IMAGES = [1, 2, 3, 4].map(n => `${BASE_URL ?? ''}/modules/common/img/testimonial-${n}.jpg`);

    function stars(rating) {
        const value = Math.max(0, Math.min(5, Number(rating) || 0));
        let html = "";

        for (let i = 1; i <= 5; i++) {
            html += `<i class="fa${i <= value ? "s" : "r"} fa-star text-warning"></i>`;
        }

        return html;
    }

    function slide({ name, profession, quote, image, rating }) {
        return `<div class="text-center bg-light p-4">
            <i class="fa fa-quote-left fa-3x mb-3 text-primary"></i>
            ${rating ? `<div class="mb-2">${stars(rating)}</div>` : ""}
            <p>${generalUtils.escapeHtml(quote || "")}</p>
            <img class="img-fluid mx-auto border p-1 mb-3" src="${image}"
                 alt="${generalUtils.escapeHtml(name || "Cliente")}" width="100" height="100">
            <h4 class="mb-1">${generalUtils.escapeHtml(name || "Cliente")}</h4>
            <span>${generalUtils.escapeHtml(profession || "Cliente")}</span>
        </div>`;
    }

    function fallbackSlides() {
        return (window.FALLBACK_TESTIMONIALS || []).map(item => ({ ...item }));
    }

    function initCarousel($container) {
        if ($container.hasClass('owl-loaded')) {
            $container.trigger('destroy.owl.carousel').removeClass('owl-loaded').find('.owl-stage-outer').children().unwrap();
        }

        $container.owlCarousel({
            autoplay: false,
            smartSpeed: 1000,
            margin: 25,
            loop: true,
            center: true,
            dots: false,
            nav: true,
            navText: [
                '<i class="bi bi-chevron-left"></i>',
                '<i class="bi bi-chevron-right"></i>'
            ],
            responsive: {
                0: { items: 1 },
                768: { items: 2 },
                992: { items: 3 }
            }
        });
    }

    function render(items, average) {
        const $container = $("#testimonialCarousel");

        const slides = items.map((item, index) => slide({
            name: item.customerName,
            profession: item.local === "carrinha_ambulante" ? "Carrinha Ambulante" : null,
            quote: item.comment || "Cliente satisfeito com o serviço.",
            rating: item.rating,
            image: IMAGES[index % IMAGES.length]
        }));

        $container.html(slides.join(""));

        if (average !== null && average !== undefined) {
            $("#testimonialAverage")
                .removeClass("d-none")
                .html(`Média de satisfação: <strong>${average} / 5</strong> · ${items.length} avaliação(ões)`);
        }

        initCarousel($container);
    }

    async function load() {
        const $container = $("#testimonialCarousel");
        const promise = API.feedback.list(6);
        const preloader = $container.preloader(".jq-skeleton-service-category-card", promise, { rows: 3 });

        try {
            const response = await promise;
            const items = response?.feedback || [];

            if (items.length === 0) {
                render(fallbackSlides().map(item => ({ customerName: item.name, comment: item.quote, rating: null })), null);
            } else {
                render(items, response?.average);
            }
        } catch (error) {
            render(fallbackSlides().map(item => ({ customerName: item.name, comment: item.quote, rating: null })), null);
        } finally {
            await preloader;
        }
    }

    $(() => {
        load();
    });

    return { load, render, stars };
})();
