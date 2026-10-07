<?php
$pageHeaderTitle = isset($pageHeaderTitle) ? $pageHeaderTitle : "Page Title";
?>

<div class="container-fluid bg-light page-header py-5">
    <div class="container text-center py-5">
        <h1 class="display-1 animated slideInLeft"><?php echo htmlspecialchars($pageHeaderTitle); ?></h1>
    </div>
</div>
