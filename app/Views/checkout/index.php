
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

<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
