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

    <!-- Our Libraries Javascript -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib-our/jq-preloader/jq-preloader.js"></script>

    <!-- APIs e Utils -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/api/apiClient.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/api/api.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/general.utils.js"></script>

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