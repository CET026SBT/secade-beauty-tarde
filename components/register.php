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
                <div class="text-center mb-4">
                    <h3 id="headerTitle" class="text-dark">Criar a sua conta</h3>
                    <div id="headerDesc" class="text-muted small px-xl-3">Preencha os dados abaixo para aceder aos nossos serviços itinerantes.</div>
                </div>

                <form id="registerForm" class="flex-fill">
                    <div class="form-steps-container">
                        <div id="step-1" class="form-step active">
                            <div class="row g-4 mb-4">
                                <h4 class="section-title col-12">
                                    <i class="bi bi-person-badge text-gold me-2"></i>Quem é você?
                                </h4>

                                <div class="col-12">
                                    <div class="form-floating"> 
                                        <input id="nome" type="text" class="form-control" placeholder="Nome Completo*" required>
                                        <label for="nome">Nome Completo*</label>
                                        <div class="invalid-feedback">Insira o seu nome completo.</div>
                                    </div>
                                    
                                </div>

                                <div class="col-12">
                                    <div class="form-floating"> 
                                        <input id="email" type="email" class="form-control" placeholder="E-mail*" required>
                                        <label for="email">E-mail*</label>
                                        <div class="invalid-feedback">A morada é obrigatória.</div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-floating">  
                                        <input id="password" type="password" class="form-control" placeholder="Palavra-passe*" required>
                                        <label for="password">Palavra-passe*</label>
                                        <div class="invalid-feedback">A palavra-passe é obrigatória.</div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">  
                                        <input id="confirmPassword" type="password" class="form-control" placeholder="Confirmar Palavra-passe*" required>
                                        <label for="confirmPassword">Confirmar Palavra-passe*</label>
                                        <div class="invalid-feedback">As palavras-passe têm de coincidir.</div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary w-100 py-2 m-0 fw-bold text-uppercase" onclick="formSteps.navigateTo(1)">Seguinte</button>
                        </div>

                        <div id="step-2" class="form-step">
                            <div class="row g-4 mb-4">
                                <h4 class="section-title col-12">
                                    <i class="bi bi-geo-alt text-gold me-2"></i>Onde vamos encontrá-lo?
                                </h4>

                                <div class="col-12">
                                    <div class="form-floating">  
                                        <input id="telemovel" type="tel" class="form-control" placeholder="Telemóvel*" required>
                                        <label for="telemovel">Telemóvel*</label>
                                        <div class="invalid-feedback">Insira um número de telemóvel válido.</div>
                                    </div>
                                </div>

                                <div class="col-12">  
                                    <div class="form-floating">
                                        <input id="morada" type="text" class="form-control" placeholder="Morada*" required>
                                        <label for="morada">Morada*</label>
                                        <div class="invalid-feedback">A morada é obrigatória.</div>
                                    </div>
                                    <div class="feedback-slot mt-1">
                                        <div id="moradaFeedback" class="alert alert-info py-2 px-3 small mb-0" role="alert">
                                            <i class="bi bi-info-circle me-1"></i> <span id="moradaFeedbackText">Serviço disponível apenas para as cidades com loja e carrinha.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6"> 
                                    <div class="form-floating">  
                                        <input id="numPorta" type="text" class="form-control" placeholder="Nº da Porta*" required>
                                        <label for="numPorta">Nº da Porta*</label>
                                        <div class="invalid-feedback">Obrigatório.</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-floating">  
                                        <input id="andarBloco" type="text" class="form-control" placeholder="Andar / Bloco">
                                        <label for="andarBloco">Andar / Bloco</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input id="termosCondicoes" class="form-check-input" type="checkbox" value="" required>
                                        <label for="termosCondicoes" class="form-check-label small text-muted">
                                            Li e aceito os <a href="#" target="_blank" class="text-gold text-decoration-underline">Termos e Condições</a> e a Política de Privacidade.
                                        </label>
                                        <div class="invalid-feedback">Deve aceitar os termos e condições para continuar.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-4">
                                <button type="button" class="btn btn-primary no-bg w-50 py-2 m-0 fw-bold text-uppercase" onclick="formSteps.navigateTo(0)">Voltar</button>
                                <button type="button" class="btn btn-primary w-50 py-2 m-0 fw-bold text-uppercase">Registar</button>
                            </div>
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