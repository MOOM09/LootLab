
<?php
/**
 * Product Details Page
 *
 * Expects:
 * $product (array from Product::getById())
 */

$product = isset($product) && is_array($product) ? $product : [];

$pageTitle = isset($product['name'])
    ? (string) $product['name']
    : 'Product details';

$productId = (int) ($product['id'] ?? 0);
$productName = (string) ($product['name'] ?? '');
$productDescription = (string) ($product['description'] ?? '');
$productImage = trim((string) ($product['image'] ?? ''));

$price = (float) ($product['price'] ?? 0);

$discountPrice = isset($product['discount_price'])
    && $product['discount_price'] !== null
    ? (float) $product['discount_price']
    : null;

$stockQuantity = (int) ($product['stock_quantity'] ?? 0);
$inStock = $stockQuantity > 0;

$categoryName = (string) ($product['category_name'] ?? '');
$subcategoryName = (string) ($product['subcategory_name'] ?? '');

$appUrl = rtrim($appUrl ?? '', '/');

require __DIR__ . '/../layouts/user-header.php';
?>

<section class="user-page">
    <div class="user-container">

        <!-- Breadcrumb -->
        <p class="user-eyebrow">
            <a href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">
                Discover
            </a>

            <span aria-hidden="true"> / </span>

            <span dir="auto">
                <?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </span>
        </p>

        <div class="user-detail-layout">

            <!-- Product Image -->
            <div class="user-detail-media">

                <img
                    data-user-product-image
                    src="<?= htmlspecialchars($productImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    <?= $productImage === '' ? 'hidden' : '' ?>>

                <span
                    class="user-image-fallback"
                    data-user-image-fallback
                    <?= $productImage !== '' ? 'hidden' : '' ?>
                    aria-hidden="true">
                    ✦
                </span>

            </div>

            <!-- Product Information -->
            <div class="user-detail-copy">

                <p class="user-eyebrow" dir="auto">
                    <?= htmlspecialchars($subcategoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </p>

                <h1 dir="auto">
                    <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </h1>

                <!-- Stock -->
                <p class="user-stock-copy <?= $inStock ? '' : 'user-is-out' ?>">

                    <?php if ($inStock): ?>

                        In stock · <?= $stockQuantity ?> available

                    <?php else: ?>

                        Currently out of stock

                    <?php endif; ?>

                </p>

                <!-- Price -->
                <div class="user-product-price user-detail-price">

                    <?php if ($discountPrice !== null): ?>

                        <span class="user-price-current">
                            <?= number_format($discountPrice, 2) ?> JOD
                        </span>

                        <span class="user-price-old">
                            <?= number_format($price, 2) ?> JOD
                        </span>

                        <span class="user-price-tag">
                            Special price
                        </span>

                    <?php else: ?>

                        <span class="user-price-current">
                            <?= number_format($price, 2) ?> JOD
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Description -->
                <div class="user-detail-description" dir="auto">
                    <?= nl2br(
                        htmlspecialchars(
                            $productDescription,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        )
                    ) ?>
                </div>

                <!-- Actions -->
                <div class="user-product-actions">

                    <button
                        class="user-favorite-button"
                        type="button"
                        data-user-favorite-button
                        data-product-id="<?= $productId ?>"
                        aria-pressed="false"
                        aria-label="Add to favorites">
                        ♡
                    </button>

                    <button
                        class="user-button"
                        type="button"
                        data-user-add-to-cart
                        data-product-id="<?= $productId ?>"
                        <?= $inStock ? '' : 'disabled' ?>
                        <?= $inStock ? '' : 'title="Out of stock"' ?>
                        aria-label="Add <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> to cart">

                        <?= $inStock ? 'Add to cart' : 'Out of stock' ?>

                    </button>

                </div>

                <p class="user-status-note">
                    <span aria-hidden="true">i</span>
                    <span>
                        Add products to your cart to review them before checkout.
                    </span>
                </p>

            </div>
        </div>

        <!-- Product Reviews -->
        <?php require __DIR__ . '/../reviews/index.php'; ?>

    </div>
</section>

<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
