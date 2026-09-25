<?php
register_script("components/menuUserBo", "backoffice");

$boCurrentPage = $boCurrentPage ?? "appointments";
$boIsEmployee = Session::isEmployee();
$boHomeUrl    = $boIsEmployee ? BASE_URL . "/gestao/servicos" : BASE_URL . "/gestao/agendamentos";

// Cada perfil só vê páginas a que tem acesso: /gestao/agendamentos exige perfil de
// gestor (ver o guard em modules/backoffice/appointments.php) e redireciona o
// funcionário para a home — por isso não é apresentada no menu dele.
$boLinks = $boIsEmployee
    ? [
        ["page" => "services",     "url" => "/gestao/servicos",       "icon" => "bi-list-check",      "label" => "Serviços"]
      ]
    : [
        ["page" => "appointments", "url" => "/gestao/agendamentos",   "icon" => "bi-calendar-check",  "label" => "Agendamentos"],
        ["page" => "routes",       "url" => "/gestao/rotas",          "icon" => "bi-signpost-split",  "label" => "Rotas"],
        ["page" => "fiscal",       "url" => "/gestao/fiscal",         "icon" => "bi-receipt-cutoff",  "label" => "Calendário Fiscal"],
        ["page" => "greenReceipts","url" => "/gestao/recibos-verdes", "icon" => "bi-cash-stack",      "label" => "Recibos Verdes"]
      ];
?>
<nav class="navbar navbar-expand-xl navbar-dark bo-navbar py-3">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $boHomeUrl ?>">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-logo-primary.svg" alt="Secade Beauty" height="32" class="user-select-none" draggable="false">
            <span class="fw-bold">Backoffice</span>
        </a>

        <?php include ROOT_PATH . "/modules/backoffice/components/menuUserBo.php" ?>

        <button type="button" class="navbar-toggler mx-2" data-bs-toggle="collapse" data-bs-target="#boNavbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="boNavbarCollapse">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-2">
                <?php foreach ($boLinks as $link): ?>
                    <li class="nav-item">
                        <a class="nav-link py-sm-1 <?= $boCurrentPage === $link["page"] ? "active" : "" ?>"
                           href="<?= BASE_URL . $link["url"] ?>">
                            <i class="bi <?= $link["icon"] ?> me-1"></i> <?= htmlspecialchars($link["label"]) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>