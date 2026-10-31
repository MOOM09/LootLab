<?php
/** @var array $items */
$items = isset($items) && is_array($items) ? $items : [];
$pageTitle = 'Favorites';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page user-favorites">
    <div class="user-container">
        <p class="user-eyebrow">Saved for later</p>
        <h1 class="user-page-title">Your favorites</h1>
        <p class="user-page-subtitle">Products you have saved from the collection.</p>
        <p class="user-form-message" data-favorites-status role="status" aria-live="polite"></p>
        <div class="user-products-grid" data-favorites-list>
            <?php foreach ($items as $item): ?>
                <?php
                $productId = (int) ($item['product_id'] ?? 0);
                $productName = (string) ($item['name'] ?? '');
                $image = trim((string) ($item['image'] ?? ''));
                $price = (float) ($item['price'] ?? 0);
                $discountPrice = isset($item['discount_price']) && $item['discount_price'] !== null
                    ? (float) $item['discount_price']
                    : null;
                ?>
                <article class="user-product-card" data-favorite-product="<?= $productId ?>">
                    <div class="user-product-media">
                        <button
                            class="user-favorite-button"
                            type="button"
                            data-user-favorite-button
                            data-product-id="<?= $productId ?>"
                            aria-pressed="true"
                            aria-label="Remove from favorites">♥</button>
                        <a
                            class="user-product-image-link"
                            href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $productId, ENT_QUOTES, 'UTF-8') ?>"
                            aria-label="View <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
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
                        <h2 class="user-product-title" dir="auto">
                            <a href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $productId, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </a>
                        </h2>
                        <div class="user-product-price" aria-label="Product price">
                            <?php if ($discountPrice !== null): ?>
                                <span class="user-price-current"><?= number_format($discountPrice, 2) ?></span>
                                <span class="user-price-old"><?= number_format($price, 2) ?></span>
                            <?php else: ?>
                                <span class="user-price-current"><?= number_format($price, 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <div class="user-empty-state" data-favorites-empty>
                    <span class="user-empty-icon" aria-hidden="true">♡</span>
                    <h2>No favorites saved yet</h2>
                    <p>Save a product from the collection and it will appear here.</p>
                    <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Browse products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
