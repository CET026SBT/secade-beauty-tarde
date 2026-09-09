<?php
$pageHeaderTitle = isset($pageHeaderTitle) ? $pageHeaderTitle : "Page Title";
//$pageHeaderBreadcrumb = isset($pageHeaderBreadcrumb) ? $pageHeaderBreadcrumb : "Page";
?>

<div class="container-fluid bg-light page-header py-5 mb-5">
    <div class="container text-center py-5">
        <h1 class="display-1 animated slideInLeft"><?php echo htmlspecialchars($pageHeaderTitle); ?></h1>
        <!--<nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center animated slideInLeft mb-0">
                <li class="breadcrumb-item"><a class="text-primary" href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item"><a class="text-primary" href="#">Pages</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($pageHeaderBreadcrumb); ?></li>
            </ol>
        </nav>-->
    </div>
</div>
