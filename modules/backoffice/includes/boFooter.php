    <!-- Preloader principal da área de gestão -->
    <div preloader-overlay preloader-fade class="jq-preloader-instance bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" role="status">
            <span class="visually-hidden">A carregar...</span>
        </div>
    </div>

    <?php include ROOT_PATH . "/modules/common/lib-our/jq-preloader/templates.php"; ?>

    <!-- Third-party Libraries Javascript -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/jquery/jquery.3.6.1.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/bootstrap/bootstrap.5.0.0.min.js"></script>

    <!-- Gráficos do backoffice (E-3: Chart.js v2.9.4 servido localmente, nunca por CDN).
         Carregado SÓ na área de gestão — o site público não precisa dele. -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/chartjs/Chart.bundle.min.js"></script>

    <!-- Sweetalert2 local (Q-05): diálogos de confirmação/alerta da aplicação -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/sweetalert/sweetalert.js"></script>

    <!-- Our Libraries Javascript -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib-our/jq-preloader/jq-preloader.js"></script>

    <!-- APIs e Utils -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/api/apiClient.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/api/api.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/general.utils.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/form.utils.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/bookingStatus.utils.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/vat.utils.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/siteStats.utils.js"></script>

    <!-- Backoffice base -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/backoffice/js/bo.utils.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/backoffice/js/bo.js"></script>

    <!-- Page scripts -->
    <?php
    global $requiredScripts;
    if (!empty($requiredScripts)) {
        foreach ($requiredScripts as $scriptPath) {
            echo '<script type="text/javascript" src="' . BASE_URL . '/' . $scriptPath . '"></script>' . PHP_EOL;
        }
    }
    ?>
</body>

</html>