<?php
register_script("components/menuUser", "main");

require_once APP_PATH . "/utils/Session.php";
$isLoggedIn = Session::isLoggedIn();
$user = Session::user();

if ($isLoggedIn && $user): 
    $nameParts = explode(' ', $user['name']);
    $userName = $nameParts[0] . ' ' . end($nameParts);

    $profile    = Session::getUserProfile();
    $isManager  = $profile === "gestor";
    $isEmployee = $profile === "funcionario";
    $isStaff    = $isManager || $isEmployee;

    // Cada perfil vê apenas os atalhos a que tem acesso. As páginas de gestão estão
    // guardadas no servidor (ver o guard em cada ficheiro de modules/backoffice/) e
    // este menu tem de refletir exatamente essas permissões:
    //   - gestor      -> agendamentos, rotas, fiscal e recibos verdes
    //   - funcionário -> apenas a aceitação de serviços (a página /gestao/agendamentos
    //                    exige perfil de gestor e redireciona o funcionário para a home)
    $managementLinks = [];

    if ($isEmployee) {
        $managementLinks[] = ["url" => "/gestao/servicos", "icon" => "bi-list-check", "label" => "Aceitação de Serviços"];
    }

    if ($isManager) {
        $managementLinks[] = ["url" => "/gestao/agendamentos", "icon" => "bi-calendar-check", "label" => "Agendamentos"];
        $managementLinks[] = ["url" => "/gestao/rotas", "icon" => "bi-signpost-split", "label" => "Rotas"];
        $managementLinks[] = ["url" => "/gestao/fiscal", "icon" => "bi-receipt-cutoff", "label" => "Calendário Fiscal"];
        $managementLinks[] = ["url" => "/gestao/recibos-verdes", "icon" => "bi-cash-stack", "label" => "Recibos Verdes"];
    }
?>
<div class="menuUser dropdown ms-auto me-xl-4 order-xl-last">
    <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="text-white me-2 d-none d-lg-inline"><?= htmlspecialchars($userName) ?></span>
        <img src="<?= BASE_URL ?>/modules/common/img/testimonial-1.jpg"
            alt="<?= htmlspecialchars($userName) ?>"
            class="wh-40 rounded-circle object-fit-cover border border-2 border-primary">
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <!--
        <li>
            <a class="dropdown-item" href="<?= BASE_URL ?>/perfil">
                <i class="bi bi-person-circle me-2"></i>Perfil
            </a>
        </li>
        -->

        <?php if ($isStaff): ?>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-uppercase small">Gestão</h6></li>

            <?php foreach ($managementLinks as $link): ?>
                <li>
                    <a class="dropdown-item" href="<?= BASE_URL . $link["url"] ?>">
                        <i class="bi <?= $link["icon"] ?> me-2"></i><?= htmlspecialchars($link["label"]) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>
                <a class="dropdown-item" href="<?= BASE_URL ?>/agendamentos">
                    <i class="bi bi-calendar-check me-2"></i>Agendamentos
                </a>
            </li>
        <?php endif; ?>

        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-danger" href="#" onclick="menuUser.logout(event)">
                <i class="bi bi-box-arrow-right me-2"></i>Sair
            </a>
        </li>
    </ul>
</div>
<?php else: ?>
<a href="<?= BASE_URL ?>/login" class="btn btn-sm btn-primary no-bg px-3 py-2 m-0 ms-auto me-2 me-xl-4 order-xl-last">
    <i class="fa fa-user-lock me-2"></i>Iniciar Sessão
</a>
<?php endif; ?>