<?php
register_script("validators/login.validator", "common");
register_script("components/login", "main");
?>

<div class="container-fluid px-0 d-flex flex-fill">
    <div class="row align-items-center flex-fill m-0">
        <div class="col-lg-5 h-100 d-none d-lg-flex text-white position-relative align-items-center justify-content-center p-0 overflow-hidden">
            <img src="<?= BASE_URL ?>/modules/common/img/bg-login.png" alt="Imagem Lateral" class="auth-left-img user-select-none" draggable="false">
        </div>

        <div class="col-lg-7 h-100 d-flex align-items-center justify-content-center position-relative py-5 overflow-hidden bg-light">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-logo-primary.svg" alt="Secade Beauty" class="auth-logo-watermark user-select-none" draggable="false">

            <div class="col-md-10 p-4 bg-white shadow-sm border-0 rounded position-relative" style="z-index: 1; max-width: 500px;">
                <div class="text-center mb-4">
                    <h3 class="text-dark">Iniciar Sessão</h3>
                    <div class="text-muted small px-lg-3">Bem-vindo de volta! Aceda à sua conta.</div>
                </div>

                <form id="loginForm">
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="form-floating">
                                <input name="email" type="email" class="form-control" placeholder="E-mail" required>
                                <label>E-mail</label>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <input name="password" type="password" class="form-control" placeholder="Palavra-passe" required>
                                <label>Palavra-passe</label>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input name="rememberMe" type="checkbox" class="form-check-input">
                                    <label class="form-check-label small text-muted me-2">Lembrar-me</label>
                                </div>
                                <a href="<?= BASE_URL ?>/recuperar-passe" class="small text-primary text-decoration-none">Esqueceu-se da passe?</a>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 m-0 fw-bold text-uppercase">Entrar</button>
                </form>

                <div class="text-center mt-4">
                    <p class="small text-muted">Ainda não tem conta? <a href="<?= BASE_URL ?>/registo" class="text-primary fw-bold text-decoration-none">Registe-se aqui</a></p>
                </div>
            </div>
        </div>
    </div>
</div>