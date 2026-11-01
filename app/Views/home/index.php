<?php
$featuredProducts = isset($featuredProducts) && is_array($featuredProducts) ? $featuredProducts : [];
$pageTitle = 'Home';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page"><div class="user-container">
<div class="user-home-hero"><p class="user-eyebrow">LOOT HUB · A WORLD OF FANDOM</p><h1>Collect what you <em>love.</em></h1><p>Discover gaming, anime, and pop-culture collectibles curated for fans. Find something unforgettable, save it, and make it yours.</p><a class="user-button" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Explore Products</a></div>
<section class="user-home-about" id="about"><div><p class="user-eyebrow">ABOUT LOOT HUB</p><h2>Made for your next obsession.</h2></div><div><p>LootLab brings the worlds of anime, gaming, movies, and TV together in one place. Discover collectibles, figures, posters, apparel, and accessories that celebrate what you love.</p><p>Save products to Favorites, preview 3D models, or ask Loote AI for personalized recommendations.</p></div></section>
<section class="user-home-featured"><div class="user-section-heading"><div><p class="user-eyebrow">FEATURED DROPS</p><h2>Start exploring</h2></div><a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">View all products</a></div><div class="user-products-grid">
<?php foreach ($featuredProducts as $product): ?>
<?php $id=(int)$product['id']; $name=(string)$product['name']; $image=(string)($product['image']??''); ?>
<article class="user-product-card user-home-card"><a href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $id, ENT_QUOTES, 'UTF-8') ?>"><img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" loading="lazy"></a><div class="user-product-content"><h3 class="user-product-title"><a href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $id, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></a></h3><p class="user-product-price"><?= number_format((float)($product['discount_price']??$product['price']),2) ?> JD</p><a class="user-click-details" href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . $id, ENT_QUOTES, 'UTF-8') ?>">CLICK TO DETAILS</a></div></article>
<?php endforeach; ?></div></section>
</div></section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
