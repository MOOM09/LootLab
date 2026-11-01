<?php
/** @var string $csrfToken */
/** @var string $appUrl */
$pageTitle = 'Forgot password';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page loothub-auth-page">
    <div class="loothub-auth-shell">
        <aside class="loothub-auth-hero">
            <span class="loothub-auth-logo">L✦</span>
            <p class="loothub-auth-kicker">SECURE YOUR ACCOUNT</p>
            <h2>Forgot your<br><em>password?</em></h2>
            <p>We'll help you get back to your LootLab collection.</p>
        </aside>
        <div class="user-container">
            <section class="user-form-card loothub-auth-card">
                <p class="user-eyebrow">ACCOUNT RECOVERY</p>
                <h1 class="user-page-title">Forgot password?</h1>
                <p class="user-page-subtitle">Enter your account email to request a password reset link.</p>
                <form class="user-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="user-field">
                        <label for="email">Email</label>
                        <input class="user-input" id="email" type="email" name="email" maxlength="255" autocomplete="email" required>
                    </div>
                    <div class="user-form-actions"><button class="user-button" type="submit">Send reset link</button></div>
                </form>
                <p class="user-form-links">Remember your password?
                    <a href="<?= htmlspecialchars($appUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Back to login</a>
                </p>
            </section>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
