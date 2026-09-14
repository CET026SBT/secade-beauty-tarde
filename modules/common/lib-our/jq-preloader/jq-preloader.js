$.fn.preloader = function(...args) {
    const $targets = $(this);
    if ($targets.length === 0) return Promise.resolve();

    let templateSelector = null;
    if (typeof args[0] === 'string') {
        templateSelector = args.shift();
    }

    let overrideRows = null;
    if (args.length > 0 && Number.isInteger(args[args.length - 1])) {
        overrideRows = args.pop();
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
        const rows = overrideRows !== null 
            ? overrideRows 
            : (isSkeleton ? parseInt($mold.attr('preloader-skeleton'), 10) || 3 : 1);

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

            if (!isSkeleton) {
                $target.addClass('jq-preloader-container');
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
                batch.$renderedNodes.remove();
                $target.removeClass('preloader-container');
                $target.removeData('preloader-batch');
                batch.resolveComplete();
            }
        }).catch(err => {
            batch.rejectComplete(err);
        });
    });

    return Promise.all(allExecutionPromises);
};
