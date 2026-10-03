<?php
// Como funciona — quatro passos numerados (conceito da referência), com os fluxos reais
// da plataforma: catálogo -> loja ou carrinha -> aceitação pela equipa -> acompanhamento.
$passos = [
    ["icon" => "fas fa-spa", "titulo" => "Escolhe os serviços",
     "texto" => "No catálogo, por área. Vê o preço com IVA e o tempo estimado antes de decidir."],
    ["icon" => "bi bi-shop", "titulo" => "Marca na loja ou em casa",
     "texto" => "Loja de Évora, de terça a sábado, em marcações de 30 minutos — ou a carrinha ao domicílio."],
    ["icon" => "fas fa-truck", "titulo" => "A equipa aceita",
     "texto" => "Cada serviço é assumido por um profissional e o agendamento fica registado."],
    ["icon" => "fas fa-clipboard-check", "titulo" => "Acompanha e avalia",
     "texto" => "Segue o estado em \"Meus Agendamentos\" e, no fim, deixa a sua avaliação."]
];
?>
<div class="container-fluid service py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.1s">
            <h1 class="font-dancing-script text-primary">Como funciona</h1>
            <h1 class="mb-5">Quatro passos simples</h1>
        </div>
        <div class="row g-4">
            <?php foreach ($passos as $index => $passo): ?>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="<?= 0.2 + $index * 0.1 ?>s">
                    <div class="service-item h-100 bg-light border-bottom border-end text-center p-4">
                        <div class="btn-square-lg bg-primary extended-border mx-auto mb-3">
                            <span class="h4 mb-0 text-dark mb-2"><?= $index + 1 ?></span>
                        </div>
                        <i class="<?= htmlspecialchars($passo["icon"]) ?> fa-3x text-primary mb-3"></i>
                        <h4 class="mb-2"><?= htmlspecialchars($passo["titulo"]) ?></h4>
                        <p class="mb-0"><?= htmlspecialchars($passo["texto"]) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>