const navbar = (() => {
    let $navbarCollapse, bsCollapseInstance;
    
    function init() {
        $navbarCollapse = $('#navbarCollapse');
        if (!$navbarCollapse.length) return;

        bsCollapseInstance = 
            bootstrap.Collapse.getInstance($navbarCollapse[0]) || 
            new bootstrap.Collapse($navbarCollapse[0], { toggle: false });
    }

    function bindEvents() {
        $(window).on('resize', () => {
            if (!bsCollapseInstance) return;

            const windowWidth = window.innerWidth;
            if (windowWidth >= 992 && windowWidth < 1200) return;

            bsCollapseInstance.hide();
        });
    }

    $(() => {
        init();
        bindEvents();
    })

    return {
        show: () => bsCollapseInstance?.show?.(),
        hide: () => bsCollapseInstance?.hide?.(),
        toggle: (flgShow) => {
            if (!bsCollapseInstance) return;
            if (flgShow !== undefined) {
                bsCollapseInstance[flgShow ? 'show' : 'hide']();
            } else {
                bsCollapseInstance.toggle();
            }
        }
    };
})();
