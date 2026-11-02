
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
$productModel = trim((string) ($product['model_path'] ?? ''));

$price = (float) ($product['price'] ?? 0);

$discountPrice = isset($product['discount_price'])
    && $product['discount_price'] !== null
    ? (float) $product['discount_price']
    : null;

$stockQuantity = (int) ($product['stock_quantity'] ?? 0);
$inStock = $stockQuantity > 0;

$categoryName = (string) ($product['category_name'] ?? '');
$subcategoryName = (string) ($product['subcategorys_name'] ?? '');

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

            <!-- Product image or interactive 3D viewer -->
            <div class="user-detail-media <?= $productModel !== '' ? 'lootlab-3d-media' : '' ?>">
                <?php if ($productModel !== ''): ?>
                    <div class="lootlab-3d-viewer" data-lootlab-3d-viewer
                        data-model-src="<?= htmlspecialchars($productModel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        aria-label="Interactive 3D preview of <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <?php if ($productImage !== ''): ?>
                        <img class="lootlab-3d-preview" data-lootlab-3d-preview
                             src="<?= htmlspecialchars($productImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                             style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;z-index:0;pointer-events:none;">
                        <?php endif; ?>
                        <p class="lootlab-3d-status" data-lootlab-3d-status role="status">Loading interactive 3D model…</p>
                        <div class="lootlab-3d-controls">
                            <button type="button" data-lootlab-3d-reset>Reset view</button>
                        </div>
                    </div>
                    <p class="lootlab-3d-hint">Drag to rotate · Scroll to zoom</p>
                <?php else: ?>

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
                <?php endif; ?>
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
                <div class="user-product-actions user-detail-actions">

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

<?php if ($productModel !== ''): ?>
<script type="module" src="<?= htmlspecialchars($userAppUrl . '/js/product-3d.js?v=20261101-2', ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
