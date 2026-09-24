(() => {
    const parseTemplateSelector = selector => selector
        .split(',')
        .map(sel => {
            sel = sel.trim();
            return sel.startsWith('#jq-preloader-templates') ? sel : `#jq-preloader-templates ${sel}`;
        })
        .join(', ');

    const parseArguments = args => {
        let templateSelector = null;
        if (typeof args[0] === 'string') {
            templateSelector = parseTemplateSelector(args.shift());
        }

        let config = {};
        if ($.isPlainObject(args.at(-1))) {
            config = args.pop();
        }

        let promises = args.length === 1 && Array.isArray(args[0]) ? args[0] : args;
        if (promises.length === 0) promises = [Promise.resolve()];

        const resolvedPromises = promises.map(p => 
            typeof p === 'function' ? Promise.resolve().then(p) : Promise.resolve(p)
        );

        return { templateSelector, config, resolvedPromises };
    };

    const calculateFadeDelay = fadeDuration => {
        const durationStr = String(fadeDuration);
        return parseFloat(durationStr) * (durationStr.endsWith('ms') ? 1 : 1000);
    };

    const resolveMold = ($target, templateSelector) => {
        return templateSelector 
            ? $(templateSelector) 
            : $target.children('[preloader-overlay], [preloader-skeleton]').first();
    };

    const resolveElementOptions = ($target, $mold, config, settings) => {
        const wasInstantiated = $mold.hasClass('jq-preloader-instance');

        const hasAttrDefer = $target.is('[preloader-defer]') || $mold.is('[preloader-defer]');
        const defer = config.defer !== undefined 
            ? config.defer 
            : (hasAttrDefer ? true : settings.defer);

        const rows = config.rows !== undefined 
            ? config.rows 
            : (parseInt($target.attr('preloader-rows') || $mold.attr('preloader-skeleton'), 10) || settings.rows);

        const rawFade = config.fade !== undefined 
            ? config.fade 
            : ($target.attr('preloader-fade') ?? $mold.attr('preloader-fade'));

        const fadeDuration = rawFade === true || rawFade === '' 
            ? settings.fadeDuration 
            : (rawFade !== undefined ? rawFade : false);

        return { wasInstantiated, defer, rows, fadeDuration };
    };

    const setupDefer = ($target, defer) => {
        if (!defer) return;
        $target.addClass('jq-preloader-defer');
        $target.children(':not([preloader-overlay], [preloader-skeleton])').addClass('jq-preloader-existing');
    };

    const setupFade = ($target, fadeDuration, batch) => {
        if (fadeDuration) {
            $target.addClass('jq-preloader-fade').css('--jq-preloader-fade-duration', fadeDuration);
        }
        batch.fadeDuration = fadeDuration;
    };

    const renderTemplates = ($target, $mold, wasInstantiated, rows, batch) => {
        const isSkeleton = $mold.is('[preloader-skeleton]');
        let $clones = $([]);
        const totalNodes = isSkeleton ? rows : 1;
        
        for (let i = 0; i < totalNodes; i++) {
            $clones = $clones.add($mold.clone());
        }

        $target.append($clones);

        if (wasInstantiated) {
            $mold.removeClass('jq-preloader-instance');
            $clones.addClass('jq-preloader-was-instantiated');
        }
        
        $clones.addClass('jq-preloader-instance');
        batch.$renderedNodes = $clones;
    };

    const initializeBatch = ($target, $mold, options) => {
        let resolveBatch, rejectBatch;
        const completionPromise = new Promise((resolve, reject) => { 
            resolveBatch = resolve; 
            rejectBatch = reject; 
        });

        const batch = {
            pendingCount: 0,
            completionPromise,
            resolveBatch,
            rejectBatch,
            $renderedNodes: $([]),
            fadeDuration: false
        };

        $target.data('preloader-batch', batch);
        $target.addClass('jq-preloader-container').attr('aria-busy', 'true');

        setupDefer($target, options.defer);
        setupFade($target, options.fadeDuration, batch);
        renderTemplates($target, $mold, options.wasInstantiated, options.rows, batch);

        return batch;
    };

    const executeCleanUp = ($target, batch) => {
        if ($target.hasClass('jq-preloader-fade')) {
            $target.addClass('jq-preloader-fading-out');
            const delay = calculateFadeDelay(batch.fadeDuration || $.fn.preloader.defaults.fadeDuration);
            setTimeout(() => finishCleanUp($target, batch), delay);
        } else {
            finishCleanUp($target, batch);
        }
    };

    const finishCleanUp = ($target, batch) => {
        batch.$renderedNodes.remove();

        if ($target.hasClass('jq-preloader-defer')) {
            $target.children(':not(.jq-preloader-existing, [preloader-overlay], [preloader-skeleton])').detach().clone(true, true).appendTo($target);
        }

        $target.children('.jq-preloader-existing').removeClass('jq-preloader-existing');
        $target.removeClass('jq-preloader-container jq-preloader-defer jq-preloader-fade jq-preloader-fading-out');
        $target.removeAttr('aria-busy').removeData('preloader-batch');
        $target.css('--jq-preloader-fade-duration', '');

        if (batch.hasError) {
            batch.rejectBatch(new Error("Preloader batch failed due to one or more rejected promises."));
        } else {
            batch.resolveBatch();
        }
    };

    $.fn.preloader = function(...args) {
        const $targets = $(this);
        if ($targets.length === 0) return Promise.resolve();

        const { templateSelector, config, resolvedPromises } = parseArguments(args);
        const settings = $.extend({}, $.fn.preloader.defaults, config);
        const allExecutionPromises = [];

        $targets.each(function() {
            const $target = $(this);
            const $mold = resolveMold($target, templateSelector);

            if ($mold.length === 0) {
                allExecutionPromises.push(Promise.all(resolvedPromises));
                return;
            }

            const options = resolveElementOptions($target, $mold, config, settings);
            let batch = $target.data('preloader-batch') || initializeBatch($target, $mold, options);

            batch.pendingCount += resolvedPromises.length;

            Promise.all(resolvedPromises)
                .catch(() => {
                    batch.hasError = true;
                })
                .finally(() => {
                    batch.pendingCount -= resolvedPromises.length;
                    if (batch.pendingCount === 0) executeCleanUp($target, batch);
                });

            allExecutionPromises.push(batch.completionPromise);
        });

        return Promise.all(allExecutionPromises);
    };

    $.fn.preloader.defaults = {
        fadeDuration: '0.4s',
        rows: 3,
        defer: false 
    };
})();
