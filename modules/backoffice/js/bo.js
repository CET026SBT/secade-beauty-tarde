(function () {
    "use strict";

    // Spinner principal da área de gestão
    $('body').preloader(new Promise(resolve => {
        $(resolve);
    }));
})();