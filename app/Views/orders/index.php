<?php
$pageTitle = 'Orders';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <p class="user-eyebrow">Your purchase history</p>
        <h1 class="user-page-title">My orders</h1>
        <p class="user-page-subtitle">Track order status and review previous purchases.</p>
        <div class="user-notice-card">
            <h2>Orders page is blocked pending secure backend integration</h2>
            <p><code>GET /orders</code> currently returns JSON from <code>OrderController::index()</code> and is not protected by authentication. It returns all order rows rather than orders belonging to the current user.</p>
            <p>This page will not call or display that endpoint until the backend enforces login and scopes every result to the signed-in user.</p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
