<?php
// A equipa — conceito de duas colunas (imagem + texto). A imagem é um ESPAÇO RESERVADO.
// O número de profissionais é DOCUMENTAL (folha de salários): ver `SITE_STATS_DOCUMENTAL`.
?>
<div class="container-fluid py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 wow fadeIn align-self-stretch d-flex flex-column" data-wow-delay="0.1s">
                <div class="img-container flex-grow-1">
                    <img src="<?= BASE_URL ?>/modules/common/img/about-images/about-team.png"
                        alt="Equipa da Secade Beauty">
                </div>
            </div>
            <div class="col-lg-6 wow fadeIn info-container" data-wow-delay="0.3s">
                <h1 class="font-dancing-script text-primary">A equipa</h1>
                <h1 class="mb-4">Quem executa o serviço que marcou</h1>
                <p class="mb-4">Por trás de cada marcação está uma equipa de
                    <strong><span data-site-stat="team"><?= (int)SITE_STATS_FALLBACK["team"] ?></span> profissionais</strong>,
                    organizada pelas três áreas do catálogo: <strong>cabeleireiro</strong> (tranças e penteados),
                    <strong>barbearia</strong> (cortes e barba) e <strong>estética</strong> (maquilhagem, manicure e
                    limpeza facial).</p>
                <p class="mb-4">Quando o cliente marca, os serviços ficam pendentes de aceitação e é a equipa que os
                    assume, um a um, escolhendo quem os executa. Um agendamento só fica
                    <strong>totalmente aceite</strong> quando o último serviço é assumido — é isso que garante que
                    ninguém fica à espera sem resposta.</p>
                <p class="mb-0">A equipa da carrinha trabalha em <strong>recibos verdes</strong>, com o valor do
                    serviço e a deslocação calculados no próprio sistema; na loja, o trabalho é apoiado pelo mesmo
                    registo digital, do aceitar ao executar.</p>
            </div>
        </div>
    </div>
</div>