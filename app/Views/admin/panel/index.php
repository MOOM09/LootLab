<?php
/** @var array $bootstrap */
$adminUrl = rtrim($bootstrap["appUrl"], "/");
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$adminPrefCsrf = $_SESSION['csrf_token']; ?>
<?php
$adminTheme = $_SESSION['lootlab_theme'] ?? $_COOKIE['lootlab_theme'] ?? 'plum-dark';
if ($adminTheme === 'inferno') $adminTheme = 'inferno-light';
if ($adminTheme === 'plum-light') $adminTheme = 'plum-dark';
if (!in_array($adminTheme, ['plum-dark', 'inferno-light'], true)) $adminTheme = 'plum-dark';
$adminLang = $_SESSION['lootlab_lang'] ?? $_COOKIE['lootlab_lang'] ?? 'en';
if (!in_array($adminLang, ['en', 'ar'], true)) $adminLang = 'en';
?>
<!DOCTYPE html>
<html lang="<?= $adminLang ?>" dir="<?= $adminLang === 'ar' ? 'rtl' : 'ltr' ?>" data-loot-theme="<?= $adminTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="<?= $adminTheme === 'plum-dark' ? '#08070B' : '#FFFFFF' ?>">
    <title>LOOT HUB — Admin Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminUrl . "/css/admin.css?v=lootlab-admin-v5", ENT_QUOTES, "UTF-8") ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminUrl . '/css/themes-admin.css?v=lootlab-admin-v5', ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= htmlspecialchars($adminUrl . '/js/i18n.js?v=lootlab-admin-v5', ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <script type="application/json" id="admin-bootstrap"><?= json_encode(
        $bootstrap,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
    ) ?></script>
    <script src="<?= htmlspecialchars($adminUrl . "/js/admin.js?v=lootlab-admin-validation-2", ENT_QUOTES, "UTF-8") ?>" defer></script>
</head>
<body class="admin-body">
<a class="admin-skip" href="#admin-main">Skip to content</a>
<svg xmlns="http://www.w3.org/2000/svg" class="admin-icon-defs" aria-hidden="true"><defs>
    <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/></symbol>
    <symbol id="i-box" viewBox="0 0 24 24"><path d="m12 3 9 5v9l-9 5-9-5V8l9-5Zm-9 5 9 5 9-5m-9 5v9M7.5 5.5l9 5"/></symbol>
    <symbol id="i-category" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h5l3 4h6a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 9h18"/></symbol>
    <symbol id="i-bag" viewBox="0 0 24 24"><path d="M5 7h14l2 14H3L5 7Zm3 0V5a4 4 0 0 1 8 0v2"/><path d="M9 12h6"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24"><path d="M3 3v18h18M7 14l4-4 4 3 6-8"/></symbol>
    <symbol id="i-tag" viewBox="0 0 24 24"><path d="m3 3 9 .1 9 9a2 2 0 0 1 0 2.8l-6.1 6.1a2 2 0 0 1-2.8 0l-9-9L3 3Z"/><circle cx="7.5" cy="7.5" r="1"/></symbol>
    <symbol id="i-star" viewBox="0 0 24 24"><path d="m12 3 2.8 5.8 6.4.9-4.6 4.5 1.1 6.3-5.7-3-5.7 3 1.1-6.3-4.6-4.5 6.4-.9L12 3Z"/></symbol>
    <symbol id="i-settings" viewBox="0 0 24 24"><path d="m9 3-1 3-3 1-2 3 2 2-1 3 2 3 3-1 3 2 3-1 2-3 3-1v-4l-3-1-1-3-3-2-3 1Z"/><circle cx="12" cy="12" r="3"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-edit" viewBox="0 0 24 24"><path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-2 2 5 5"/></symbol>
    <symbol id="i-trash" viewBox="0 0 24 24"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></symbol>
    <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></symbol>
    <symbol id="i-wallet" viewBox="0 0 24 24"><path d="M20 8V5H5a2 2 0 0 0 0 4h16v11H5a2 2 0 0 1-2-2V7m13 6h5v4h-5v-4Z"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><path d="m12 3 10 18H2L12 3Zm0 6v5m0 3v1"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
    <symbol id="i-download" viewBox="0 0 24 24"><path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 6-6 4 4 3-3 5 5"/></symbol>
    <symbol id="i-gamepad" viewBox="0 0 24 24"><path d="M6.5 8h11a4 4 0 0 1 3.8 3l1.1 5.5a2 2 0 0 1-3.1 2l-3.4-2.4H8.1l-3.4 2.4a2 2 0 0 1-3.1-2L2.7 11a4 4 0 0 1 3.8-3Z"/><path d="M6.5 12h4m-2-2v4m7.7-2h.1m2.2 2h.1"/></symbol>
    <symbol id="i-power" viewBox="0 0 24 24"><path d="M12 2v10M6 5a9 9 0 1 0 12 0"/></symbol>
</defs></svg>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <a class="admin-brand" href="?page=overview" data-nav="overview" aria-label="LOOT HUB Admin Overview">
        <span class="admin-brand-mark" aria-hidden="true">
            <img class="admin-logo-dark" src="<?= htmlspecialchars($adminUrl . '/images/branding/loot-hub-symbol.png', ENT_QUOTES, 'UTF-8') ?>" alt="">
            <span class="admin-logo-light"><svg><use href="#i-gamepad"/></svg></span>
        </span>
        <span>LOOT <b>HUB</b><small>ADMIN STUDIO</small></span>
    </a>
   
    <p class="admin-nav-label">WORKSPACE</p>
    <nav aria-label="Management sections">
        <a href="?page=overview" data-nav="overview"><svg><use href="#i-grid"/></svg><span>Overview</span><span class="admin-nav-live">LIVE</span></a>
        <a href="?page=users" data-nav="users"><svg><use href="#i-users"/></svg><span>Users</span></a>
        <a href="?page=products" data-nav="products"><svg><use href="#i-box"/></svg><span>Products</span></a>
        <a href="?page=categories" data-nav="categories"><svg><use href="#i-category"/></svg><span>Categories</span></a>
        <a href="?page=orders" data-nav="orders"><svg><use href="#i-bag"/></svg><span>Orders</span><span id="admin-pending-count" class="admin-nav-counter" hidden></span></a>
        <p class="admin-nav-label">STORE TOOLS</p>
        <a href="?page=promos" data-nav="promos"><svg><use href="#i-tag"/></svg><span>Promo codes</span></a>
        <a href="?page=reviews" data-nav="reviews"><svg><use href="#i-star"/></svg><span>Reviews</span></a>
    </nav>
        </nav>

    <a class="admin-workspace" href="?page=settings" data-nav="settings">
        <span class="admin-avatar admin-workspace-avatar" data-admin-avatar aria-hidden="true">LH</span>

        <span class="admin-workspace-identity">
            <strong data-admin-name>
                <?= htmlspecialchars((string) ($bootstrap["user"]["name"] ?? "Administrator"), ENT_QUOTES, "UTF-8") ?>
            </strong>
            <small>Account settings</small>
        </span>

        <span class="admin-live-dot"></span>
    </a>
</aside>
</aside>
<button class="admin-mobile-scrim" id="admin-mobile-scrim" aria-label="Close navigation" hidden></button>
<div class="admin-shell">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-icon-button admin-mobile-toggle" data-action="menu" aria-label="Open navigation" aria-expanded="false"><svg><use href="#i-menu"/></svg></button>
            <a href="?page=overview" data-nav="overview" class="admin-workspace-crumb">Workspace</a>
            <svg><use href="#i-chevron"/></svg><strong id="admin-crumb">Overview</strong>
        </div>
        <div class="admin-top-actions">
            <button class="admin-global-search" data-action="search" aria-label="Search workspace"><svg><use href="#i-search"/></svg><span>Search the store…</span><kbd>Ctrl K</kbd></button>
            <span class="admin-top-separator"></span>
            <button class="admin-icon-button admin-notifications" data-nav="products" data-stock="attention" title="Review low-stock products" aria-label="Low-stock notifications"><svg><use href="#i-bell"/></svg><i></i></button>
            <details class="admin-account-menu" data-admin-account-menu>
                <summary
    aria-label="Account and profile"
    title="Account and profile"
    class="admin-profile-trigger"
>
    <svg
        class="admin-profile-icon"
        width="26"
        height="26"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.7"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="8" r="3"/>
        <path d="M5 19c1-4 3-5 7-5s6 1 7 5"/>
    </svg>
</summary>
                <div class="admin-account-dropdown">
                    <a href="?page=settings" data-nav="settings"><svg><use href="#i-settings"/></svg><span>Your Profile</span></a>
                    <div class="admin-pref-group"><span class="admin-pref-title">Language</span>
                        <div class="admin-lang-buttons" role="group" aria-label="Language">
                            <button type="button" data-admin-pref="lang" data-value="en" aria-pressed="<?= $adminLang === 'en' ? 'true' : 'false' ?>">English</button>
                            <button type="button" data-admin-pref="lang" data-value="ar" aria-pressed="<?= $adminLang === 'ar' ? 'true' : 'false' ?>">العربية</button>
                        </div>
                    </div>
                    <div class="admin-pref-group"><span class="admin-pref-title">Theme</span>
                        <div class="admin-theme-buttons" role="group" aria-label="Color theme">
                            <button type="button" class="admin-theme-dot admin-dot-red" data-admin-pref="theme" data-value="inferno-light" aria-label="Red and white light theme" title="Inferno Red · Light" aria-pressed="<?= $adminTheme === 'inferno-light' ? 'true' : 'false' ?>"></button>
                            <button type="button" class="admin-theme-dot admin-dot-purple" data-admin-pref="theme" data-value="plum-dark" aria-label="Purple and black dark theme" title="Plum · Dark" aria-pressed="<?= $adminTheme === 'plum-dark' ? 'true' : 'false' ?>"></button>
                        </div>
                    </div>
                    <form method="post" action="<?= htmlspecialchars($adminUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>" data-admin-logout>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminPrefCsrf, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="admin-logout-button"><svg><use href="#i-power"/></svg><span>Log Out</span></button>
                    </form>
                </div>
            </details>
        </div>
    </header>
    <main class="admin-main" id="admin-main" tabindex="-1"><div class="admin-loading"><span></span>Getting your workspace ready…</div></main>
    <footer class="admin-footer"><span>LOOT HUB <span class="admin-footer-dot">·</span> Admin Studio</span><span>Made for your next chapter.</span></footer>
</div>
<dialog class="admin-drawer" id="admin-drawer" aria-label="Record details"></dialog>
<dialog class="admin-modal" id="admin-modal" aria-label="Admin action"></dialog>
<div class="admin-toasts" id="admin-toasts" aria-live="polite" aria-atomic="false"></div>
</body>
</html>
