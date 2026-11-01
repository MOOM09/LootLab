
<?php
/**
 * Profile Page
 *
 * Expects:
 * $user (array)
 * $designs (array)
 * $appUrl (string)
 * $csrfToken (string)
 */

$user = isset($user) && is_array($user) ? $user : [];
$designs = isset($designs) && is_array($designs) ? $designs : [];

$appUrl = rtrim($appUrl ?? '', '/');
$csrfToken = $csrfToken ?? '';

$pageTitle = 'Your profile';

require __DIR__ . '/../layouts/user-header.php';
?>

<section class="user-page">
    <div class="user-container">

        <p class="user-eyebrow">Your account</p>

        <h1 class="user-page-title">Profile</h1>

        <p class="user-page-subtitle">
            Manage your account information and saved AI designs.
        </p>

        <div class="user-profile-grid">

            <!-- ACCOUNT INFORMATION -->
            <section
                class="user-info-card"
                aria-labelledby="user-profile-info-heading">

                <h2 id="user-profile-info-heading">
                    Account Information
                </h2>

                <dl class="user-profile-facts">

                    <div>
                        <dt>Name</dt>
                        <dd dir="auto">
                            <?= htmlspecialchars(
                                (string) ($user['name'] ?? ''),
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Email</dt>
                        <dd>
                            <?= htmlspecialchars(
                                (string) ($user['email'] ?? ''),
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Phone</dt>
                        <dd>
                            <?= htmlspecialchars(
                                (string) ($user['phone'] ?? ''),
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ) ?: 'Not provided' ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Account Type</dt>
                        <dd>
                            <?= htmlspecialchars(
                                (string) ($user['role'] ?? ''),
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                </dl>

                <hr class="user-divider">

                <!-- UPDATE PROFILE -->
                <form
                    class="user-form"
                    action="<?= htmlspecialchars($appUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>"
                    method="post"
                    data-user-async-form
                    data-user-method="PUT"
                    data-user-json="true"
                    data-user-auto-hide="true">

                    <h3>Update Profile</h3>

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="user-field">
                        <label for="profile-name">Name</label>

                        <input
                            class="user-input"
                            id="profile-name"
                            type="text"
                            name="name"
                            maxlength="100"
                            value="<?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            required>
                    </div>

                    <div class="user-field">
                        <label for="profile-phone">Phone</label>

                        <input
                            class="user-input"
                            id="profile-phone"
                            type="tel"
                            name="phone"
                            maxlength="25"
                            value="<?= htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </div>

                    <p
                        class="user-form-message"
                        data-user-form-message
                        role="status"
                        aria-live="polite"></p>

                    <div class="user-form-actions">
                        <button
                            class="user-button"
                            type="submit"
                            data-user-submit>
                            Save Changes
                        </button>
                    </div>

                </form>

            </section>

            <!-- SESSION / ACCOUNT SETTINGS -->
            <aside class="user-data-card">

                <h2>Session</h2>

                <p class="user-page-subtitle">
                    Sign out from this device when you are finished.
                </p>

                <!-- LOGOUT -->
                <form
                    class="user-form"
                    id="logout-form"
                    action="<?= htmlspecialchars($appUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>"
                    method="post"
                    data-user-async-form>

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <p
                        class="user-form-message"
                        id="logout-message"
                        data-user-form-message
                        role="status"
                        aria-live="polite"></p>

                    <button
                        class="user-button-secondary"
                        type="submit"
                        data-user-submit>
                        Log Out
                    </button>

                </form>

                <hr class="user-divider">

                <!-- DELETE ACCOUNT -->
                <details>

                    <summary class="user-nav-link">
                        Delete Account
                    </summary>

                    <p class="user-status-note">
                        This action is permanent. Google accounts require
                        identity confirmation that is not available in this form.
                    </p>

                    <form
                        class="user-form"
                        action="<?= htmlspecialchars($appUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>"
                        method="post"
                        data-user-async-form
                        data-user-method="DELETE"
                        data-user-json="true"
                        data-user-confirm="Delete your LootLab account? This cannot be undone.">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="user-field">
                            <label for="delete-password">
                                Password
                            </label>

                            <input
                                class="user-input"
                                id="delete-password"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                required>
                        </div>

                        <p
                            class="user-form-message"
                            data-user-form-message
                            role="status"
                            aria-live="polite"></p>

                        <button
                            class="user-button-quiet"
                            type="submit"
                            data-user-submit>
                            Delete Account
                        </button>

                    </form>

                </details>

            </aside>

        </div>

        <!-- AI CUSTOM DESIGNS -->
        <section class="user-designs">

            <p class="user-eyebrow">AI Creations</p>

            <h2 class="user-page-title">
                	My Designs
            </h2>

            <p class="user-page-subtitle">
                Your saved AI-generated designs.
            </p>

            <?php if (empty($designs)): ?>

                <div class="user-empty-state">

                    <span
                        class="user-empty-icon"
                        aria-hidden="true">
                        ✦
                    </span>

                    <h3>No saved designs yet</h3>

                    <p>
                        Create your first AI design and find it here.
                    </p>

                </div>

            <?php else: ?>

                <div
                    class="user-products-grid"
                    style="margin-top: 24px;">

                    <?php foreach ($designs as $design): ?>

                        <?php
                        $designId = (int) ($design['id'] ?? 0);

                        $imageUrl = $appUrl
                            . '/profile/design-image?id='
                            . $designId;

                        $designLink = $appUrl
                            . '/aiImage.php?design_id='
                            . $designId;
                        ?>

                        <article class="user-product-card user-design-card">

                            <div class="user-product-media">

                                <a
                                    class="user-product-image-link"
                                    href="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    target="_blank"
                                    rel="noopener">

                                    <img
                                        class="user-product-image"
                                        src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="AI Design #<?= $designId ?>"
                                        loading="lazy">

                                </a>

                            </div>

                            <div class="user-product-content">

                                <h3 class="user-product-title">
                                   <?= $userLanguage === 'ar' ? 'تصميم' : 'Design' ?> #<?= $designId ?>
                                </h3>

                                <p class="user-product-category">
                                    <?= htmlspecialchars(
                                        (string) ($design['created_at'] ?? ''),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <div class="user-product-actions">

                                    <a
                                        class="user-button-secondary"
                                        href="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        target="_blank"
                                        rel="noopener">
                                        View Design
                                    </a>

                                    <a
                                        class="user-button"
                                        href="<?= htmlspecialchars($designLink, ENT_QUOTES, 'UTF-8') ?>">
                                       	Customize a Product
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>
</section>

<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
