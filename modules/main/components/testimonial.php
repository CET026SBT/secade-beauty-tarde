<?php
register_script("components/testimonial", "main");

// Testemunhos estáticos — usados quando ainda não existe feedback real na BD
$fallbackTestimonials = [
    ["name" => "Carla Castanho", "profession" => "Professora",
     "quote" => "Excelente serviço e atendimento muito simpático. Recomendo vivamente!",
     "image" => BASE_URL . "/modules/common/img/testimonial-1.jpg"],
    ["name" => "Ana Bento", "profession" => "Vendedora",
     "quote" => "Ambiente espetacular e profissionais de enorme qualidade. Saio sempre renovada!",
     "image" => BASE_URL . "/modules/common/img/testimonial-2.jpg"],
    ["name" => "Joana Piçarra", "profession" => "Assistente call center",
     "quote" => "Atendimento fantástico e resultados sempre acima das expetativas.",
     "image" => BASE_URL . "/modules/common/img/testimonial-3.jpg"],
    ["name" => "Amélia Torres", "profession" => "Médica",
     "quote" => "Profissionalismo e dedicação de excelência em cada detalhe.",
     "image" => BASE_URL . "/modules/common/img/testimonial-4.jpg"]
];
?>

<script>
    window.FALLBACK_TESTIMONIALS = <?= json_encode($fallbackTestimonials, JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.2s">
            <h1 class="font-dancing-script text-primary">Testemunhos</h1>
            <h1 class="mb-5">O que dizem os clientes!</h1>
            <p class="text-muted small mb-4 d-none" id="testimonialAverage"></p>
        </div>
        <div class="owl-carousel testimonial-carousel wow fadeIn" data-wow-delay="0.3s"
             id="testimonialCarousel" preloader-defer></div>
    </div>
</div>

