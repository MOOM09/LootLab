<?php
$reviewProductId = (int) ($product['id'] ?? 0);
$reviewUrl = $userAppUrl . '/reviews?product_id=' . $reviewProductId;
?>
<section
    class="user-review-section"
    aria-labelledby="user-reviews-heading"
    data-user-review-section
    data-reviews-url="<?= htmlspecialchars($reviewUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-product-id="<?= $reviewProductId ?>"
    data-user-id="<?= isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : '' ?>">
    <div class="user-section-heading">
        <div>
            <p class="user-eyebrow">From the community</p>
            <h2 id="user-reviews-heading">Reviews</h2>
            <p data-user-review-status>Reviews will appear here when available.</p>
        </div>
    </div>
    <div class="user-review-list" data-user-review-list></div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <form
            class="user-review-form"
            action="<?= htmlspecialchars($userAppUrl . '/reviews', ENT_QUOTES, 'UTF-8') ?>"
            method="post"
            data-user-review-form
            data-user-async-form>
            <h3>Share your experience</h3>
            <input type="hidden" name="csrf_token" value="">
            <input type="hidden" name="product_id" value="<?= $reviewProductId ?>">
            <div class="user-field">
                <label for="user-review-rating">Rating</label>
                <select class="user-select" id="user-review-rating" name="rating" required>
                    <option value="">Choose a rating</option>
                    <option value="5">5 — Excellent</option>
                    <option value="4">4 — Great</option>
                    <option value="3">3 — Good</option>
                    <option value="2">2 — Fair</option>
                    <option value="1">1 — Poor</option>
                </select>
            </div>
            <div class="user-field">
                <label for="user-review-comment">Your review</label>
                <textarea class="user-textarea" id="user-review-comment" name="comment" maxlength="5000" required></textarea>
            </div>
            <p class="user-form-message" data-user-form-message role="status" aria-live="polite"></p>
            <div class="user-form-actions">
                <button class="user-button" type="submit" data-user-submit>Post review</button>
            </div>
        </form>
    <?php else: ?>
        <p class="user-status-note">
            <span aria-hidden="true">i</span>
            <span><a href="<?= htmlspecialchars($userAppUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Log in</a> to share a review.</span>
        </p>
    <?php endif; ?>
</section>
