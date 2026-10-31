<?php
$pageTitle = 'Cart';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page user-cart">
    <div class="user-container">
        <p class="user-eyebrow">Your picks</p>
        <h1 class="user-page-title">Shopping cart</h1>
        <p class="user-page-subtitle">Review your selected products before checkout.</p>
        <div data-user-cart>
            <p class="user-form-message" data-cart-status role="status" aria-live="polite">Loading your cart…</p>
            <div class="user-empty-state" data-cart-empty hidden>
                <span class="user-empty-icon" aria-hidden="true">▱</span>
                <h2>Your cart is empty</h2>
                <p>Find something you love and add it to your cart.</p>
                <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Continue browsing</a>
            </div>
            <div class="user-cart-items" data-cart-items></div>
            <section class="user-cart-summary user-data-card" data-cart-summary hidden aria-label="Cart totals">
                <h2>Order summary</h2>
                <dl>
                    <div><dt>Subtotal</dt><dd data-cart-subtotal>0.00</dd></div>
                    <div><dt>Discount</dt><dd data-cart-discount>0.00</dd></div>
                    <div class="user-cart-total"><dt>Total</dt><dd data-cart-total>0.00</dd></div>
                </dl>
            </section>
            <p class="user-cart-checkout-note">Checkout is not connected yet. Your cart changes are saved.</p>
            <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Continue browsing</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
