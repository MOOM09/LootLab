
<?php
$old = $old ?? [];
$error = $error ?? null;
$notice = $notice ?? null;
$appUrl = rtrim($appUrl ?? '', '/');
$csrfToken = $csrfToken ?? '';
$selectedMethod = $old['payment_method'] ?? 'cod';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - LootLab</title>
</head>
<body>

    <h1>Checkout</h1>

    <?php if ($error): ?>
        <p style="color: red;" role="alert">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($notice): ?>
        <p style="color: #8a6d3b;" role="status">
            <?= htmlspecialchars($notice) ?>
        </p>
    <?php endif; ?>

    <form action="<?= htmlspecialchars($appUrl . '/checkout', ENT_QUOTES, 'UTF-8') ?>" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
        >

        <div>
            <label for="shipping_address">Shipping Address</label>
            <textarea
                id="shipping_address"
                name="shipping_address"
                required
            ><?= htmlspecialchars($old['shipping_address'] ?? '') ?></textarea>
        </div>

        <div>
            <label for="phone">Phone Number</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                required
            >
        </div>

        <fieldset>
            <legend>Payment Method</legend>

            <label>
                <input
                    type="radio"
                    name="payment_method"
                    value="cod"
                    <?= $selectedMethod === 'cod' ? 'checked' : '' ?>
                >
                Cash on Delivery - pay when your order arrives.
            </label>

            <br>

            <label>
                <input
                    type="radio"
                    name="payment_method"
                    value="stripe"
                    <?= $selectedMethod === 'stripe' ? 'checked' : '' ?>
                >
                Credit / Debit Card (Stripe) - sandbox test payments.
            </label>
        </fieldset>

        <p>
            <small>
                Card payments are converted from JOD to USD for the Stripe sandbox
                (1 JOD = <?= htmlspecialchars((string) ($_ENV['STRIPE_JOD_TO_USD_RATE'] ?? '1.41'), ENT_QUOTES, 'UTF-8') ?> USD).
                Your order total is stored in JOD.
            </small>
        </p>

        <?php
        //? Each button targets its own registered route; both keep the form fields and CSRF token.
        //? "Pay with Stripe" posts to POST /checkout/stripe, "Place Order" posts to POST /checkout.
        ?>
        <button type="submit" formaction="<?= htmlspecialchars($appUrl . '/checkout', ENT_QUOTES, 'UTF-8') ?>">
            Place Order (Cash on Delivery)
        </button>

        <button type="submit" formaction="<?= htmlspecialchars($appUrl . '/checkout/stripe', ENT_QUOTES, 'UTF-8') ?>">
            Pay with Stripe
        </button>

    </form>

</body>
</html>
