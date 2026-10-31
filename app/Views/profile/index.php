<?php
/** @var array $user */
/** @var string $appUrl */
/** @var string $csrfToken */
$pageTitle = 'Your profile';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page">
    <div class="user-container">
        <p class="user-eyebrow">Your account</p>
        <h1 class="user-page-title">Profile</h1>
        <p class="user-page-subtitle">Manage the account information currently available to LootLab.</p>

        <div class="user-profile-grid">
            <section class="user-info-card" aria-labelledby="user-profile-info-heading">
                <h2 id="user-profile-info-heading">Account information</h2>
                <dl class="user-profile-facts">
                    <div>
                        <dt>Name</dt>
                        <dd dir="auto"><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd><?= htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?: 'Not provided' ?></dd>
                    </div>
                    <div>
                        <dt>Account type</dt>
                        <dd><?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
                    </div>
                </dl>
                <hr class="user-divider">
                <form
                    class="user-form"
                    action="<?= htmlspecialchars($appUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>"
                    method="post"
                    data-user-async-form
                    data-user-method="PUT"
                    data-user-json="true"
                    data-user-auto-hide="true">
                    <h3>Update profile</h3>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="user-field">
                        <label for="profile-name">Name</label>
                        <input class="user-input" id="profile-name" type="text" name="name" maxlength="100" value="<?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
                    </div>
                    <div class="user-field">
                        <label for="profile-phone">Phone</label>
                        <input class="user-input" id="profile-phone" type="tel" name="phone" maxlength="25" value="<?= htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </div>
                    <p class="user-form-message" data-user-form-message role="status" aria-live="polite"></p>
                    <div class="user-form-actions">
                        <button class="user-button" type="submit" data-user-submit>Save changes</button>
                    </div>
                </form>
            </section>

            <aside class="user-data-card">
                <h2>Session</h2>
                <p class="user-page-subtitle">Sign out from this device when you are finished.</p>
                <form
                    class="user-form"
                    id="logout-form"
                    action="<?= htmlspecialchars($appUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>"
                    method="post"
                    data-user-async-form>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <p class="user-form-message" id="logout-message" data-user-form-message role="status" aria-live="polite"></p>
                    <button class="user-button-secondary" type="submit" data-user-submit>Log out</button>
                </form>
                <hr class="user-divider">
                <details>
                    <summary class="user-nav-link">Delete account</summary>
                    <p class="user-status-note">This action is permanent. Google accounts require identity confirmation that is not available in this form.</p>
                    <form
                        class="user-form"
                        action="<?= htmlspecialchars($appUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>"
                        method="post"
                        data-user-async-form
                        data-user-method="DELETE"
                        data-user-json="true"
                        data-user-confirm="Delete your LootLab account? This cannot be undone.">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="user-field">
                            <label for="delete-password">Password</label>
                            <input class="user-input" id="delete-password" type="password" name="password" autocomplete="current-password" required>
                        </div>
                        <p class="user-form-message" data-user-form-message role="status" aria-live="polite"></p>
                        <button class="user-button-quiet" type="submit" data-user-submit>Delete account</button>
                    </form>
                </details>
            </aside>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
