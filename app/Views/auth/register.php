<?php
/** @var string $appUrl */
/** @var string $csrfToken */
$pageTitle = 'Create account';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <div class="user-form-card">
            <p class="user-eyebrow">Join the community</p>
            <h1 class="user-page-title">Create your account</h1>
            <p class="user-page-subtitle">Save favorites and keep your LootLab account in one place.</p>

            <form
                class="user-form"
                id="register-form"
                method="post"
                action="<?= htmlspecialchars($appUrl . '/register', ENT_QUOTES, 'UTF-8') ?>"
                data-user-async-form
                data-user-auto-hide="false">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="user-field">
                    <label for="name">Name</label>
                    <input class="user-input" id="name" type="text" name="name" autocomplete="name" maxlength="100" required>
                </div>
                <div class="user-field">
                    <label for="email">Email</label>
                    <input class="user-input" id="email" type="email" name="email" autocomplete="email" maxlength="255" required>
                </div>
                <div class="user-form-grid">
                    <div class="user-field">
                        <label for="password">Password</label>
                        <input class="user-input" id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <div class="user-field">
                        <label for="password-confirmation">Confirm password</label>
                        <input class="user-input" id="password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                </div>
                <p class="user-form-message" data-user-form-message role="alert" aria-live="polite"></p>
                <div class="user-form-actions">
                    <button class="user-button" type="submit" data-user-submit>Create account</button>
                    <a class="user-button-secondary" href="<?= htmlspecialchars($appUrl . '/auth/google', ENT_QUOTES, 'UTF-8') ?>">Continue with Google</a>
                </div>
            </form>
            <div class="user-form-links">
                <span>Already have an account?</span>
                <a href="<?= htmlspecialchars($appUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Log in</a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
