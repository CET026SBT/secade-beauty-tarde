<?php
/**
 * Central templates for the jQuery Preloader
 * Must be included in the main layout (e.g., footer or global hidden area)
 * 
 * How to specify and use:
 * 
 * 1. Default Spinner Overlay:
 *    - Simply call: $('.containers').preloader(promiseA, promiseB, ...)
 *    - The plugin automatically looks for the first element with [preloader-overlay].
 * 
 * 2. Generic Skeleton Loader:
 *    - Simply call: $('.containers').preloader(promiseA, ...)
 *    - The plugin looks for the first element with [preloader-skeleton] and duplicates it 3 times by default.
 * 
 * 3. Specify a Custom Template/Selector, e.g.:
 *    - Pass the CSS selector as the first argument: $('.containers').preloader('.jq-preloader-overlay', ...promises)
 * 
 * 4. Using Configuration Options:
 *    - Pass an options object as the last argument: $('.containers').preloader(...promises, { rows: 5, defer: true, fade: '0.4s' })
 *    - Supported options:
 *      - rows: Number of times to duplicate the skeleton (can also be defined via the [preloader-rows] attribute on the container or template).
 *      - defer: Boolean to hide prematurely injected elements until the preloader finishes (can also be enabled via the [preloader-defer] attribute on the container or template).
 *      - fade: Controls the enter/exit animation (opacity transition). 
 *        - Can be passed as a duration string (e.g., `'0.3s'`), boolean (`true` applies the default `0.4s` duration, `false` disables it), 
 *          or via the [preloader-fade] attribute (with a value like `preloader-fade="0.3s"` or as a boolean flag `preloader-fade`).
 */
?>
<div id="jq-preloader-templates">

    <!-- Spinner Overlay (Used in forms while loading or submiting) -->
    <div preloader-overlay class="jq-overlay-process bg-white bg-opacity-75">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">A processar...</span>
        </div>
    </div>

    <!-- Skeleton for Service Category cards -->
    <div preloader-skeleton preloader-fade class="jq-skeleton-service-category-card col-md-6 col-lg-4">
        <div class="service-item h-100 p-4 bg-white border-bottom border-end text-center placeholder-glow">
            <div class="d-inline-flex align-items-center justify-content-center mb-4 placeholder wh-60 bg-primary bg-opacity-25">
                <i class="bi bi-brush text-white fs-3"></i>
            </div>
            <h3 class="mb-3">
                <span class="placeholder placeholder-sm col-6 bg-secondary bg-opacity-25"></span>
            </h3>
            <p class="mb-4">
                <span class="placeholder placeholder-sm col-9 bg-secondary bg-opacity-10"></span>
            </p>
            <div class="d-inline-block col-6">
                <span class="placeholder placeholder-sm col-12 py-4 border border-primary bg-transparent"></span>
            </div>
        </div>
    </div>
        
</div>
