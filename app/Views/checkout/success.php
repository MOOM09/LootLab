<?php
$pageTitle = 'Order confirmation';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <div class="user-empty-state">
            <span class="user-empty-icon" aria-hidden="true">✓</span>
            <p class="user-eyebrow">Order confirmation</p>
            <h1 class="user-page-title">No order confirmation yet</h1>
            <p>This page does not claim an order was created. The project has no checkout flow or success route to confirm one.</p>
            <a class="user-button" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Continue shopping</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
