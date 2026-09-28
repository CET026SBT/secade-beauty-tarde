<?php
// Apresentação introdutória do que a plataforma oferece (home e /sobre, logo abaixo do "Sobre nós").
// Números: preenchidos por `siteStats.utils.js` (contados na BD, com o valor documental como
// salvaguarda — fonte única: `SITE_STATS_FALLBACK` em `app/config/config.php`).
// As imagens são ESPAÇOS RESERVADOS: substituir os ficheiros abaixo por fotografias reais
// mantendo o nome (ou ajustar aqui o caminho).
$statsFallback = SITE_STATS_FALLBACK;

$overviewCards = [
    [
        "image"   => "overview-catalogo.svg",
        "title"   => "Catálogo por áreas",
        "text"    => "Cabeleireiro, barbearia e estética. Filtre por preço, duração ou pesquisa e veja quanto tempo demora antes de marcar.",
        "highlights" => [
            ["stat" => "services",   "label" => "serviços"],
            ["stat" => "categories", "label" => "áreas"]
        ]
    ],
    [
        "image"   => "overview-loja.svg",
        "title"   => "Marcação na loja",
        "text"    => "Escolha serviços, data e hora e confirme em segundos. Terça a sábado, das 09:00 às 19:00, em marcações de 30 minutos.",
        "highlights" => [
            ["text" => "30 min", "label" => "por marcação"],
            ["text" => "10 %",   "label" => "sinal (simulado)"]
        ]
    ],
    [
        "image"   => "overview-carrinha.svg",
        "title"   => "Carrinha ao domicílio",
        "text"    => "Vamos até à sua morada com os serviços organizados por pessoa, com duração e valor calculados para cada uma.",
        "highlights" => [
            ["stat" => "cities", "label" => "cidades"]
        ]
    ],
    [
        "image"   => "overview-acompanhamento.svg",
        "title"   => "Acompanhamento do pedido",
        "text"    => "Cada serviço aceite pela equipa fica registado: consulte o estado em \"Meus Agendamentos\" e avalie no fim.",
        "highlights" => [
            ["text" => "24/7", "label" => "consulta online"]
        ]
    ]
];
?>

<div class="container-fluid service py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.1s">
            <h1 class="font-dancing-script text-primary">A nossa plataforma</h1>
            <h1 class="mb-3">Tudo o que o Secade Beauty oferece</h1>
            <p class="text-muted mx-auto mb-5" style="max-width: 720px;">
                Do primeiro clique ao resultado final, tudo se resolve aqui: escolher o serviço, marcar a hora,
                ir ao salão ou esperar pela carrinha e acompanhar cada passo da marcação.
            </p>
        </div>
        <div class="row g-4">
            <?php foreach ($overviewCards as $index => $card): ?>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="<?= 0.2 + $index * 0.1 ?>s">
                    <div class="service-item h-100 bg-light border-bottom border-end">
                        <div class="ratio ratio-4x3">
                            <img class="img-fluid" style="object-fit: cover;"
                                 src="<?= BASE_URL ?>/modules/common/img/<?= htmlspecialchars($card["image"]) ?>"
                                 alt="<?= htmlspecialchars($card["title"]) ?>">
                        </div>
                        <div class="p-4">
                            <h4 class="mb-3"><?= htmlspecialchars($card["title"]) ?></h4>
                            <p class="mb-3"><?= htmlspecialchars($card["text"]) ?></p>
                            <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0">
                                <?php foreach ($card["highlights"] as $highlight): ?>
                                    <li>
                                        <span class="d-block h4 mb-0 text-primary"
                                              <?= isset($highlight["stat"]) ? 'data-site-stat="' . htmlspecialchars($highlight["stat"]) . '"' : '' ?>><?= htmlspecialchars($highlight["stat"] ?? $highlight["text"]) ?></span>
                                        <small class="text-uppercase text-muted"><?= htmlspecialchars($highlight["label"]) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5 wow fadeIn" data-wow-delay="0.6s">
            <a class="btn btn-primary text-uppercase px-5 py-3" href="<?= BASE_URL ?>/agendar">Marcar agora <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>