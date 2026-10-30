
<?php
$old = $old ?? [];
$error = $error ?? null;
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
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form action="place-order" method="POST">

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

        <div>
            <h3>Payment Method</h3>
            <p>Cash on Delivery</p>
            <p>Pay when your order arrives.</p>
        </div>

        <button type="submit">Place Order</button>

    </form>

</body>
</html>
