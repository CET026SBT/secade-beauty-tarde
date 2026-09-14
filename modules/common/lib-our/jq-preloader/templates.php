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
<div id="jq-preloader-templates" style="display: none !important;">

    <!-- 1. Spinner Overlay (Ecrã inteiro ou sobre um container) -->
    <div preloader-overlay class="jq-overlay-process bg-white bg-opacity-75">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">A processar...</span>
        </div>
    </div>

    <!-- 2. Skeleton Box (Para blocos genéricos) -->
    <div preloader-skeleton="1" class="jq-skeleton-item">
        <div class="jq-skeleton-line jq-skeleton-title"></div>
        <div class="jq-skeleton-line jq-skeleton-text"></div>
        <div class="jq-skeleton-line jq-skeleton-text-short"></div>
    </div>
    
</div>
