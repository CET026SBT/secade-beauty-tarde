<?php
require_once __DIR__ . '/config.php';

if (!isset($showMainFooter) || $showMainFooter === true) {
    include dirname(__DIR__, 1) . '/components/main-footer.php';
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

    <!-- JavaScript Libraries -->
    <script src="lib/jquery/jquery.3.6.1.min.js"></script>
    <script src="lib/bootstrap/bootstrap.5.0.0.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Main Javascript -->
    <script src="js/main.js"></script>

    <!-- Page & Components Javascript -->
    <?php 
    global $requiredScripts;
    if (!empty($requiredScripts)) {
        foreach ($requiredScripts as $scriptPath) {
            echo '<script src="' . $scriptPath . '"></script>' . PHP_EOL;
        }
    }
    ?>
</body>

</html>
