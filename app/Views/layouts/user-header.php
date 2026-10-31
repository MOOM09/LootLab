<?php
$userAppUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
$userPageTitle = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle . ' — LOOT HUB'
    : 'LOOT HUB — Gaming Store';
$userIsSignedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#08070B">
    <title><?= htmlspecialchars($userPageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($userAppUrl . '/css/user.css', ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= htmlspecialchars($userAppUrl . '/js/user.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</head>
<body class="user-body" data-user-app-url="<?= htmlspecialchars($userAppUrl, ENT_QUOTES, 'UTF-8') ?>">
    <a class="user-skip-link" href="#user-main">Skip to content</a>
    <header class="user-navbar" data-user-navbar>
        <div class="user-navbar-inner">
            <a class="user-brand" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>" aria-label="LOOT HUB home">
                <span class="user-brand-mark" aria-hidden="true">L</span>
                <span>LOOT <strong>HUB</strong></span>
            </a>

            <form class="user-search" action="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>" method="get" role="search" data-user-search-form>
                <label class="user-visually-hidden" for="user-site-search">Search products</label>
                <span class="user-search-icon" aria-hidden="true">⌕</span>
                <input id="user-site-search" type="search" name="q" placeholder="Search the collection…" autocomplete="off" data-user-catalog-search>
                <kbd aria-hidden="true">↵</kbd>
            </form>

            <button class="user-menu-toggle" type="button" aria-expanded="false" aria-controls="user-primary-nav" data-user-menu-toggle>
                <span class="user-visually-hidden">Toggle navigation</span>
                <span></span><span></span><span></span>
            </button>

            <nav class="user-primary-nav" id="user-primary-nav" aria-label="Main navigation" data-user-nav>
                <a class="user-nav-link" href="<?= htmlspecialchars($userAppUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">Discover</a>
                <a class="user-nav-link" href="<?= htmlspecialchars($userAppUrl . '/favorites', ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">♡</span> Favorites
                    <span class="user-nav-count" data-user-favorite-count hidden>0</span>
                </a>
                <a
                    class="user-nav-link"
                    href="<?= htmlspecialchars($userAppUrl . ($userIsSignedIn ? '/cart' : '/login'), ENT_QUOTES, 'UTF-8') ?>">
                    <svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 7H6" />
                        <circle cx="10" cy="20" r="1" />
                        <circle cx="18" cy="20" r="1" />
                    </svg>
                    Cart
                </a>
                <details class="user-account-menu" data-user-account-menu>
                    <summary class="user-nav-link">
                        <span class="user-avatar" aria-hidden="true"><?= $userIsSignedIn ? '●' : '○' ?></span>
                        Account
                    </summary>
                    <div class="user-account-dropdown">
                        <?php if ($userIsSignedIn): ?>
                            <a href="<?= htmlspecialchars($userAppUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>">Your profile</a>
                            <a href="<?= htmlspecialchars($userAppUrl . '/orders', ENT_QUOTES, 'UTF-8') ?>" data-user-unavailable="Orders are currently exposed only through an API without user ownership checks.">Your orders</a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($userAppUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Log in</a>
                            <a href="<?= htmlspecialchars($userAppUrl . '/register', ENT_QUOTES, 'UTF-8') ?>">Create account</a>
                        <?php endif; ?>
                    </div>
                </details>
                <a class="user-ai-link" href="<?= htmlspecialchars($userAppUrl . '/ai.php', ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">✦</span> Loot AI
                </a>
            </nav>
        </div>
    </header>
    <main class="user-main" id="user-main">
