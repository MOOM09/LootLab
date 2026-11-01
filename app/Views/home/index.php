<?php
$topProducts = isset($topProducts) && is_array($topProducts) ? $topProducts : [];
$topProductsWithSales = (int) ($topProductsWithSales ?? 0);
$hasDemoRankings = (bool) ($hasDemoRankings ?? false);
$pageTitle = 'Home';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <div class="user-home-hero">
            <p class="user-eyebrow">LOOT HUB · A WORLD OF FANDOM</p>
            <h1>Collect what you <em>love.</em></h1>
            <p>Discover gaming, anime, and pop-culture collectibles curated for fans. Find something unforgettable, save it, and make it yours.</p>
            <a class="user-button" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Explore Products</a>
        </div>

        <section class="user-home-about" id="about">
            <div>
                <p class="user-eyebrow">ABOUT LOOT HUB</p>
                <h2>Made for your next obsession.</h2>
            </div>
            <div>
                <p>LootLab brings the worlds of anime, gaming, movies, and TV together in one place. Discover collectibles, figures, posters, apparel, and accessories that celebrate what you love.</p>
                <p>Save products to Favorites, preview 3D models, or ask Loote AI for personalized recommendations.</p>
            </div>
        </section>
    </div>

    <section class="user-home-featured user-topten-section" aria-labelledby="home-topten-heading">
        <div class="user-container">
            <div class="user-section-heading user-topten-heading">
                <div>
                    <p class="user-eyebrow">TOP 10 · FAN FAVORITES</p>
                    <h2 id="home-topten-heading">Best Sellers</h2>
                    <p class="user-topten-description">
                        <?php if ($topProductsWithSales >= count($topProducts) && count($topProducts) > 0): ?>
                            Ranked by quantities from processed and fulfilled orders.
                        <?php elseif ($hasDemoRankings): ?>
                            Preview leaderboard: sample popularity rankings fill products without completed sales.
                        <?php else: ?>
                            Discover popular picks while confirmed sales rankings build up.
                        <?php endif; ?>
                    </p>
                </div>
                <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">View all products</a>
            </div>
        </div>

        <?php if ($topProducts === []): ?>
            <div class="user-container"><p class="user-status-note">No active products are available yet.</p></div>
        <?php else: ?>
            <div class="user-topten-viewport" aria-label="Moving top ten product showcase. Hover or focus on a product to pause." tabindex="0">
                <div class="user-topten-track">
                    <?php foreach ([false, true] as $duplicate): ?>
                        <div class="user-topten-set" <?= $duplicate ? 'aria-hidden="true"' : '' ?>>
                            <?php foreach ($topProducts as $index => $product): ?>
                                <?php
                                $id = (int) ($product['id'] ?? 0);
                                $name = (string) ($product['name'] ?? '');
                                $image = trim((string) ($product['image'] ?? ''));
                                $price = (float) ($product['discount_price'] ?? $product['price'] ?? 0);
                                $unitsSold = (int) ($product['units_sold'] ?? 0);
                                $productHref = $userAppUrl . '/products/show?id=' . $id;
                                ?>
                                <a class="user-topten-card"
                                    href="<?= htmlspecialchars($productHref, ENT_QUOTES, 'UTF-8') ?>"
                                    aria-label="<?= htmlspecialchars($name . ' — view product details', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                    <?= $duplicate ? 'tabindex="-1"' : '' ?>>
                                    <span class="user-topten-media">
                                        <?php if ($image !== ''): ?>
                                            <img src="<?= htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                                alt="<?= $duplicate ? '' : htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                                loading="lazy">
                                        <?php else: ?>
                                            <span class="user-image-fallback" aria-hidden="true">✦</span>
                                        <?php endif; ?>
                                        <span class="user-topten-rank">#<?= $index + 1 ?></span>
                                    </span>
                                    <span class="user-topten-copy">
                                        <strong dir="auto"><?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                                        <span class="user-topten-price"><?= number_format($price, 2) ?> JD</span>
                                        <span class="user-topten-cta">CLICK TO DETAILS <span aria-hidden="true">↗</span></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="user-topten-hint">Hover to pause · Click to explore</p>
        <?php endif; ?>
    </section>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
