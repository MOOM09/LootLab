<?php
/** @var array $products */
$products = isset($products) && is_array($products) ? $products : [];
$pageTitle = 'Discover';
$userCategories = [];
foreach ($products as $product) {
    if (isset($product['category_id'], $product['category_name'])) {
        $userCategories[(string) $product['category_id']] = (string) $product['category_name'];
    }
}
asort($userCategories, SORT_NATURAL | SORT_FLAG_CASE);
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container" data-user-catalog-page>
        <p class="user-eyebrow">Curated for your next obsession</p>
        <h1 class="user-page-title">Find your next favorite.</h1>
        <p class="user-page-subtitle">
            Explore the collection, discover new picks, and save the things you love.
        </p>

        <div class="user-category-list" role="group" aria-label="Filter products by category">
            <button class="user-category-item" type="button" data-user-category data-category-id="all" data-category-name="All products" aria-pressed="true">
                All products
            </button>
            <?php foreach ($userCategories as $categoryId => $categoryName): ?>
                <button
                    class="user-category-item"
                    type="button"
                    data-user-category
                    data-category-id="<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>"
                    data-category-name="<?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    dir="auto"
                    aria-pressed="false">
                    <?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="user-section-heading">
            <div>
                <h2>Explore the collection</h2>
                <p>Browse real products from the LootLab catalog.</p>
            </div>
        </div>

        <div class="user-catalog-toolbar">
            <p class="user-catalog-result" aria-live="polite">
                <strong data-user-result-count><?= count($products) ?></strong>
                <span> products · </span>
                <span data-user-active-category>All products</span>
            </p>
            <div class="user-catalog-tools">
                <label for="user-product-sort">Sort by</label>
                <select class="user-select" id="user-product-sort" data-user-sort>
                    <option value="newest">Recently added</option>
                    <option value="price-asc">Price: low to high</option>
                    <option value="price-desc">Price: high to low</option>
                    <option value="name">Name</option>
                </select>
            </div>
        </div>

        <div class="user-products-grid" data-user-catalog>
            <?php foreach ($products as $product): ?>
                <?php
                $productId = (int) ($product['id'] ?? 0);
                $categoryId = (string) ($product['category_id'] ?? '');
                $categoryName = (string) ($product['category_name'] ?? '');
                $productName = (string) ($product['name'] ?? '');
                $price = (float) ($product['price'] ?? 0);
                $discountPrice = isset($product['discount_price']) && $product['discount_price'] !== null
                    ? (float) $product['discount_price']
                    : null;
                $image = trim((string) ($product['image'] ?? ''));
                $inStock = (int) ($product['stock_quantity'] ?? 0) > 0;
                ?>
                <article
                    class="user-product-card"
                    data-user-product-card
                    data-product-id="<?= $productId ?>"
                    data-category-id="<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>"
                    data-category-name="<?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    data-name="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    data-price="<?= htmlspecialchars((string) ($discountPrice ?? $price), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="user-product-media">
                        <?php if ($discountPrice !== null): ?>
                            <span class="user-discount-badge">Deal</span>
                        <?php endif; ?>
                        <?php if (!$inStock): ?>
                            <span class="user-stock-badge">Out of stock</span>
                        <?php endif; ?>
                        <button
                            class="user-favorite-button"
                            type="button"
                            data-user-favorite-button
                            data-product-id="<?= $productId ?>"
                            aria-pressed="false"
                            aria-label="Add to favorites">♡</button>
                        <a class="user-product-image-link" href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $productId, ENT_QUOTES, 'UTF-8') ?>" aria-label="View <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            <img
                                class="user-product-image"
                                data-user-product-image
                                src="<?= htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                loading="lazy"
                                <?= $image === '' ? 'hidden' : '' ?>>
                            <span class="user-image-fallback" data-user-image-fallback <?= $image !== '' ? 'hidden' : '' ?> aria-hidden="true">✦</span>
                        </a>
                    </div>
                    <div class="user-product-content">
                        <p class="user-product-category" dir="auto"><?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <h3 class="user-product-title" dir="auto">
                            <a href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $productId, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </a>
                        </h3>
                        <div class="user-product-price" aria-label="Product price">
                            <?php if ($discountPrice !== null): ?>
                                <span class="user-price-current"><?= number_format($discountPrice, 2) ?></span>
                                <span class="user-price-old"><?= number_format($price, 2) ?></span>
                            <?php else: ?>
                                <span class="user-price-current"><?= number_format($price, 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-product-actions">
                            <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $productId, ENT_QUOTES, 'UTF-8') ?>">Details</a>
                            <button
                                class="user-button"
                                type="button"
                                data-user-add-to-cart
                                data-product-id="<?= $productId ?>"
                                <?= $inStock ? '' : 'disabled' ?>
                                <?= $inStock ? '' : 'title="Out of stock"' ?>
                                aria-label="Add <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> to cart">
                                <?= $inStock ? 'Add to cart' : 'Unavailable' ?>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($products === []): ?>
                <div class="user-empty-state">
                    <span class="user-empty-icon" aria-hidden="true">✦</span>
                    <h2>The collection is getting ready</h2>
                    <p>There are no active products to show right now. Please check back soon.</p>
                </div>
            <?php else: ?>
                <div class="user-empty-state" data-user-no-results hidden>
                    <span class="user-empty-icon" aria-hidden="true">⌕</span>
                    <h2>No matches found</h2>
                    <p>Try a different search or choose another category.</p>
                </div>
            <?php endif; ?>
        </div>
        <p class="user-status-note">
            <span aria-hidden="true">i</span>
            <span>Ratings appear on product pages when reviews are available. Cart checkout is not connected yet.</span>
        </p>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
