<?php
/** @var array $products */
$products = isset($products) && is_array($products) ? $products : [];
$pageTitle = 'Discover';
$appUrl = rtrim($appUrl ?? ($userAppUrl ?? ''), '/');
$csrfToken = $csrfToken ?? '';
$isLoggedIn = $isLoggedIn ?? false;
$userCategories = [];
$userSubcategories = [];
$userProductTypes = [];
foreach ($products as $product) {
    if (isset($product['product_type_id'], $product['product_type_name']) && $product['product_type_id'] !== null) {
        $userProductTypes[(string) $product['product_type_id']] = (string) $product['product_type_name'];
    }
    if (isset($product['category_id'], $product['category_name'])) {
        $categoryId = (string) $product['category_id'];
        $userCategories[$categoryId] = (string) $product['category_name'];
        if (isset($product['subcategorys_id'], $product['subcategorys_name'])) {
            $userSubcategories[$categoryId][(string) $product['subcategorys_id']] = (string) $product['subcategorys_name'];
        }
    }
}
asort($userCategories, SORT_NATURAL | SORT_FLAG_CASE);
asort($userProductTypes, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($userSubcategories as &$categorySubcategories) {
    asort($categorySubcategories, SORT_NATURAL | SORT_FLAG_CASE);
}
unset($categorySubcategories);
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container" data-user-catalog-page>
        <p class="user-eyebrow">Curated for your next obsession</p>
        <h1 class="user-page-title">Find your next favorite.</h1>
        <p class="user-page-subtitle">
            Explore the collection, discover new picks, and save the things you love.
        </p>


        <form class="user-catalog-search" action="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>" method="get" role="search" data-user-search-form>
            <label for="user-site-search">Search products</label>
            <input id="user-site-search" type="search" name="q" placeholder="Search products, categories and subcategories..." data-user-catalog-search autocomplete="off">
            <button type="submit" class="user-button-secondary">Search</button>
        </form>
        <div class="user-filters-heading"><h2>Filter products</h2><p>Choose a category, subcategory or product type.</p></div>
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

        <!-- Subcategory buttons appear after a category is selected. -->
        <div class="user-category-list" data-user-subcategory-list hidden role="group" aria-label="Filter products by subcategory">
            <button class="user-category-item" type="button" data-user-subcategory data-subcategory-id="all" data-subcategory-name="All subcategories" aria-pressed="true">
                All subcategories
            </button>
            <?php foreach ($userSubcategories as $subcategoryCategoryId => $subcategoryNames): ?>
                <?php foreach ($subcategoryNames as $subcategoryId => $subcategoryName): ?>
                    <button
                        class="user-category-item"
                        type="button"
                        data-user-subcategory
                        data-category-id="<?= htmlspecialchars((string) $subcategoryCategoryId, ENT_QUOTES, 'UTF-8') ?>"
                        data-subcategory-id="<?= htmlspecialchars((string) $subcategoryId, ENT_QUOTES, 'UTF-8') ?>"
                        data-subcategory-name="<?= htmlspecialchars($subcategoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        aria-pressed="false"
                        hidden>
                        <?= htmlspecialchars($subcategoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <!-- Product types: e.g. 3D Models, Posters, Figures -->
        <div class="user-category-list" role="group" aria-label="Filter products by product type" data-user-type-list>
            <button class="user-category-item" type="button" data-user-type data-type-id="all" data-type-name="All types" aria-pressed="true">All types</button>
            <?php foreach ($userProductTypes as $typeId => $typeName): ?>
                <button class="user-category-item" type="button" data-user-type
                    data-type-id="<?= htmlspecialchars($typeId, ENT_QUOTES, 'UTF-8') ?>"
                    data-type-name="<?= htmlspecialchars($typeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    aria-pressed="false"><?= htmlspecialchars($typeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></button>
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
                $productDescription = trim((string) ($product['description'] ?? ''));
                $price = (float) ($product['price'] ?? 0);
                $discountPrice = isset($product['discount_price']) && $product['discount_price'] !== null
                    ? (float) $product['discount_price']
                    : null;
                $image = trim((string) ($product['image'] ?? ''));
                $inStock = (int) ($product['stock_quantity'] ?? 0) > 0;
                $has3DModel = trim((string) ($product['model_path'] ?? '')) !== '';
                ?>
                <article
                    class="user-product-card"
                    data-user-product-card
                    data-product-id="<?= $productId ?>"
                    data-category-id="<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>"
                    data-category-name="<?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    data-subcategory-id="<?= htmlspecialchars((string) ($product['subcategorys_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-subcategory-name="<?= htmlspecialchars((string) ($product['subcategorys_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    data-type-id="<?= htmlspecialchars((string) ($product['product_type_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-name="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    data-price="<?= htmlspecialchars((string) ($discountPrice ?? $price), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="user-product-media">
                        <?php if ($has3DModel): ?>
                            <span class="lootlab-3d-badge">◈ 3D VIEW</span>
                        <?php endif; ?>
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
                                <span class="user-price-current"><?= number_format($discountPrice, 2) ?> JD</span>
                                <span class="user-price-old"><?= number_format($price, 2) ?> JD JD</span>
                            <?php else: ?>
                                <span class="user-price-current"><?= number_format($price, 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="user-product-description" dir="auto"><?= htmlspecialchars($productDescription !== '' ? $productDescription : 'Explore this LootLab collectible.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        <a class="user-click-details" href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $productId, ENT_QUOTES, 'UTF-8') ?>">CLICK TO DETAILS</a>
                        <div class="user-product-actions">
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
        <nav class="user-catalog-pagination" aria-label="Product pages" data-user-pagination></nav>
        <p class="user-status-note">
            <span aria-hidden="true">i</span>
            <span>Ratings appear on product pages when reviews are available.</span>
        </p>
    </div>
</section>
<script src="<?= htmlspecialchars($userAppUrl . '/js/catalog-filter-fix.js?v=loote2', ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
