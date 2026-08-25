(function() {
    "use strict";

    // Spinner
    function spinner() {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    // Initiate the wowjs
    new WOW().init();
    
    // Back to top button
    function updateBackToTopButton() {
        var scrollTop = $(window).scrollTop();
        var opacity = Math.max(Math.min((scrollTop - 80) / 150, 1), 0);
        $('.back-to-top').css('opacity', opacity);
    }
    updateBackToTopButton();

    $(window).scroll(function () {
        updateBackToTopButton();
    });

    $('.back-to-top').click(function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
    });
})();
