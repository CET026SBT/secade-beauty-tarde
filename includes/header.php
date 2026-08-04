<?php
require_once __DIR__ . '/config.php';

$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME . ' - Salão de Beleza';
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
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link href="lib/fonts/google-fonts/fonts.css" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="lib/fonts/font-awesome/css/all.min.css" rel="stylesheet">
    <link href="lib/fonts/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="lib/bootstrap/bootstrap.5.0.0.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
