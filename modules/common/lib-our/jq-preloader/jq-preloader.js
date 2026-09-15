$.fn.preloader = function(...args) {
    const $targets = $(this);
    if ($targets.length === 0) return Promise.resolve();

    let templateSelector = null;
    if (typeof args[0] === 'string') {
        templateSelector = args.shift()
            .split(',')
            .map(sel => {
                sel = sel.trim();
                return sel.startsWith('#jq-preloader-templates') 
                    ? sel 
                    : `#jq-preloader-templates ${sel}`;
            })
            .join(', ');
    }

    let config = {};
    if (args.length > 0 && $.isPlainObject(args[args.length - 1])) {
        config = args.pop();
    }

    let promises = args;
    if (promises.length === 1 && Array.isArray(promises[0])) {
        promises = promises[0];
    }

    const resolvedPromises = promises.map(p => 
        typeof p === 'function' ? Promise.resolve().then(p) : Promise.resolve(p)
    );

    if (resolvedPromises.length === 0) return Promise.resolve();

    const allExecutionPromises = [];

    $targets.each(function() {
        const $target = $(this);

        let $mold = templateSelector ? $(templateSelector) : $target.find('[preloader-overlay], [preloader-skeleton]').first();
        if ($mold.length === 0) {
            allExecutionPromises.push(Promise.all(resolvedPromises));
            return;
        }

        const isSkeleton = $mold.is('[preloader-skeleton]');
        
        const rows = config.rows !== undefined 
            ? config.rows 
            : (parseInt($target.attr('preloader-rows') || $mold.attr('preloader-skeleton'), 10) || 3);

        const shouldDefer = config.defer !== undefined 
            ? config.defer 
            : $target.is('[preloader-defer]') || $mold.is('[preloader-defer]');

        let batch = $target.data('preloader-batch');
        if (!batch) {
            let resolveBatchComplete, rejectBatchComplete;
            const batchCompletePromise = new Promise((resolve, reject) => {
                resolveBatchComplete = resolve;
                rejectBatchComplete = reject;
            });

            batch = {
                promises: [],
                $renderedNodes: $([]),
                completePromise: batchCompletePromise,
                resolveComplete: resolveBatchComplete,
                rejectComplete: rejectBatchComplete
            };
            $target.data('preloader-batch', batch);

            $target.addClass('jq-preloader-container');

            if (shouldDefer) {
                $target.children().not('[preloader-skeleton], [preloader-overlay]').addClass('jq-preloader-existing');
            }

            let $clones = $([]);
            const totalNodes = isSkeleton ? rows : 1;
            for (let i = 0; i < totalNodes; i++) {
                $clones = $clones.add($mold.clone());
            }
            $target.append($clones);

            batch.$renderedNodes = $clones;
        }

        batch.promises.push(...resolvedPromises);

        allExecutionPromises.push(batch.completePromise);

        const groupExecution = Promise.all(resolvedPromises);

        groupExecution.finally(() => {
            batch.promises = batch.promises.filter(p => !resolvedPromises.includes(p));

            if (batch.promises.length === 0) {
                const $nodes = batch.$renderedNodes;
                
                const cleanUp = () => {
                    $nodes.remove();
                    $target.removeClass('jq-preloader-container');
                    $target.find('.jq-preloader-existing').removeClass('jq-preloader-existing');
                    $target.removeData('preloader-batch');
                    batch.resolveComplete();
                };

                const $transitionNodes = $nodes.filter('.wow, [preloader-overlay]');

                if ($transitionNodes.length > 0) {
                    $transitionNodes.addClass('preloader-fade-out');
                    $transitionNodes.first().one('transitionend', function(e) {
                        if (e.target !== this) return;
                        $(this).off('transitionend');
                        cleanUp();
                    });
                } else {
                    cleanUp();
                }
            }
        }).catch(err => {
            batch.rejectComplete(err);
        });
    });

    return Promise.all(allExecutionPromises);
};
