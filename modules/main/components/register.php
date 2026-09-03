<?php
register_script('validators/user.validator', 'common');
register_script('validators/customer.validator', 'common');
register_script('utils/addressAutocomplete', 'common');
register_script('components/customerRegister', 'common');
?>

<div class="container-fluid px-0 d-flex flex-fill h-100">
    <div class="row align-items-center flex-fill m-0 w-100">
        <div class="col-xl-5 h-100 d-none d-xl-flex text-white position-relative align-items-center justify-content-center p-0 overflow-hidden">
            <img src="<?= BASE_URL ?>/common/img/bg-login.png" alt="Imagem Lateral" class="auth-left-img">
        </div>

        <div class="col-xl-7 h-100 d-flex flex-column align-items-center justify-content-center position-relative py-5 overflow-hidden bg-light">
            <img src="<?= BASE_URL ?>/common/img/sb-logo-primary.svg" alt="Secade Beauty" class="auth-logo-watermark">

            <div class="col-xl-10 p-4 bg-white shadow-sm border-0 rounded position-relative my-auto d-flex flex-column overflow-hidden" style="z-index: 1; max-width: 600px;">
                <div class="text-center mb-4">
                    <h3 class="text-dark">Criar a sua conta</h3>
                    <div class="text-muted small px-xl-3">Preencha os dados abaixo para aceder aos nossos serviços itinerantes.</div>
                </div>

                <form id="customerRegisterForm" class="flex-fill">
                    <div class="form-steps-container">
                        <div class="form-step active">
                            <h4 class="section-title">
                                <i class="bi bi-person-badge text-gold me-2"></i>Quem é você?
                            </h4>

                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input name="nome" type="text" class="form-control" placeholder="Nome Completo*" required>
                                        <label>Nome Completo*</label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input name="email" type="email" class="form-control" placeholder="E-mail*" required>
                                        <label>E-mail*</label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input name="password" type="password" class="form-control" placeholder="Palavra-passe*" required>
                                        <label>Palavra-passe*</label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input name="confirmPassword" type="password" class="form-control" placeholder="Confirmar Palavra-passe*" required>
                                        <label>Confirmar Palavra-passe*</label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary w-100 py-2 m-0 fw-bold text-uppercase" onclick="customerRegister.form.nextStep()">Seguinte</button>
                        </div>

                        <div class="form-step">
                            <h4 class="section-title">
                                <i class="bi bi-geo-alt text-gold me-2"></i>Onde vamos encontrá-lo?
                            </h4>

                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input name="telemovel" type="tel" class="form-control" placeholder="Telemóvel*" required>
                                        <label>Telemóvel*</label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input name="morada" type="text" class="form-control" placeholder="Morada*" required list="moradas-list" sb-validate-on="change">
                                        <label>Morada*</label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                    <div class="feedback-slot my-1">
                                        <div class="alert alert-info py-2 px-3 small mb-0" role="alert">
                                            <i class="bi bi-info-circle me-1"></i> <span id="moradaFeedback">Serviço disponível apenas para as cidades com loja e carrinha.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-floating">
                                        <input name="numPorta" type="text" class="form-control" placeholder="Nº da Porta*" required>
                                        <label>Nº da Porta*</label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-floating">
                                        <input name="andarBloco" type="text" class="form-control" placeholder="Andar / Bloco">
                                        <label>Andar / Bloco</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input name="termosCondicoes" class="form-check-input" type="checkbox" value="" required>
                                        <label class="form-check-label small text-muted">
                                            Li e aceito os <a href="#" target="_blank" class="text-gold text-decoration-underline">Termos e Condições</a> e a Política de Privacidade.
                                        </label>
                                        <div class="invalid-feedback mt-0 mb-1"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-4">
                                <button type="button" class="btn btn-primary no-bg w-50 py-2 m-0 fw-bold text-uppercase" onclick="customerRegister.form.prevStep()">Voltar</button>
                                <button type="button" class="btn btn-primary w-50 py-2 m-0 fw-bold text-uppercase" onclick="customerRegister.form.submit()">Registar</button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="small text-muted">Já tem uma conta? <a href="<?= BASE_URL ?>/login" class="text-gold fw-bold text-decoration-underline">Inicie sessão</a></p>
                </div>
            </div>
        </div>
    </div>
</div>