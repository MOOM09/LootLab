<?php
$pageTitle = 'Order details';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <p class="user-eyebrow">Purchase details</p>
        <h1 class="user-page-title">Order details</h1>
        <div class="user-notice-card">
            <h2>Order details are not safely available yet</h2>
            <p><code>GET /orders/show?id=…</code> returns JSON and checks only that a user is logged in. Its model looks up an order by ID without verifying that the order belongs to that user, and the response does not include order items.</p>
            <p>This view does not request or expose order data. Backend ownership checks and an order-items response are required first.</p>
            <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Back to products</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
