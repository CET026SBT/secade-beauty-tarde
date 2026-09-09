<?php
$pageTitle = isset($pageTitle) ? $pageTitle . " - " . SITE_NAME : SITE_NAME . " - Salão de Beleza";
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="<?= BASE_URL ?>/modules/common/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link href="<?= BASE_URL ?>/modules/common/lib/fonts/google-fonts/fonts.css" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="<?= BASE_URL ?>/modules/common/lib/fonts/font-awesome/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/modules/common/lib/fonts/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="<?= BASE_URL ?>/modules/common/lib/animate/animate.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/modules/common/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="<?= BASE_URL ?>/modules/common/lib/bootstrap/bootstrap.5.0.0.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link href="<?= BASE_URL ?>/modules/main/css/style.css" rel="stylesheet">

    <script>
        window.BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>

<body>
