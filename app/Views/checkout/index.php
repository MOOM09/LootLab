<?php
$pageTitle = 'Checkout';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <p class="user-eyebrow">Almost yours</p>
        <h1 class="user-page-title">Checkout</h1>
        <p class="user-page-subtitle">Review your order and delivery information.</p>
        <div class="user-notice-card">
            <h2>Checkout is not available yet</h2>
            <p>There is no checkout route or server-side order-from-cart flow in the current project. No payment, address submission, or order creation has been performed.</p>
            <p>Checkout needs a backend action that reads the signed-in user’s cart, validates current product stock and prices, calculates totals on the server, and confirms the order before showing success.</p>
            <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Return to products</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
