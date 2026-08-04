<?php
register_script('components/testimonial');

$testimonials = [
    [
        'name' => 'Carla Castanho',
        'profession' => 'Professora',
        'quote' => 'Excelente serviço e atendimento muito simpático. Recomendo vivamente!',
        'image' => 'img/testimonial-1.jpg'
    ],
    [
        'name' => 'Ana Bento',
        'profession' => 'Vendedora',
        'quote' => 'Ambiente espetacular e profissionais de enorme qualidade. Saio sempre renovada!',
        'image' => 'img/testimonial-2.jpg'
    ],
    [
        'name' => 'Joana Piçarra',
        'profession' => 'Assistente call center',
        'quote' => 'Atendimento fantástico e resultados sempre acima das expetativas.',
        'image' => 'img/testimonial-3.jpg'
    ],
    [
        'name' => 'Amélia Torres',
        'profession' => 'Médica',
        'quote' => 'Profissionalismo e dedicação de excelência em cada detalhe.',
        'image' => 'img/testimonial-4.jpg'
    ]
];
?>

<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.2s">
            <h1 class="font-dancing-script text-primary">Testemunhos</h1>
            <h1 class="mb-5">O que dizem os clientes!</h1>
        </div>
        <div class="owl-carousel testimonial-carousel wow fadeIn" data-wow-delay="0.3s">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="text-center bg-light p-4">
                    <i class="fa fa-quote-left fa-3x mb-3 text-primary"></i>
                    <p><?php echo htmlspecialchars($testimonial['quote']); ?></p>
                    <img class="img-fluid mx-auto border p-1 mb-3" src="<?php echo htmlspecialchars($testimonial['image']); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>">
                    <h4 class="mb-1"><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                    <span><?php echo htmlspecialchars($testimonial['profession']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

