<?php
// Os nossos valores — grelha de cartões de texto (conceito da referência, conteúdo Secade).
// Sem números inventados: os que aparecem contados vêm de `siteStats.utils.js`.
$valores = [
    ["icon" => "fas fa-award", "titulo" => "Qualidade",
     "texto" => "Cada serviço é executado por profissionais e registado no agendamento — do primeiro ao último passo."],
    ["icon" => "fas fa-handshake", "titulo" => "Confiança",
     "texto" => "O preço com IVA e a duração estimada aparecem antes de confirmar. Só depois é que o sinal é pago."],
    ["icon" => "fas fa-magic", "titulo" => "Comodidade",
     "texto" => "Na loja de Évora ou em casa, é a carrinha que se desloca. Escolhe-se o que dá menos trabalho."],
    ["icon" => "fas fa-map-marked-alt", "titulo" => "Proximidade",
     "texto" => "Empresa local: atendemos o distrito de Évora, nas cidades onde já vamos regularmente."],
    ["icon" => "fas fa-eye", "titulo" => "Transparência",
     "texto" => "Cada aceitação, execução e pagamento fica registado no sistema — sem surpresas no fim."],
    ["icon" => "fas fa-lightbulb", "titulo" => "Inovação",
     "texto" => "Tecnologia na marcação (código de confirmação, estados do pedido) sem perder o atendimento humano."]
];
?>
<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.1s">
            <h1 class="font-dancing-script text-primary">Os nossos valores</h1>
            <h1 class="mb-5">O que nos guia no dia a dia</h1>
        </div>
        <div class="row g-4">
            <?php foreach ($valores as $index => $valor): ?>
                <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="<?= 0.2 + $index * 0.1 ?>s">
                    <div class="bg-light border-bottom border-end h-100 p-4">
                        <i class="<?= htmlspecialchars($valor["icon"]) ?> fa-3x text-primary mb-3"></i>
                        <h4 class="mb-2"><?= htmlspecialchars($valor["titulo"]) ?></h4>
                        <p class="mb-0"><?= htmlspecialchars($valor["texto"]) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>