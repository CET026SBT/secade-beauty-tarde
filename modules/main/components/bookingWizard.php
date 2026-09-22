<?php
register_script("validators/booking.validator", "common");
register_script("components/bookingWizard", "main");

$minBookingDate  = date("Y-m-d", strtotime("+1 day"));
$bookingUserName = Session::user()["name"] ?? "";
?>
<div class="container-fluid py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-4 wow fadeIn" data-wow-delay="0.1s">
                    <h1 class="font-dancing-script text-primary mb-2">Marcação Online</h1>
                    <p class="text-muted mb-0">Loja física em Évora ou carrinha ambulante na sua localidade.</p>
                </div>

                <div id="bookingStepsIndicator" class="booking-steps mb-4"></div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <form id="bookingWizardForm" data-client-name="<?= htmlspecialchars($bookingUserName) ?>">
                            <div class="form-steps-container">

                                <!-- ============ PASSO 1: SERVIÇOS ============ -->
                                <div class="form-step active" data-step="services">
                                    <h4 class="section-title">
                                        <i class="bi bi-scissors text-gold me-2"></i>Passo 1 · Seleção de Serviços
                                    </h4>
                                    <p class="text-muted small">Escolha um ou mais serviços. O total e a duração são calculados automaticamente.</p>

                                    <div id="bookingServicePicker" class="mb-3" preloader-defer>
                                        <div class="filters d-flex flex-wrap gap-2 mb-3"></div>
                                        <div class="services-list row g-3"></div>
                                    </div>

                                    <div class="alert alert-light border d-flex flex-wrap justify-content-between gap-2 mb-3">
                                        <span><i class="bi bi-clock me-1 text-primary"></i>Duração total: <strong id="totalDuration">0 min</strong></span>
                                        <span><i class="bi bi-tag me-1 text-primary"></i>Valor total: <strong id="totalAmount">0,00 €</strong></span>
                                    </div>

                                    <div class="alert alert-danger d-none" id="servicesError" role="alert"></div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-primary px-4" data-step-next>
                                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ============ PASSO 2: CANAL ============ -->
                                <div class="form-step" data-step="channel">
                                    <h4 class="section-title">
                                        <i class="bi bi-geo-alt text-gold me-2"></i>Passo 2 · Onde prefere ser atendido?
                                    </h4>

                                    <div class="row g-4 mb-3">
                                        <div class="col-md-6">
                                            <label class="channel-card d-block h-100 cursor-pointer">
                                                <input type="radio" name="channel" value="loja_fisica" class="d-none" form-validate-on="change">
                                                <div class="card h-100 border channel-card-body">
                                                    <div class="card-body">
                                                        <i class="bi bi-building fs-1 text-primary mb-3 d-block"></i>
                                                        <h5 class="mb-2">Loja Física</h5>
                                                        <p class="text-muted small mb-2">Atendimento no nosso salão, em Évora.</p>
                                                        <ul class="small text-muted mb-0 ps-3">
                                                            <li>Terça a Sábado, 09:00 – 19:00</li>
                                                            <li>Sem custos de deslocação</li>
                                                            <li>Serviços validados automaticamente</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="channel-card d-block h-100 cursor-pointer" id="ambChannelCard">
                                                <input type="radio" name="channel" value="carrinha_ambulante" class="d-none" form-validate-on="change">
                                                <div class="card h-100 border channel-card-body">
                                                    <div class="card-body">
                                                        <i class="bi bi-truck fs-1 text-primary mb-3 d-block"></i>
                                                        <h5 class="mb-2">Carrinha Ambulante</h5>
                                                        <p class="text-muted small mb-2">Vamos até à sua porta, em todo o Alentejo.</p>
                                                        <ul class="small text-muted mb-0 ps-3">
                                                            <li>10 cidades suportadas</li>
                                                            <li>Confirmação por OTP (simulada)</li>
                                                            <li>Sujeito a validação de rota</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="alert alert-danger d-none" id="channelError" role="alert"></div>

                                    <div class="alert alert-warning d-none" id="ambDisabledNote">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Os serviços selecionados exigem espaço físico e só estão disponíveis na loja.
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-step-prev>
                                            <i class="bi bi-arrow-left me-1"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" data-step-next>
                                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ============ PASSO 2B: MORADA + PESSOAS ============ -->
                                <div class="form-step" data-step="address">
                                    <h4 class="section-title">
                                        <i class="bi bi-house-heart text-gold me-2"></i>Passo 2B · Morada e Pessoas
                                    </h4>
                                    <p class="text-muted small">Os serviços são agrupados por pessoa para estimar a duração no terreno.</p>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold" for="addressChoice">Morada de atendimento</label>
                                        <select name="addressChoice" id="addressChoice" class="form-select" form-validate-on="change">
                                            <option value="">A carregar moradas...</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div id="newAddressForm" class="border rounded p-3 mb-4 d-none">
                                        <h6 class="fw-bold small text-uppercase mb-3">Nova morada</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select name="cityName" id="cityName" class="form-select" form-validate-on="change">
                                                        <option value="">Selecione...</option>
                                                    </select>
                                                    <label>Cidade*</label>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input name="zipCode" id="zipCode" type="text" class="form-control" placeholder="0000-000" form-validate-on="change">
                                                    <label>Código Postal*</label>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-floating">
                                                    <input name="street" id="street" type="text" class="form-control" placeholder="Rua*" form-validate-on="change">
                                                    <label>Rua / Avenida*</label>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input name="doorNumber" id="doorNumber" type="text" class="form-control" placeholder="Nº*" form-validate-on="change">
                                                    <label>Nº da Porta*</label>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold small text-uppercase mb-3">Pessoas e serviços</h6>
                                    <div id="peopleContainer"></div>

                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1 mb-3" id="addPersonBtn">
                                        <i class="bi bi-person-plus me-1"></i> Adicionar pessoa
                                    </button>

                                    <div class="alert alert-danger d-none" id="peopleError" role="alert"></div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-step-prev>
                                            <i class="bi bi-arrow-left me-1"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" data-step-next>
                                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ============ PASSO 2C: OTP ============ -->
                                <div class="form-step" data-step="otp">
                                    <h4 class="section-title">
                                        <i class="bi bi-shield-lock text-gold me-2"></i>Passo 2C · Verificação por OTP
                                    </h4>
                                    <p class="text-muted small">
                                        Simulação de SMS (restrição académica): o código é gerado e mostrado no ecrã.
                                    </p>

                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <button type="button" class="btn btn-outline-primary" id="otpRequestBtn">
                                            <i class="bi bi-send me-1"></i> Enviar Código
                                        </button>
                                        <span class="text-muted small" id="otpStatus"></span>
                                    </div>

                                    <div class="alert alert-warning d-none" id="otpSimulationBox">
                                        <i class="bi bi-envelope-paper me-1"></i>
                                        <strong>Simulação SMS:</strong> o seu código OTP é
                                        <span class="fs-5 fw-bold text-dark" id="otpCodeDisplay">------</span>
                                    </div>

                                    <div class="form-floating col-md-6 mb-3">
                                        <input name="otpCode" id="otpCode" type="text" inputmode="numeric" maxlength="6"
                                               class="form-control" placeholder="Código OTP" form-validate-on="change">
                                        <label>Código OTP (6 dígitos)*</label>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="alert alert-success d-none" id="otpOkBox">
                                        <i class="bi bi-check-circle me-1"></i> Telemóvel validado com sucesso.
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-step-prev>
                                            <i class="bi bi-arrow-left me-1"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" data-step-next>
                                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ============ PASSO 3: DATA E HORA ============ -->
                                <div class="form-step" data-step="datetime">
                                    <h4 class="section-title">
                                        <i class="bi bi-calendar-check text-gold me-2"></i>Passo 3 · Data e Hora
                                    </h4>

                                    <div class="row g-4">
                                        <div class="col-md-5">
                                            <div class="form-floating mb-2">
                                                <input name="bookingDate" id="bookingDate" type="date" class="form-control"
                                                       min="<?= $minBookingDate ?>" placeholder="Data" form-validate-on="change">
                                                <label>Data pretendida*</label>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                            <div class="alert alert-light border small mb-0">
                                                <i class="bi bi-info-circle me-1"></i> Atendimento de Terça a Sábado, das 09:00 às 19:00.
                                            </div>
                                        </div>

                                        <div class="col-md-7">
                                            <div id="slotsContainer" class="slots-grid" preloader-defer>
                                                <p class="text-muted small mb-0">Escolha uma data para ver os horários disponíveis.</p>
                                            </div>
                                            <input type="hidden" name="time" id="selectedTime" value="">
                                            <div class="alert alert-warning d-none mt-3" id="slotError"></div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between mt-4">
                                        <button type="button" class="btn btn-outline-secondary" data-step-prev>
                                            <i class="bi bi-arrow-left me-1"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" data-step-next>
                                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ============ PASSO 4 (LOJA): PROFISSIONAL ============ -->
                                <div class="form-step" data-step="professional">
                                    <h4 class="section-title">
                                        <i class="bi bi-person-badge text-gold me-2"></i>Passo 4 · Preferência de Profissional
                                    </h4>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="professional" id="professionalAny" value="" checked>
                                        <label class="form-check-label" for="professionalAny">Sem preferência</label>
                                    </div>
                                    <p class="text-muted small mb-4">
                                        A equipa é atribuída automaticamente de acordo com as categorias dos serviços selecionados.
                                    </p>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-step-prev>
                                            <i class="bi bi-arrow-left me-1"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" data-step-next>
                                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ============ PASSO 4 (AMB): POLÍTICA DE SINAL ============ -->
                                <div class="form-step" data-step="policy">
                                    <h4 class="section-title">
                                        <i class="bi bi-cash-coin text-gold me-2"></i>Passo 4 · Política de Sinal
                                    </h4>

                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Na <strong>primeira marcação em ambulatório</strong> o sinal é <strong>dispensado</strong>.
                                        Não são cobrados custos de deslocação.
                                    </div>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        O agendamento fica <strong>pendente</strong> de validação da rota e será
                                        confirmado ou cancelado pela equipa (com aviso ao cliente).
                                    </div>

                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" name="termsAccepted" id="termsAccepted" value="1" form-validate-on="change">
                                        <label class="form-check-label" for="termsAccepted">
                                            Li e aceito as condições do serviço ambulante.*
                                        </label>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-step-prev>
                                            <i class="bi bi-arrow-left me-1"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-primary px-4" data-step-next>
                                            Continuar <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- ============ PASSO 5: RESUMO ============ -->
                                <div class="form-step" data-step="summary">
                                    <h4 class="section-title">
                                        <i class="bi bi-clipboard-check text-gold me-2"></i>Passo 5 · Resumo e Confirmação
                                    </h4>

                                    <div id="summaryBody" class="mb-4"></div>

                                    <div class="alert alert-danger d-none" id="submitError" role="alert"></div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-step-prev>
                                            <i class="bi bi-arrow-left me-1"></i> Voltar
                                        </button>
                                        <button type="button" class="btn btn-success px-4" id="confirmBookingBtn">
                                            <i class="bi bi-check-lg me-1"></i> Confirmar Agendamento
                                        </button>
                                    </div>

                                    <div preloader-overlay class="jq-overlay-process-lg"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>