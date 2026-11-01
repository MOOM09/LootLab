
<?php
/** @var string $appUrl */
/** @var string $csrfToken */

$pageTitle = 'Log in';
require __DIR__ . '/../layouts/user-header.php';
?>

<section class="user-page loothub-auth-page"><div class="loothub-auth-shell">
    <aside class="loothub-auth-hero"><span class="loothub-auth-logo">L✦</span><p class="loothub-auth-kicker">THE LOOT HUB EXPERIENCE</p><h2>Welcome to your<br><em>next obsession.</em></h2><p>Gaming, anime, and custom creations. Discover your world.</p><div class="loothub-auth-orbit">✦</div></aside>
    <div class="user-container">
        <div class="user-form-card loothub-auth-card">

            <p class="user-eyebrow">Welcome back</p>

            <h1 class="user-page-title">
                Log in to your account
            </h1>

            <p class="user-page-subtitle">
                Pick up where your next favorite left off.
            </p>

            <form
                class="user-form"
                id="login-form"
                method="post"
                action="<?= htmlspecialchars($appUrl . '/login', ENT_QUOTES, 'UTF-8') ?>"
                data-user-async-form
                data-user-auto-hide="false">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="user-field">
                    <label for="email">Email</label>
                    <input
                        class="user-input"
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="username"
                        required>
                </div>

                <div class="user-field">
                    <label for="password">Password</label>
                    <input
                        class="user-input"
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required>
                </div>

                <p
                    class="user-form-message"
                    data-user-form-message
                    role="alert"
                    aria-live="polite">
                </p>

                <div class="user-form-actions">
                    <button
                        class="user-button"
                        type="submit"
                        data-user-submit>
                        Log in
                    </button>

                    <a
                        class="user-button-secondary loothub-google-button"
                        href="<?= htmlspecialchars($appUrl . '/auth/google', ENT_QUOTES, 'UTF-8') ?>">
                        <span class="loothub-google-g" aria-hidden="true">G</span> Continue with Google
                    </a>
                </div>

            </form>

            <div class="user-form-links">
                <span>Forgot your password?</span>

                <a href="<?= htmlspecialchars($appUrl . '/forgot-password', ENT_QUOTES, 'UTF-8') ?>">
                    Reset password
                </a>
            </div>

            <?php if (($_GET['notice'] ?? '') === 'signin'): ?><p class="loothub-auth-notice" role="status">Please log in first to continue.</p><?php endif; ?>
            <div class="user-form-links">
                <span>New to LootLab?</span>

                <a href="<?= htmlspecialchars($appUrl . '/register', ENT_QUOTES, 'UTF-8') ?>">
                    Create an account
                </a>
            </div>

        </div>
    </div>
</section>

<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
