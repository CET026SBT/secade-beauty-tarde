<?php
require_once __DIR__ . '/../includes/config.php';
register_script('components/register');
?>

<div class="container-fluid px-0 d-flex flex-fill h-100">
    <div class="row align-items-center flex-fill m-0 w-100">
        <div class="col-xl-6 h-100 d-none d-xl-flex text-white position-relative align-items-center justify-content-center p-0 overflow-hidden">
            <img src="img/bg-login.png" alt="Imagem Registo" class="auth-left-img">
        </div>

        <div class="col-xl-6 h-100 d-flex flex-column align-items-center justify-content-center position-relative py-5 overflow-hidden bg-light">
            <img src="img/sb-logo-primary.svg" alt="Secade Beauty" class="auth-logo-watermark">
            
            <div class="col-xl-10 p-4 bg-white shadow-sm border-0 rounded position-relative my-auto d-flex flex-column overflow-hidden" style="z-index: 1; max-width: 600px;">
                <div id="formHeader" class="text-center mb-4">
                    <h3 id="headerTitle" class="text-dark">Criar a sua conta</h3>
                    <div id="headerDesc" class="text-muted small px-xl-3">Preencha os dados abaixo para aceder aos nossos serviços itinerantes.</div>
                </div>

                <form id="registerForm" class="flex-fill">
                    <div id="step-1" class="form-step">
                        <div class="mb-4">
                            <h4 class="section-title mb-3 animated">
                                <i class="bi bi-person-badge text-gold me-2"></i>Quem é você?
                            </h4>

                            <div class="form-floating mb-3 animated" style="animation-delay: 0.05s;">    
                                <input id="nome" type="text" class="form-control py-2" placeholder="Nome Completo" autocomplete="off" required>
                                <label for="nome">Nome Completo</label>
                            </div>

                            <div class="form-floating mb-3 animated" style="animation-delay: 0.1s;">    
                                <input id="email" type="email" class="form-control py-2" placeholder="E-mail" autocomplete="off" required>
                                <label for="email">E-mail</label>
                            </div>
                            
                            <div class="form-floating mb-3 animated" style="animation-delay: 0.15s;">  
                                <input id="password" type="password" class="form-control py-2" placeholder="Palavra-passe" autocomplete="off" required>
                                <label for="password">Palavra-passe</label>    
                            </div>

                            <div class="form-floating mb-3 animated" style="animation-delay: 0.2s;">  
                                <input id="confirmPassword" type="password" class="form-control py-2" placeholder="Confirmar Palavra-passe" autocomplete="off" required>
                                <label for="confirmPassword">Confirmar Palavra-passe</label>    
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 py-2 m-0 fw-bold text-uppercase animated" onclick="goToStep(2)">Seguinte</button>
                    </div>

                    <div id="step-2" class="form-step d-none">
                        <div class="mb-4">
                            <h4 class="section-title mb-3 animated">
                                <i class="bi bi-geo-alt text-gold me-2"></i>Onde vamos encontrá-lo?
                            </h4>

                            <div class="form-floating mb-3 animated" style="animation-delay: 0.05s;">  
                                <input id="telemovel" type="tel" class="form-control py-2" placeholder="Telemóvel" autocomplete="off" required>
                                <label for="telemovel">Telemóvel</label>    
                            </div>

                            <div class="form-floating mb-3 animated" style="animation-delay: 0.1s;">  
                                <input id="morada" type="text" class="form-control py-2" placeholder="Morada" autocomplete="off" required>
                                <label for="morada">Morada</label>    
                            </div>

                            <div class="form-floating mb-3 animated" style="animation-delay: 0.15s;">  
                                <input id="codigoPostal" type="text" class="form-control py-2" placeholder="Código Postal" autocomplete="off" required>
                                <label for="codigoPostal">Código Postal</label>    
                            </div>

                            <div class="form-floating mb-3 animated" style="animation-delay: 0.2s;">  
                                <input id="localidade" type="text" class="form-control py-2" placeholder="Localidade" autocomplete="off" required>
                                <label for="localidade">Localidade</label>    
                            </div>
                        </div>

                        <div class="d-flex gap-4">
                            <button type="button" class="btn btn-primary no-bg w-50 py-2 m-0 fw-bold text-uppercase animated" onclick="goToStep(1)">Voltar</button>
                            <button type="button" class="btn btn-primary w-50 py-2 m-0 fw-bold text-uppercase animated">Registar</button>
                        </div>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="small text-muted">Já tem uma conta? <a href="login.php" class="text-gold fw-bold text-decoration-underline">Inicie sessão</a></p>
                </div>
            </div>
        </div>
    </div>
</div>