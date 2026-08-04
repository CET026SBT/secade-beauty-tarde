<div class="container-fluid px-0 flex-1">
    <div class="row align-items-center">
        
        <!-- Lado Esquerdo: Imagem / Branding (Oculto em telemóveis) -->
        <div class="col-lg-6 d-none d-lg-flex bg-dark text-white position-relative align-items-center justify-content-center py-5">
            <div class="text-center p-5 z-index-1">
                <img src="img/sb-logo-primary.svg" alt="Secade Beauty" height="80" class="mb-4">
                <h2 class="text-primary mb-3">Secade Beauty</h2>
                <p class="text-white-50 lead w-75 mx-auto">Experiência, elegância e inovação em serviços de beleza, na nossa loja ou na carrinha itinerante.</p>
            </div>
        </div>

        <!-- Lado Direito: Formulário de Login -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center py-5 bg-light">
            <div class="col-md-8 col-lg-7 p-4 bg-white shadow-sm border-0 rounded">
                
                <div class="text-center mb-4">
                    <h3 class="text-dark font-weight-bold">Iniciar Sessão</h3>
                    <p class="text-muted small">Introduza os seus dados para gerir agendamentos</p>
                </div>

                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">E-mail</label>
                        <input type="email" name="email" class="form-control bg-light border-0 py-2" placeholder="exemplo@email.pt" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Palavra-passe</label>
                        <input type="password" name="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label small text-muted" for="rememberMe">Lembrar-me</label>
                        </div>
                        <a href="recuperar_passe.php" class="small text-primary text-decoration-none">Esqueceu-se da passe?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold text-uppercase">Entrar</button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="small text-muted">Ainda não tem conta? <a href="register.php" class="text-primary fw-bold text-decoration-none">Registe-se aqui</a></p>
                </div>

            </div>
        </div>

    </div>
</div>