
<?php

$userAppUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

$userPageTitle = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle . ' — LOOT HUB'
    : 'LOOT HUB — Gaming Store';

$userIsSignedIn = isset($_SESSION['user_id'])
    && (int) $_SESSION['user_id'] > 0;

$userIsAdmin = $userIsSignedIn
    && ($_SESSION['role'] ?? '') === 'admin';

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

// Current request path
$userRequestPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

// Application base path
$userBasePath = rtrim(
    (string) (parse_url($userAppUrl, PHP_URL_PATH) ?? ''),
    '/'
);

// Resolve route relative to application base
$userCurrentRoute = $userBasePath !== ''
    && (
        $userRequestPath === $userBasePath
        || str_starts_with($userRequestPath, $userBasePath . '/')
    )
        ? substr($userRequestPath, strlen($userBasePath))
        : $userRequestPath;

$userCurrentRoute = $userCurrentRoute === ''
    ? '/'
    : $userCurrentRoute;

// Active navigation item
$userActiveNav = match (true) {
    $userCurrentRoute === '/' => 'home',

    str_starts_with($userCurrentRoute, '/products') => 'products',

    str_starts_with($userCurrentRoute, '/favorites') => 'favorites',

    str_starts_with($userCurrentRoute, '/orders') => 'orders',

    str_starts_with($userCurrentRoute, '/cart'),
    str_starts_with($userCurrentRoute, '/checkout') => 'cart',

    default => '',
};

// Preferences are always whitelisted before use in HTML.
$userTheme = $_SESSION['lootlab_theme'] ?? $_COOKIE['lootlab_theme'] ?? 'plum-dark';
if ($userTheme === 'inferno') $userTheme = 'inferno-light';
if ($userTheme === 'plum-light') $userTheme = 'plum-dark';
if (!in_array($userTheme, ['plum-dark', 'inferno-light'], true)) $userTheme = 'plum-dark';
$userLanguage = $_SESSION['lootlab_lang'] ?? $_COOKIE['lootlab_lang'] ?? 'en';
if (!in_array($userLanguage, ['en', 'ar'], true)) $userLanguage = 'en';
$_SESSION['lootlab_theme'] = $userTheme;
$_SESSION['lootlab_lang'] = $userLanguage;

?>

<!DOCTYPE html>
<html lang="<?= $userLanguage ?>" dir="<?= $userLanguage === 'ar' ? 'rtl' : 'ltr' ?>" data-loot-theme="<?= $userTheme ?>">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="theme-color" content="<?= $userTheme === 'plum-dark' ? '#08070B' : '#FFFFFF' ?>">

    <title>
        <?= htmlspecialchars(
            $userPageTitle,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) ?>
    </title>

    <!-- Google Fonts -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            $userAppUrl . '/css/user.css?v=lootlab-v10',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <!-- Main JavaScript -->
    <script
        src="<?= htmlspecialchars(
            $userAppUrl . '/js/user.js?v=lootlab-v6',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        defer
    ></script>

    <!-- Optional page-specific CSS -->
    <?php if (!empty($userExtraCss)): ?>
        <link
            rel="stylesheet"
            href="<?= htmlspecialchars(
                $userAppUrl . $userExtraCss,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    <?php endif; ?>

    <link rel="stylesheet" href="<?= htmlspecialchars($userAppUrl . '/css/themes.css?v=lootlab-v6', ENT_QUOTES, 'UTF-8') ?>">
    <script src="<?= htmlspecialchars($userAppUrl . '/js/i18n.js?v=lootlab-v4', ENT_QUOTES, 'UTF-8') ?>" defer></script>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
>

    <!-- Navbar icons: keep labels and icons aligned in LTR and RTL. -->
    <style>
        .user-primary-nav .user-nav-link,
        .loothub-guest-nav .user-nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .user-primary-nav .user-nav-link > .fa-solid,
        .loothub-guest-nav .user-nav-link > .fa-solid {
            flex-shrink: 0;
            font-size: 0.95em;
            color: var(--user-primary, currentColor);
            transition: transform 0.2s ease;
        }

        .user-primary-nav .user-nav-link:hover > .fa-solid,
        .loothub-guest-nav .user-nav-link:hover > .fa-solid {
            transform: translateY(-2px);
        }

        @media (prefers-reduced-motion: reduce) {
            .user-primary-nav .user-nav-link > .fa-solid,
            .loothub-guest-nav .user-nav-link > .fa-solid {
                transition: none;
            }
        }
    </style>
</head>

<body
    class="user-body"
    data-user-app-url="<?= htmlspecialchars(
        $userAppUrl,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    data-user-signed-in="<?= $userIsSignedIn ? '1' : '0' ?>"
    data-loote-landing="true"
    data-loot-lang="<?= $userLanguage ?>"
    data-loot-theme="<?= $userTheme ?>"
    data-user-preferences="<?= htmlspecialchars($userAppUrl . '/preferences', ENT_QUOTES, 'UTF-8') ?>"
    data-user-csrf="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
>

    <a class="user-skip-link" href="#user-main">
        Skip to content
    </a>

    <!-- ================= NAVBAR ================= -->
    <header class="user-navbar" data-user-navbar>

        <div class="user-navbar-inner">

            <!-- Brand -->
            <a
                class="user-brand"
                href="<?= htmlspecialchars(
                    $userAppUrl . '/',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                aria-label="Loot Hub homepage"
            >
    <span class="loot-brand-icon">
    <img
        class="loot-logo-dark"
        src="<?= htmlspecialchars($userAppUrl . '/images/branding/loot-hub-symbol.png', ENT_QUOTES, 'UTF-8') ?>"
        alt=""
    >

    <span class="loot-logo-light">
        <i class="fa-solid fa-gamepad"></i>
    </span>
     </span>

                <span>
                    LOOT <strong>HUB</strong>
                </span>
            </a>

            <?php if (!$userIsSignedIn): ?>

                <!-- Guest Navigation -->
                <nav
                    class="loothub-guest-nav"
                    aria-label="Guest navigation"
                >
                    <a
                        class="user-nav-link user-nav-active"
                        href="<?= htmlspecialchars(
                            $userAppUrl . '/',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <i class="fa-solid fa-house" aria-hidden="true"></i>
                        <span data-i18n="Home">Home</span>
                    </a>
                    <a class="user-nav-link<?= $userActiveNav === 'favorites' ? ' user-nav-active' : '' ?>"
                       href="<?= htmlspecialchars($userAppUrl . '/favorites', ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-heart" aria-hidden="true"></i>
                        <span data-i18n="Favorites">Favorites</span>
                        <span class="user-nav-count" data-user-favorite-count hidden>0</span>
                    </a>
                    <a class="user-nav-link user-nav-cart<?= $userActiveNav === 'cart' ? ' user-nav-active' : '' ?>"
                       href="<?= htmlspecialchars($userAppUrl . '/cart', ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                        <span data-i18n="Cart">Cart</span><span class="user-nav-count" data-user-cart-count hidden aria-label="Cart quantity">0</span>
                    </a>
                </nav>

                <div class="user-guest-auth" aria-label="Account">
                    <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">
                        <span data-i18n="Login">Login</span>
                    </a>
                    <a class="user-button" href="<?= htmlspecialchars($userAppUrl . '/register', ENT_QUOTES, 'UTF-8') ?>">
                        <span data-i18n="Register">Register</span>
                    </a>
                </div>

            <?php else: ?>

                <!-- Mobile Menu Toggle -->
                <button
                    class="user-menu-toggle"
                    type="button"
                    aria-expanded="false"
                    aria-controls="user-primary-nav"
                    data-user-menu-toggle
                >
                    <span class="user-visually-hidden">
                        Toggle navigation
                    </span>

                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <!-- Authenticated Navigation -->
                <nav
                    class="user-primary-nav"
                    id="user-primary-nav"
                    aria-label="Main navigation"
                    data-user-nav
                >

                    <div class="user-nav-main">

                        <!-- Home -->
                        <a
                            class="user-nav-link<?= $userActiveNav === 'home' ? ' user-nav-active' : '' ?>"
                            href="<?= htmlspecialchars(
                                $userAppUrl . '/',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= $userActiveNav === 'home' ? 'aria-current="page"' : '' ?>
                        >
                            <i class="fa-solid fa-house" aria-hidden="true"></i>
                            <span data-i18n="Home">Home</span>
                        </a>

                        <!-- Products -->
                        <a
                            class="user-nav-link<?= $userActiveNav === 'products' ? ' user-nav-active' : '' ?>"
                            href="<?= htmlspecialchars(
                                $userAppUrl . '/products',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= $userActiveNav === 'products' ? 'aria-current="page"' : '' ?>
                        >
                            <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                            <span data-i18n="Products">Products</span>
                        </a>

                        <!-- Favorites -->
                        <a
                            class="user-nav-link<?= $userActiveNav === 'favorites' ? ' user-nav-active' : '' ?>"
                            href="<?= htmlspecialchars(
                                $userAppUrl . '/favorites',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <i class="fa-solid fa-heart" aria-hidden="true"></i>
                            <span data-i18n="Favorites">Favorites</span>

                            <span
                                class="user-nav-count"
                                data-user-favorite-count
                                hidden
                            >
                                0
                            </span>
                        </a>

                        <!-- Cart -->
                        <a
                            class="user-nav-link user-nav-cart<?= $userActiveNav === 'cart' ? ' user-nav-active' : '' ?>"
                            href="<?= htmlspecialchars(
                                $userAppUrl . '/cart',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                            <span data-i18n="Cart">Cart</span><span class="user-nav-count" data-user-cart-count hidden aria-label="Cart quantity">0</span>
                        </a>

                        <!-- My Orders -->
                        <a
                            class="user-nav-link<?= $userActiveNav === 'orders' ? ' user-nav-active' : '' ?>"
                            href="<?= htmlspecialchars(
                                $userAppUrl . '/orders',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= $userActiveNav === 'orders' ? 'aria-current="page"' : '' ?>
                        >
                            <i class="fa-solid fa-box" aria-hidden="true"></i>
                            <span data-i18n="My Orders">My Orders</span>
                        </a>

                    </div>

                    <!-- Right Navigation Tools -->
                    <div class="user-nav-tools">

                        <!-- Admin Dashboard -->
                        <?php if ($userIsAdmin): ?>

                            <a
                                class="user-nav-link user-admin-shortcut"
                                href="<?= htmlspecialchars(
                                    $userAppUrl . '/admin.php',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                                <span data-i18n="Go to Dashboard">Go to Dashboard</span>
                            </a>

                        <?php endif; ?>

                        <!-- AI Image Studio -->
                        <a
                            class="user-image-ai-link"
                            href="<?= htmlspecialchars(
                                $userAppUrl . '/aiImage.php',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            aria-label="AI Image Studio"
                            title="AI Image Studio"
                        >
                            <svg
                                class="user-ai-image-icon"
                                viewBox="0 0 36 36"
                                width="34"
                                height="34"
                                fill="none"
                                aria-hidden="true"
                            >
                                <rect
                                    x="2.5"
                                    y="5.5"
                                    width="26"
                                    height="24"
                                    rx="5"
                                    stroke="currentColor"
                                    stroke-width="2"
                                />

                                <circle
                                    cx="11"
                                    cy="13"
                                    r="2.5"
                                    fill="currentColor"
                                />

                                <path
                                    d="m5 25 8-8 5 5 4-4 4 5"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linejoin="round"
                                />

                                <path
                                    d="m29 1 1.4 5.3L35 8l-4.6 1.5L29 15l-1.4-5.5L23 8l4.6-1.7z"
                                    fill="#e5c6ff"
                                />
                            </svg>

                            <span class="user-ai-image-label">
                                AI Image
                            </span>
                        </a>

                        <!-- Account Dropdown -->
                        <details
                            class="user-account-menu"
                            data-user-account-menu
                        >

                            <summary
                                class="user-nav-link"
                                aria-label="Account and profile"
                                title="Account"
                            >
                                <svg
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="10" />
                                    <circle cx="12" cy="8" r="3" />
                                    <path d="M5 19c1-4 3-5 7-5s6 1 7 5" />
                                </svg>
                            </summary>

                            <div class="user-account-dropdown">

                                <a
                                    href="<?= htmlspecialchars(
                                        $userAppUrl . '/profile',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >
                                    Your Profile
                                </a>

                                <a
                                    href="<?= htmlspecialchars(
                                        $userAppUrl . '/orders',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >
                                    Your Orders
                                </a>

                                <div class="loot-preference-block">
                                    <span class="loot-setting-label" data-i18n="Language">Language</span>
                                    <div class="loot-lang-options" role="group" aria-label="Language">
                                        <button type="button" data-loot-pref="lang" data-loot-value="en" aria-pressed="<?= $userLanguage === 'en' ? 'true' : 'false' ?>">English</button>
                                        <button type="button" data-loot-pref="lang" data-loot-value="ar" aria-pressed="<?= $userLanguage === 'ar' ? 'true' : 'false' ?>">العربية</button>
                                    </div>
                                </div>
                                <div class="loot-preference-block loot-theme-options" role="group" aria-label="Color theme">
                                    <span class="loot-setting-label" data-i18n="Theme">Theme</span>
                                    <div class="loot-theme-choices">
                                        <button type="button" class="loot-theme-dot loot-dot-inferno" data-loot-pref="theme" data-loot-value="inferno-light" aria-label="Red and white light theme" title="Inferno Red · Light" aria-pressed="<?= $userTheme === 'inferno-light' ? 'true' : 'false' ?>"></button>
                                        <button type="button" class="loot-theme-dot loot-dot-plum" data-loot-pref="theme" data-loot-value="plum-dark" aria-label="Purple and black dark theme" title="Plum · Dark" aria-pressed="<?= $userTheme === 'plum-dark' ? 'true' : 'false' ?>"></button>
                                    </div>
                                </div>
                                <div class="loot-dropdown-divider" aria-hidden="true"></div>

                                <!-- Logout -->
                                <form
                                    method="post"
                                    action="<?= htmlspecialchars(
                                        $userAppUrl . '/logout',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    data-user-logout
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $_SESSION['csrf_token'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                    <button
                                        class="user-logout-action"
                                        type="submit"
                                    >
                                        Log Out
                                    </button>
                                </form>

                            </div>

                        </details>

                    </div>

                </nav>

            <?php endif; ?>

        </div>

    </header>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="user-main" id="user-main">
