(function () {
    "use strict";

    // Spinner principal da área de gestão
    window._oldPreloader = $.fn.preloader;
    $('body').preloader(new Promise(resolve => {
        $(resolve);
    }));

    // Contador de avisos do sino (RF-81): pedido à API, nunca escrito na página.
    $(function () {
        if (typeof boUtils !== "undefined" && boUtils.loadBellCount) {
            boUtils.loadBellCount();
        }
    });
})();