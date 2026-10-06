<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Área Cliente (F9 · §3.5): a página do cliente. A **staff não a usa** — o perfil
// de staff perde a página de perfil (C-05) e é encaminhado para o seu backoffice.
Session::requireLogin();

if (Session::isManager()) {
    header("Location: " . BASE_URL . "/gestao");
    exit;
}

if (Session::isEmployee()) {
    header("Location: " . BASE_URL . "/gestao/agenda");
    exit;
}

register_script("components/customerArea", "main");
register_script("components/bookingEditor", "main");
register_script("components/appointments", "main");

// Cropper (local, §4.6): o recorte quadrado do avatar faz-se no browser e a
// imagem é re-codificada no servidor — o crop do cliente é só pré-visualização.
$pageLibraryStyles  = ["cropper/cropper.min.css"];
$pageLibraryScripts = ["cropper/cropper.min.js"];

$user = Session::user();
$currentPage = "areaCliente";

// A secção inicial vem do pedido (`?seccao=`, o `#` ou o caminho antigo) para que
// `/agendamentos` e `/perfil` continuem a aterrar no sítio certo.
$requestPath = trim((string)parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH), "/");
$section = $_GET["seccao"] ?? "";

if ($section === "") {
    if (str_ends_with($requestPath, "agendamentos")) {
        $section = "agendamentos";
    } elseif (str_ends_with($requestPath, "perfil")) {
        $section = "perfil";
    } else {
        $section = "perfil";
    }
}

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>

<main>
    <div class="container-fluid bg-light page-header py-5 mb-5">
        <div class="container text-center py-4">
            <h1 class="display-4 animated slideInDown mb-3">Área Cliente</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Área Cliente</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container py-5" id="customerArea" data-section="<?= htmlspecialchars($section) ?>" preloader-defer>
        <div class="alert alert-danger d-none" id="customerAreaError" role="alert"></div>
        <div class="alert alert-success d-none" id="customerAreaSuccess" role="alert"></div>

        <ul class="nav nav-pills justify-content-center gap-2 mb-4" id="customerAreaTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#sectionPerfil" type="button" role="tab">
                    <i class="bi bi-person-circle me-1"></i> Perfil
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#sectionAgendamentos" type="button" role="tab">
                    <i class="bi bi-calendar-check me-1"></i> Agendamentos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#sectionLembretes" type="button" role="tab">
                    <i class="bi bi-bell me-1"></i> Lembretes
                    <span class="badge bg-danger ms-1 d-none" id="customerAlertsBadge">0</span>
                </button>
            </li>
        </ul>
    <div class="tab-content">
            <!-- PERFIL -->
            <div class="tab-pane fade show active" id="sectionPerfil" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body text-center">
                                <img id="customerPhoto" class="wh-150 rounded-circle object-fit-cover mb-3 border border-3 border-primary"
                                     src="<?= BASE_URL ?>/modules/common/img/testimonial-1.jpg"
                                     alt="<?= htmlspecialchars($user['name']) ?>">

                                <h4 class="mb-1" id="customerName"><?= htmlspecialchars($user['name']) ?></h4>
                                <p class="text-muted mb-3" id="customerEmail"><?= htmlspecialchars($user['email']) ?></p>
                                <span class="badge bg-primary">Cliente</span>

                                <div class="border-top mt-3 pt-3 text-start">
                                    <label class="form-label small text-muted mb-1" for="customerPhotoFile">Alterar fotografia</label>
                                    <input type="file" id="customerPhotoFile" class="form-control form-control-sm mb-2"
                                           accept="image/jpeg,image/png,image/webp">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="customerPhotoUploadBtn">
                                            <i class="bi bi-upload me-1"></i>Carregar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="customerPhotoRemoveBtn">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <p class="small text-muted mt-2 mb-0">JPG, PNG ou WEBP até 2 MB. O recorte é quadrado e
                                        centrado no rosto antes de guardar.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Dados pessoais</h5>
                                <button type="button" class="btn btn-sm btn-light" id="customerEditBtn">
                                    <i class="bi bi-pencil me-1"></i>Editar
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6"><span class="text-muted small d-block">Nome completo</span>
                                        <span class="fw-bold" id="customerFieldName">-</span></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">E-mail</span>
                                        <span class="fw-bold" id="customerFieldEmail">-</span></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">Telemóvel</span>
                                        <span class="fw-bold" id="customerFieldPhone">-</span></div>
                                    <div class="col-md-6"><span class="text-muted small d-block">NIF</span>
                                        <span class="fw-bold" id="customerFieldNif">-</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>As minhas moradas</h5>
                                <button type="button" class="btn btn-sm btn-light" id="customerAddressNewBtn">
                                    <i class="bi bi-plus me-1"></i>Nova morada
                                </button>
                            </div>
                            <div class="card-body" id="customerAddressList">
                                <p class="text-muted mb-0">A carregar as moradas...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- AGENDAMENTOS -->
            <div class="tab-pane fade" id="sectionAgendamentos" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>As minhas marcações</h5>
                        <a href="<?= BASE_URL ?>/agendar" class="btn btn-sm btn-light">
                            <i class="bi bi-plus me-1"></i> Nova marcação
                        </a>
                    </div>
                    <div class="card-body" id="appointmentsPage">
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-sm btn-outline-primary appt-filter active" data-status="">Todos</button>
                            <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="pendente_alocacao">Pendentes</button>
                            <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="confirmado">Confirmados</button>
                            <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="cancelado">Cancelados</button>
                        </div>
                            <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="pendente_alocacao">Pendentes</button>
                            <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="confirmado">Confirmados</button>
                            <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="cancelado">Cancelados</button>
                        </div>

                        <div class="alert alert-danger d-none" id="appointmentsError" role="alert"></div>
                        <div class="alert alert-success d-none" id="appointmentsSuccess" role="alert"></div>
                        <div id="appointmentsList"></div>
                    </div>
                </div>
            </div>

            <!-- LEMBRETES -->
            <div class="tab-pane fade" id="sectionLembretes" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Os meus lembretes</h5>
                        <button type="button" class="btn btn-sm btn-light" id="customerAlertsReadBtn">
                            <i class="bi bi-check2-all me-1"></i>Marcar como lidos
                        </button>
                    </div>
                    <div class="card-body" id="customerAlertsList">
                        <p class="text-muted mb-0">A carregar os lembretes...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal: editar dados pessoais -->
<div class="modal fade" id="customerProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar dados pessoais</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="customerProfileForm">
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1" for="customerInputName">Nome completo</label>
                        <input type="text" name="name" id="customerInputName" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1" for="customerInputPhone">Telemóvel</label>
                        <input type="text" name="phone" id="customerInputPhone" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small text-muted mb-1" for="customerInputNif">NIF</label>
                        <input type="text" name="nif" id="customerInputNif" class="form-control">
                        <div class="invalid-feedback"></div>
                    </div>
                    <p class="small text-muted mt-3 mb-0">
                        O e-mail e a palavra-passe não se alteram aqui — são a sua identidade de acesso.
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="customerProfileSaveBtn">
                    <i class="bi bi-check2 me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: nova morada -->
<div class="modal fade" id="customerAddressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova morada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="customerAddressForm">
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1" for="addressLabelInput">Identificação</label>
                        <input type="text" name="label" id="addressLabelInput" class="form-control" placeholder="Casa, trabalho, ..." required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1" for="addressStreetInput">Morada</label>
                        <input type="text" name="address" id="addressStreetInput" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label small text-muted mb-1" for="addressCityInput">Cidade</label>
                            <select name="cityId" id="addressCityInput" class="form-select" required></select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted mb-1" for="addressZipInput">Código postal</label>
                            <input type="text" name="zipCode" id="addressZipInput" class="form-control" placeholder="0000-000" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="isMain" id="addressMainInput" value="1">
                        <label class="form-check-label small" for="addressMainInput">Usar como morada principal</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="customerAddressSaveBtn">
                    <i class="bi bi-check2 me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: recorte da fotografia (crop quadrado — §4.6) -->
<div class="modal fade" id="customerCropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recortar fotografia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center bg-light rounded p-2">
                    <img id="customerCropImage" src="" alt="" style="max-width: 100%; display: block;">
                </div>
                <p class="small text-muted mt-3 mb-0">
                    Ajuste o zoom e a posição para que o rosto fique no centro do quadrado.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="customerCropSaveBtn">
                    <i class="bi bi-check2 me-1"></i>Guardar fotografia
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: alterar agendamento (F9b · C-06 · §4.5) -->
<div class="modal fade" id="bookingEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alterar agendamento <span id="bookingEditRef"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="bookingEditError" role="alert"></div>

                <p class="small text-muted" id="bookingEditChannel"></p>

                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1" for="bookingEditDate">Data</label>
                        <input type="date" id="bookingEditDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small text-muted mb-1">Horário</label>
                        <div id="bookingEditSlots" class="d-flex flex-wrap gap-2"></div>
                        <input type="hidden" id="bookingEditTime">
                    </div>
                </div>

                <div id="bookingEditAddressBlock" class="mb-3 d-none">
                    <label class="form-label small text-muted mb-1" for="bookingEditAddress">Morada da prestação</label>
                    <select id="bookingEditAddress" class="form-select form-select-sm"></select>
                </div>

                <div id="bookingEditStoreServices" class="mb-3 d-none">
                    <label class="form-label small text-muted mb-1">Serviços</label>
                    <div id="bookingEditStoreServicesList" class="row g-2"></div>
                </div>

                <div id="bookingEditPeopleBlock" class="mb-3 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small text-muted mb-0">Pessoas e serviços</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="bookingEditAddPerson">
                            <i class="bi bi-person-plus me-1"></i>Adicionar pessoa
                        </button>
                    </div>
                    <div id="bookingEditPeople"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="bookingEditSaveBtn">
                    <i class="bi bi-check2 me-1"></i>Guardar alterações
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php"; ?>