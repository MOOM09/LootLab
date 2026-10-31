<?php
/** @var array $product */
$pageTitle = isset($product['name']) ? (string) $product['name'] : 'Product details';
$productId = (int) ($product['id'] ?? 0);
$productName = (string) ($product['name'] ?? '');
$image = trim((string) ($product['image'] ?? ''));
$price = (float) ($product['price'] ?? 0);
$discountPrice = isset($product['discount_price']) && $product['discount_price'] !== null
    ? (float) $product['discount_price']
    : null;
$inStock = (int) ($product['stock_quantity'] ?? 0) > 0;
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <p class="user-eyebrow">
            <a href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Discover</a>
            <span aria-hidden="true"> / </span>
            <span dir="auto"><?= htmlspecialchars((string) ($product['category_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        </p>
        <div class="user-detail-layout">
            <div class="user-detail-media">
                <img
                    data-user-product-image
                    src="<?= htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    <?= $image === '' ? 'hidden' : '' ?>>
                <span class="user-image-fallback" data-user-image-fallback <?= $image !== '' ? 'hidden' : '' ?> aria-hidden="true">✦</span>
            </div>
            <div class="user-detail-copy">
                <p class="user-eyebrow" dir="auto"><?= htmlspecialchars((string) ($product['subcategory_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <h1 dir="auto"><?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
                <p class="user-stock-copy <?= $inStock ? '' : 'user-is-out' ?>">
                    <?= $inStock ? 'In stock · ' . (int) $product['stock_quantity'] . ' available' : 'Currently out of stock' ?>
                </p>
                <div class="user-product-price user-detail-price">
                    <?php if ($discountPrice !== null): ?>
                        <span class="user-price-current"><?= number_format($discountPrice, 2) ?></span>
                        <span class="user-price-old"><?= number_format($price, 2) ?></span>
                        <span class="user-price-tag">Special price</span>
                    <?php else: ?>
                        <span class="user-price-current"><?= number_format($price, 2) ?></span>
                    <?php endif; ?>
                </div>
                <p class="user-detail-description" dir="auto">
                    <?= htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </p>
                <div class="user-product-actions">
                    <button
                        class="user-favorite-button"
                        type="button"
                        data-user-favorite-button
                        data-product-id="<?= $productId ?>"
                        aria-pressed="false"
                        aria-label="Add to favorites">♡</button>
                    <button
                        class="user-button"
                        type="button"
                        data-user-add-to-cart
                        data-product-id="<?= $productId ?>"
                        <?= $inStock ? '' : 'disabled' ?>>
                        <?= $inStock ? 'Add to cart' : 'Out of stock' ?>
                    </button>
                </div>
                <p class="user-status-note">
                    <span aria-hidden="true">i</span>
                    <span>Add products to your cart to review them before checkout.</span>
                </p>
            </div>
        </div>
        <?php require __DIR__ . '/../reviews/index.php'; ?>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
