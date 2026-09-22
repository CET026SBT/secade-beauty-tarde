<?php
register_script("components/menuUser", "main");

$boCurrentPage = $boCurrentPage ?? "appointments";
$boUser = Session::user();
$boNameParts = explode(" ", trim($boUser["name"] ?? ""));
$boDisplayName = $boNameParts[0] . (count($boNameParts) > 1 ? " " . end($boNameParts) : "");

$boIsManager  = Session::isManager();
$boIsEmployee = Session::isEmployee();
$boHomeUrl    = $boIsEmployee ? BASE_URL . "/gestao/servicos" : BASE_URL . "/gestao/agendamentos";

$boLinks = $boIsEmployee
    ? [
        ["page" => "services",     "url" => "/gestao/servicos",       "icon" => "bi-list-check",      "label" => "Serviços"],
        ["page" => "appointments", "url" => "/gestao/agendamentos",   "icon" => "bi-calendar-check",  "label" => "Agendamentos"]
      ]
    : [
        ["page" => "appointments", "url" => "/gestao/agendamentos",   "icon" => "bi-calendar-check",  "label" => "Agendamentos"],
        ["page" => "routes",       "url" => "/gestao/rotas",          "icon" => "bi-signpost-split",  "label" => "Rotas"],
        ["page" => "fiscal",       "url" => "/gestao/fiscal",         "icon" => "bi-receipt-cutoff",  "label" => "Calendário Fiscal"],
        ["page" => "greenReceipts","url" => "/gestao/recibos-verdes", "icon" => "bi-cash-stack",      "label" => "Recibos Verdes"]
      ];
?>
<nav class="navbar navbar-expand-lg navbar-dark bo-navbar py-3">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $boHomeUrl ?>">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-logo.svg" alt="Secade Beauty" height="32" class="user-select-none" draggable="false">
            <span class="fw-bold">Backoffice</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#boNavbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="boNavbarCollapse">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-2">
                <?php foreach ($boLinks as $link): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $boCurrentPage === $link["page"] ? "active" : "" ?>"
                           href="<?= BASE_URL . $link["url"] ?>">
                            <i class="bi <?= $link["icon"] ?> me-1"></i> <?= htmlspecialchars($link["label"]) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary d-none d-lg-inline">
                    <i class="bi bi-person-badge me-1"></i><?= $boIsManager ? "Gestor" : "Funcionário" ?>
                </span>
                <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>/">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Ver o site
                </a>
                <span class="text-white small d-none d-xl-inline">
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($boDisplayName) ?>
                </span>
                <a class="btn btn-sm btn-primary" href="#" onclick="menuUser.logout(event)">
                    <i class="bi bi-box-arrow-right me-1"></i> Sair
                </a>
            </div>
        </div>
    </div>
</nav>