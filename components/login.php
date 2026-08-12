<?php
require_once __DIR__ . '/../includes/config.php';
register_script('components/login');
?>

<div class="container-fluid px-0 d-flex flex-fill">
    <div class="row align-items-center flex-fill m-0">
        <div class="col-lg-6 h-100 d-none d-lg-flex text-white position-relative align-items-center justify-content-center p-0 overflow-hidden">
            <img src="img/bg-login.png" alt="Imagem Login" class="auth-left-img">
        </div>

        <div class="col-lg-6 h-100 d-flex align-items-center justify-content-center position-relative py-5 overflow-hidden bg-light">
            <img src="img/sb-logo-primary.svg" alt="Secade Beauty" class="auth-logo-watermark">
            
            <div class="col-md-6 col-lg-auto p-4 bg-white shadow-sm border-0 rounded position-relative" style="z-index: 1">
                <div class="text-center mb-4">
                    <h3 class="text-dark">Iniciar Sessão</h3>
                    <p class="text-muted small px-lg-3">Bem-vindo de volta! Aceda à sua conta.</p>
                </div>

                <form id="loginForm">
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="form-floating">    
                                <input id="email" type="email" class="form-control py-2" placeholder="E-mail" required>
                                <label for="email">E-mail</label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-floating">  
                                <input id="password" type="password" class="form-control py-2" placeholder="Palavra-passe" required>
                                <label for="password">Palavra-passe</label>    
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe">
                                    <label class="form-check-label small text-muted me-2" for="rememberMe">Lembrar-me</label>
                                </div>
                                <a href="recover_password.php" class="small text-primary text-decoration-none">Esqueceu-se da passe?</a>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-primary w-100 py-2 m-0 fw-bold text-uppercase">Entrar</button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="small text-muted">Ainda não tem conta? <a href="register.php" class="text-primary fw-bold text-decoration-none">Registe-se aqui</a></p>
                </div>
            </div>
        </div>
    </div>
</div>