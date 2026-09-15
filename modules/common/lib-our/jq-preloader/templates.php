<?php
/**
 * Templates centrais para o jQuery Preloader
 * Devem ser incluídos no layout principal (ex: footer ou área global oculta)
 * 
 * Como especificar e utilizar:
 * 
 * 1. Spinner Overlay Padrão:
 *    - Basta chamar: $('.containers').preloader(promiseA, promiseB, ...)
 *    - O plugin procura automaticamente pelo primeiro elemento com [preloader-overlay].
 * 
 * 2. Skeleton Loader Genérico:
 *    - Basta chamar: $('.containers').preloader(promiseA, ...)
 *    - O plugin procura pelo primeiro elemento com [preloader-skeleton] e duplica-o 3 vezes por defeito.
 * 
 * 3. Especificar número de linhas do Skeleton (Ex: 5 linhas):
 *    - Passar o número de linhas como último argumento: $('.containers').preloader(...promises, 5)
 * 
 * 4. Especificar um Template/Seletor Personalizado, ex:
 *    - Passar o seletor CSS como primeiro argumento: $('.containers').preloader('.jq-preloader-overlay', ...promises)
 *    - Com número de linhas personalizado: $('.containers').preloader('.jq-skeleton-item', ...promises, 4) // 4 linhas
 */
?>
<div id="jq-preloader-templates">

    <!-- Spinner Overlay em Fullscreen -->
    <div preloader-overlay class="jq-overlay-main bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" role="status">
            <span class="visually-hidden">A carregar...</span>
        </div>
    </div>

    <!-- Spinner Overlay (Ecrã inteiro ou sobre um container) -->
    <div preloader-overlay class="jq-overlay-process bg-white bg-opacity-75">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">A processar...</span>
        </div>
    </div>

    <!-- Skeleton for Service Category cards -->
    <div preloader-skeleton="3" class="jq-skeleton-service-category-card col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.1s">
        <div class="service-item h-100 p-4 p-lg-5 bg-white border-bottom border-end shadow-sm rounded text-center placeholder-glow">
            <div class="d-inline-flex align-items-center justify-content-center mb-4 placeholder rounded wh-60 bg-primary bg-opacity-25">
                <i class="bi bi-brush text-white fs-3"></i>
            </div>
            <h3 class="mb-3 fw-light">
                <span class="placeholder placeholder-sm col-6 bg-secondary bg-opacity-25"></span>
            </h3>
            <p class="mb-4">
                <span class="placeholder placeholder-sm col-9 bg-secondary bg-opacity-10 d-block mx-auto mb-2"></span>
            </p>
            <div class="d-inline-block col-8">
                <span class="placeholder placeholder-sm col-12 py-2 rounded border border-primary bg-transparent d-block"></span>
            </div>
        </div>
    </div>
        
</div>
