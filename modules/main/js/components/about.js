const about = (() => {
    "use strict";

    function startCounters() {
        // Facts counter
        $('[data-toggle="counter-up"]').counterUp({
            delay: 10,
            time: 2000
        });
    }

    /**
     * Os contadores animados precisam do valor final antes de arrancar (o `counterUp`
     * lê o texto existente), por isso o preenchimento é feito aqui e não pelo
     * preenchimento automático do util. O HTML já traz o valor documental.
     */
    async function fillCounters() {
        const stats = await siteStats.load();

        $('#aboutStatTeam').text(stats.team);
        $('#aboutStatServices').text(stats.services);
    }

    $(() => {
        fillCounters().finally(startCounters);
    });

    return { fillCounters };
})();