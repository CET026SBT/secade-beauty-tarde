<?php
/**
 * Sidebar do backoffice (RF-84 · §25.5 · Fase 6.4).
 *
 * A navbar de topo tinha chegado ao limite: com os módulos da Fase 6 (painel,
 * fornecedores, contabilidade, RH, comissões, avisos) os links não cabiam. A
 * sidebar passa a ser o menu principal no desktop e um `collapse` no telemóvel
 * (Bootstrap 5.0.0 não tem offcanvas responsivo — a classe `offcanvas-lg` só
 * existe a partir da 5.2, e a stack é fixa).
 *
 * Cada perfil só vê páginas a que tem acesso (§18.6): o guard de cada página
 * continua a ser a autoridade — aqui só não se mostram links que falhariam.
 */
$boSidebarPage = $boCurrentPage ?? "dashboard";
$boSidebarGroups = Session::isEmployee()
    ? [
        ["label" => "A minha operação", "links" => [
            ["page" => "agenda",     "url" => "/gestao/agenda",     "icon" => "bi-calendar3",     "label" => "Agenda"],
            ["page" => "services",   "url" => "/gestao/servicos",   "icon" => "bi-list-check",    "label" => "Serviços"],
            ["page" => "commissions","url" => "/gestao/comissoes",  "icon" => "bi-cash-stack",    "label" => "Comissões"],
            ["page" => "alerts",     "url" => "/gestao/avisos",     "icon" => "bi-bell",          "label" => "Avisos"]
        ]]
      ]
    : [
        ["label" => "Visão geral", "links" => [
            ["page" => "dashboard",    "url" => "/gestao/painel",         "icon" => "bi-speedometer2",   "label" => "Painel"],
            ["page" => "alerts",       "url" => "/gestao/avisos",         "icon" => "bi-bell",           "label" => "Avisos"]
        ]],
        ["label" => "Operação", "links" => [
            ["page" => "appointments", "url" => "/gestao/agendamentos",   "icon" => "bi-calendar-check", "label" => "Agendamentos"],
            ["page" => "routes",       "url" => "/gestao/rotas",          "icon" => "bi-signpost-split", "label" => "Rotas"],
            ["page" => "services",     "url" => "/gestao/servicos",       "icon" => "bi-list-check",     "label" => "Serviços"]
        ]],
        ["label" => "Financeiro", "links" => [
            ["page" => "fiscal",       "url" => "/gestao/fiscal",         "icon" => "bi-receipt-cutoff", "label" => "Calendário Fiscal"],
            ["page" => "greenReceipts","url" => "/gestao/recibos-verdes", "icon" => "bi-cash-stack",     "label" => "Recibos Verdes"],
            ["page" => "commissions",  "url" => "/gestao/comissoes",      "icon" => "bi-cash-stack",     "label" => "Comissões"]
        ]],
        ["label" => "Gestão", "links" => [
            ["page" => "suppliers",    "url" => "/gestao/fornecedores",   "icon" => "bi-truck",          "label" => "Fornecedores"]
        ]]
      ];
?>
<aside class="bo-sidebar collapse d-lg-block bg-dark" id="boSidebar">
    <?php foreach ($boSidebarGroups as $group): ?>
        <span class="bo-sidebar-group"><?= htmlspecialchars($group["label"]) ?></span>
        <ul class="bo-sidebar-nav">
            <?php foreach ($group["links"] as $link): ?>
                <li>
                    <a class="bo-sidebar-link <?= $boSidebarPage === $link["page"] ? "active" : "" ?>"
                       href="<?= BASE_URL . $link["url"] ?>">
                        <i class="bi <?= $link["icon"] ?>"></i><span><?= htmlspecialchars($link["label"]) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>

    <a class="bo-sidebar-link bo-sidebar-link--out" href="<?= BASE_URL ?>/">
        <i class="bi bi-box-arrow-up-right"></i><span>Ver o site</span>
    </a>
</aside>
