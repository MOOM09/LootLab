<?php

/**
 * Expects: $product (row from Product::getById()).
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> - LootLab</title>
</head>

<body>
    <h1><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>

    <?php if (!empty($product['image'])): ?>
        <p><img src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt=""></p>
    <?php endif; ?>

    <p><?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8')) ?></p>

    <p>
        Price: <?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?>
        <?php if ($product['discount_price'] !== null): ?>
            (discounted: <?= htmlspecialchars((string) $product['discount_price'], ENT_QUOTES, 'UTF-8') ?>)
        <?php endif; ?>
    </p>

    <p>
        <?= (int) $product['stock_quantity'] > 0 ? 'In stock' : 'Out of stock' ?>
    </p>

    <?php if ((int) $product['stock_quantity'] > 0): ?>
        <!-- CartController::add expects product_id, quantity and csrf_token. -->
        <button
            type="button"
            data-add-to-cart
            data-product-id="<?= (int) $product['id'] ?>"
            disabled
            title="Add to cart requires JavaScript">Add to cart</button>
    <?php endif; ?>
</body>

</html>