<?php
register_script("components/services", "main");
?>
<div class="container-fluid service py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.1s">
            <h1 class="font-dancing-script text-primary">Os nossos serviços</h1>
            <h1 class="mb-5">Catálogo completo</h1>
        </div>

        <div class="row g-3 align-items-end mb-4 wow fadeIn" data-wow-delay="0.15s">
            <div class="col-md-5">
                <label class="form-label" for="serviceSearch">Pesquisar</label>
                <input type="search" id="serviceSearch" class="form-control" placeholder="Nome ou descrição do serviço...">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="serviceMaxDuration">Duração máxima</label>
                <select id="serviceMaxDuration" class="form-select">
                    <option value="">Qualquer</option>
                    <option value="60">Até 1 hora</option>
                    <option value="120">Até 2 horas</option>
                    <option value="240">Até 4 horas</option>
                    <option value="300">Até 5 horas</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="serviceMaxPrice">Preço máximo: <span id="serviceMaxPriceLabel">50,00 €</span></label>
                <input type="range" id="serviceMaxPrice" class="form-range" min="5" max="50" step="1" value="50">
            </div>
        </div>

        <div class="filters-container text-center mb-4 wow fadeIn" data-wow-delay="0.2s"></div>

        <div class="services-container row g-4 wow fadeIn" data-wow-delay="0.25s" preloader-rows="6" preloader-defer></div>

        <p class="text-center text-muted mt-4 d-none" id="servicesEmpty">
            Nenhum serviço corresponde aos filtros selecionados.
        </p>
    </div>
</div>

<!-- Modal de detalhes do serviço -->
<div class="modal fade" id="serviceDetailsModal" tabindex="-1" aria-labelledby="serviceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalTitle">Detalhes do serviço</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="serviceModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="#" id="serviceModalBook" class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-1"></i> Agendar
                </a>
            </div>
        </div>
    </div>
</div>
