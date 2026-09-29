<?php
/**
 * Menu do utilizador da área de gestão (dummy, próprio do backoffice).
 *
 * Diferente do `modules/main/components/menuUser.php` (site principal): é um menu
 * mínimo, sem opções que não pertencem à gestão. Mantém o mesmo aspeto, o mesmo
 * comportamento de abertura e a mesma ação de sair.
 *
 * Opções: avisos (com o contador de não lidos no sino), "Ver o site" e "Sair".
 * O perfil (gestor/funcionário) NÃO é mostrado aqui.
 */
$boMenuUser = Session::user();
$boMenuNameParts = explode(" ", trim($boMenuUser["name"] ?? ""));
$boMenuDisplayName = $boMenuNameParts[0] . (count($boMenuNameParts) > 1 ? " " . end($boMenuNameParts) : "");
?>
<div class="menuUser menuUser-bo dropdown ms-auto me-xl-4 order-xl-last">
    <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none p-0"
            type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <!-- Sininho: contador de avisos por tratar (RF-81 · D-15). O número é pedido
         ao `admin-alert-summary` pelo `bo.js` — nunca vem escrito na página. -->
        <a class="btn btn-link text-white position-relative p-1" id="boBell" href="<?= BASE_URL ?>/gestao/avisos" title="Avisos por tratar">
            <i class="bi bi-bell fs-5"></i>
            <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle d-none"
                id="boBellCount">0</span>
            <span class="visually-hidden">Avisos por tratar</span>
        </a>
        <span class="text-white me-2 d-none d-lg-inline"><?= htmlspecialchars($boMenuDisplayName) ?></span>
        <img src="<?= BASE_URL ?>/modules/common/img/testimonial-1.jpg"
             alt="<?= htmlspecialchars($boMenuDisplayName) ?>"
             class="wh-40 rounded-circle object-fit-cover border border-2 border-primary">
    </button>

    <ul class="dropdown-menu dropdown-menu-end menuUser-menu">
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_URL ?>/gestao/avisos">
                <i class="bi bi-bell"></i><span>Avisos</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
                <i class="bi bi-box-arrow-up-right"></i><span>Ver o site</span>
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="#"
               onclick="boMenuUser.logout(event)">
                <i class="bi bi-box-arrow-right"></i><span>Sair</span>
            </a>
        </li>
    </ul>
</div>