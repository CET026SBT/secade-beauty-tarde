<?php
$currentPage = isset($currentPage) ? $currentPage : basename($_SERVER["PHP_SELF"]);
$isAuthPage = in_array($currentPage, ["login.php", "login", "customerRegister.php", "registo"]);
?>

<div class="container-fluid bg-dark sticky-top p-0">
    <nav class="navbar navbar-expand-lg navbar-dark p-0">
        <a href="<?= BASE_URL ?>/" class="navbar-brand px-3 px-md-5 me-0">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-logo.png" class="site-logo user-select-none" draggable="false">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-title.png" class="site-title user-select-none" draggable="false">
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse p-3" id="navbarCollapse">
            <div class="navbar-nav me-auto">
                <a href="<?= BASE_URL ?>/" class="nav-item nav-link <?php echo in_array($currentPage, ["home.php", "home", ""]) ? "active" : ""; ?>">Home</a>
                <?php if (!$isAuthPage): ?>
                    <a href="<?= BASE_URL ?>/sobre" class="nav-item nav-link <?php echo in_array($currentPage, ["about.php", "about", "sobre"]) ? "active" : ""; ?>">Acerca</a>
                    <a href="<?= BASE_URL ?>/servicos" class="nav-item nav-link <?php echo in_array($currentPage, ["serviceCategories.php", "service", "servicos"]) ? "active" : ""; ?>">Servicos</a>
                    <a href="<?= BASE_URL ?>/contacto" class="nav-item nav-link <?php echo in_array($currentPage, ["contact.php", "contact", "contacto"]) ? "active" : ""; ?>">Contactos</a>
                <?php endif; ?>
            </div>
            <?php if (!$isAuthPage): ?>
                <?php 
                require_once APP_PATH . "/utils/Session.php";
                $isLoggedIn = Session::isLoggedIn();
                if ($isLoggedIn && $user = Session::user()): 
                    $nameParts = explode(' ', $user['name']);
                    $userName = $nameParts[0] . ' ' . end($nameParts);
                ?>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle d-flex align-items-center text-decoration-none p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="text-white me-2 d-none d-lg-inline"><?= htmlspecialchars($userName) ?></span>
                            <img src="<?= BASE_URL ?>/modules/common/img/testimonial-1.jpg" 
                                 alt="<?= htmlspecialchars($userName) ?>"
                                 style="width:40px;height:40px;object-fit:cover;border-radius:50%;border:2px solid #d4a574;">
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
                                <a class="dropdown-item text-danger" href="#" onclick="logout(event)">
                                    <i class="fa fa-sign-out-alt me-2"></i>Sair
                                </a>
                            </li>
                        </ul>
                    </div>
                    <script>
                    async function logout(e) {
                        e.preventDefault();
                        if (!confirm('Tem a certeza que deseja terminar sessão?')) return;
                        
                        try {
                            const response = await fetch('<?= BASE_URL ?>/api?action=auth-logout', { 
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' }
                            });
                            const data = await response.json();
                            
                            if (data.success) {
                                location.href = '<?= BASE_URL ?>/';
                            }
                        } catch (error) {
                            console.error('Erro ao terminar sessão:', error);
                            alert('Erro ao terminar sessão. Tente novamente.');
                        }
                    }
                    </script>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login" class="btn btn-sm btn-primary no-bg m-0 ms-lg-3 px-3 py-2">
                        <i class="fa fa-user-lock me-2"></i>Iniciar Sessão
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </nav>
</div>