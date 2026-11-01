
<?php
$old = $old ?? [];
$error = $error ?? null;
$notice = $notice ?? null;
$appUrl = rtrim($appUrl ?? '', '/');
$csrfToken = $csrfToken ?? '';
$selectedMethod = $old['payment_method'] ?? 'cod';

$pageTitle = 'Checkout';
require __DIR__ . '/../layouts/user-header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="<?= htmlspecialchars($appUrl . '/css/checkout-map.css?v=20261101-1', ENT_QUOTES, 'UTF-8') ?>">
<section class="user-page">
    <div class="user-container">

        <p class="user-eyebrow">Almost yours</p>
        <h1 class="user-page-title">Checkout</h1>
        <p class="user-page-subtitle">
            Review your order and delivery information.
        </p>

        <div class="user-form-card">

            <?php if ($error): ?>
                <p class="user-form-message" role="alert">
                    <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <?php if ($notice): ?>
                <p class="user-form-message" role="status">
                    <?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form
                class="user-form"
                action="<?= htmlspecialchars($appUrl . '/checkout', ENT_QUOTES, 'UTF-8') ?>"
                method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="user-field">
                    <label for="shipping_address">
                        Shipping Address
                    </label>

                    <textarea
                        class="user-input"
                        id="shipping_address"
                        name="shipping_address"
                        required><?= htmlspecialchars((string) ($old['shipping_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="checkout-map-section" data-checkout-map>
                    <div class="checkout-map-header">
                        <div>
                            <h2>Choose your delivery meeting point</h2>
                            <p>Search your address in Jordan, then drag the pin to the exact spot where the courier should meet you.</p>
                        </div>
                        <span class="checkout-map-country">🇯🇴 Jordan only</span>
                    </div>
                    <div class="checkout-map-tools">
                        <button class="user-button-secondary" type="button" id="checkout-find-address">Search address on map</button>
                        <button class="user-button-secondary" type="button" id="checkout-my-location">Use my location</button>
                    </div>
                    <div class="checkout-map-results" id="checkout-address-results" hidden></div>
                    <div class="checkout-map-canvas" id="checkout-jordan-map" role="application" aria-label="Map of Jordan, click to set delivery meeting point"></div>
                    <p class="checkout-map-hint">Click the map or drag the marker to pin your meeting point. The pin must be inside Jordan.</p>
                    <p class="checkout-map-status" id="checkout-map-status" role="status">No meeting point selected yet.</p>
                    <div class="checkout-selected-location">
                        <strong>📍 Delivery meeting point:</strong>
                        <span id="checkout-meeting-point-preview">Not selected</span>
                    </div>
                    <input type="hidden" id="delivery_lat" name="delivery_lat" value="<?= htmlspecialchars((string) ($old['delivery_lat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="delivery_lng" name="delivery_lng" value="<?= htmlspecialchars((string) ($old['delivery_lng'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="meeting_point_label" name="meeting_point_label" value="<?= htmlspecialchars((string) ($old['meeting_point_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="user-field">
                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        class="user-input"
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars((string) ($old['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        required>
                </div>

                <p class="checkout-otp-notice">🔐 Before placing the order or paying with Stripe, we'll send a 6-digit SMS verification code to your phone.</p>

                <fieldset class="user-field">
                    <legend>Payment Method</legend>

                    <label>
                        <input
                            type="radio"
                            name="payment_method"
                            value="cod"
                            <?= $selectedMethod === 'cod' ? 'checked' : '' ?>>
                        Cash on Delivery
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="payment_method"
                            value="stripe"
                            <?= $selectedMethod === 'stripe' ? 'checked' : '' ?>>
                        Credit / Debit Card (Stripe)
                    </label>
                </fieldset>

                <p class="user-page-subtitle">
                    Stripe payments use the sandbox environment.
                    Conversion rate:
                    1 JOD =
                    <?= htmlspecialchars((string) ($_ENV['STRIPE_JOD_TO_USD_RATE'] ?? '1.41'), ENT_QUOTES, 'UTF-8') ?>
                    USD.
                    Order totals are stored in JOD.
                </p>

                <div class="user-form-actions">

                    <button
                        class="user-button"
                        type="submit"
                        formaction="<?= htmlspecialchars($appUrl . '/checkout', ENT_QUOTES, 'UTF-8') ?>">
                        Place Order (Cash on Delivery)
                    </button>

                    <button
                        class="user-button-secondary"
                        type="submit"
                        formaction="<?= htmlspecialchars($appUrl . '/checkout/stripe', ENT_QUOTES, 'UTF-8') ?>">
                        Pay with Stripe
                    </button>

                </div>

            </form>

        </div>

    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script src="<?= htmlspecialchars($appUrl . '/js/checkout-map.js?v=20261101-1', ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
