<?php
include ROOT_PATH . "/modules/common/lib-our/jq-preloader/templates.php";

if (!isset($showMainFooter) || $showMainFooter === true) {
    include ROOT_PATH . "/modules/main/components/mainFooter.php";
}
?>
    <!-- Copyright -->
    <div class="container-fluid bg-dark text-white border-top border-secondary py-4 wow fadeIn" data-wow-delay="0.1s">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="border-bottom" href="#"><?php echo htmlspecialchars(SITE_NAME); ?></a>, Todos os Direitos Reservados.
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- Third-party Libraries Javascript -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/jquery/jquery.3.6.1.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/bootstrap/bootstrap.5.0.0.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/wow/wow.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/easing/easing.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/waypoints/waypoints.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/counterup/counterup.min.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Our Libraries Javascript -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/lib-our/jq-preloader/jq-preloader.js"></script>

    <!-- APIs -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/api/apiClient.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/api/api.js"></script>

    <!-- Essencial Utils -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/general.utils.js"></script>
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/common/js/utils/form.utils.js"></script>

    <!-- Main Javascript -->
    <script type="text/javascript" src="<?= BASE_URL ?>/modules/main/js/main.js"></script>

    <!-- Page & Components Javascript -->
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
