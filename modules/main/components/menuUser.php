<?php
register_script("components/menuUser", "main");

require_once APP_PATH . "/utils/Session.php";
$isLoggedIn = Session::isLoggedIn();
$user = Session::user();

if ($isLoggedIn && $user): 
    $nameParts = explode(' ', $user['name']);
    $userName = $nameParts[0] . ' ' . end($nameParts);
?>
<div class="menuUser dropdown">
    <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="text-white me-2 d-none d-lg-inline"><?= htmlspecialchars($userName) ?></span>
        <img src="<?= BASE_URL ?>/modules/common/img/testimonial-1.jpg"
            alt="<?= htmlspecialchars($userName) ?>"
            class="profile-image-40">
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" href="<?= BASE_URL ?>/perfil">
                <i class="fa fa-user me-2"></i>Perfil
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="<?= BASE_URL ?>/agendamentos">
                <i class="fa fa-calendar me-2"></i>Agendamentos
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-danger" href="#" onclick="menuUser.logout(event)">
                <i class="fa fa-sign-out-alt me-2"></i>Sair
            </a>
        </li>
    </ul>
</div>
<?php else: ?>
<a href="<?= BASE_URL ?>/login" class="btn btn-sm btn-primary no-bg m-0 ms-lg-3 px-3 py-2">
    <i class="fa fa-user-lock me-2"></i>Iniciar Sessão
</a>
<?php endif; ?>