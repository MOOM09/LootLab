<?php
$userAppUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
$userPageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle . ' — LOOT HUB' : 'LOOT HUB — Gaming Store';
$userIsSignedIn = isset($_SESSION['user_id']);
$userIsAdmin = $userIsSignedIn && ($_SESSION['role'] ?? '') === 'admin';
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#08070B">
<title><?= htmlspecialchars($userPageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars($userAppUrl . '/css/user.css?v=loote4', ENT_QUOTES, 'UTF-8') ?>">
<script src="<?= htmlspecialchars($userAppUrl . '/js/user.js?v=loote4', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</head>
<body class="user-body" data-user-app-url="<?= htmlspecialchars($userAppUrl, ENT_QUOTES, 'UTF-8') ?>" data-loote-landing="<?= ($pageTitle ?? '') === 'Home' ? 'true' : 'false' ?>">
<a class="user-skip-link" href="#user-main">Skip to content</a>
<header class="user-navbar" data-user-navbar><div class="user-navbar-inner">
<a class="user-brand" href="<?= htmlspecialchars($userAppUrl . '/', ENT_QUOTES, 'UTF-8') ?>" aria-label="Loot Hub homepage"><span class="user-brand-mark" aria-hidden="true">L</span><span>LOOT <strong>HUB</strong></span></a>
<button class="user-menu-toggle" type="button" aria-expanded="false" aria-controls="user-primary-nav" data-user-menu-toggle><span class="user-visually-hidden">Toggle navigation</span><span></span><span></span><span></span></button>
<nav class="user-primary-nav" id="user-primary-nav" aria-label="Main navigation" data-user-nav>
    <div class="user-nav-main">
        <a class="user-nav-link" href="<?= htmlspecialchars($userAppUrl . '/', ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <a class="user-nav-link" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Products</a>
        <a class="user-nav-link" href="<?= htmlspecialchars($userAppUrl . '/favorites', ENT_QUOTES, 'UTF-8') ?>">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 8.2c0 4.5-8.5 10.8-8.5 10.8S3.5 12.7 3.5 8.2a4.6 4.6 0 0 1 8.5-2.5 4.6 4.6 0 0 1 8.5 2.5z"/></svg>
            Favorites <span class="user-nav-count" data-user-favorite-count hidden>0</span>
        </a>
        <a class="user-nav-link user-nav-cart" href="<?= htmlspecialchars($userAppUrl . ($userIsSignedIn ? '/cart' : '/login'), ENT_QUOTES, 'UTF-8') ?>">
            <svg class="user-cart-icon" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>
            </svg>Cart
        </a>
    </div>
    <div class="user-nav-tools">
        <?php if ($userIsAdmin): ?>
            <a class="user-nav-link user-admin-shortcut" href="<?= htmlspecialchars($userAppUrl . '/admin.php', ENT_QUOTES, 'UTF-8') ?>">Go to Dashboard</a>
        <?php endif; ?>
        <a class="user-image-ai-link" href="<?= htmlspecialchars($userAppUrl . '/aiImage.php', ENT_QUOTES, 'UTF-8') ?>" title="AI Image Studio — generate and customize images" aria-label="AI Image Studio">
            <svg class="user-ai-image-icon" viewBox="0 0 36 36" width="34" height="34" fill="none" aria-hidden="true">
                <rect x="2.5" y="5.5" width="26" height="24" rx="5" stroke="currentColor" stroke-width="2"/>
                <circle cx="11" cy="13" r="2.5" fill="currentColor"/>
                <path d="m5 25 8-8 5 5 4-4 4 5" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                <path d="m29 1 1.4 5.3L35 8l-4.6 1.5L29 15l-1.4-5.5L23 8l4.6-1.7z" fill="#e5c6ff"/>
            </svg>
            <span class="user-ai-image-label">AI Image</span>
        </a>
        <details class="user-account-menu" data-user-account-menu>
            <summary class="user-nav-link" aria-label="Account and profile" title="Account">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="8" r="3"/><path d="M5 19c1-4 3-5 7-5s6 1 7 5"/></svg>
            </summary>
            <div class="user-account-dropdown">
                <?php if ($userIsSignedIn): ?>
                    <a href="<?= htmlspecialchars($userAppUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>">Your Profile</a>
                    <a href="<?= htmlspecialchars($userAppUrl . '/orders', ENT_QUOTES, 'UTF-8') ?>">Your Orders</a>
                    <form method="post" action="<?= htmlspecialchars($userAppUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>" data-user-logout>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button class="user-logout-action" type="submit">Log Out</button>
                    </form>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($userAppUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Log in</a>
                    <a href="<?= htmlspecialchars($userAppUrl . '/register', ENT_QUOTES, 'UTF-8') ?>">Create account</a>
                <?php endif; ?>
            </div>
        </details>
    </div>
</nav></div></header>
<main class="user-main" id="user-main">
