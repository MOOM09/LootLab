<?php

/**
 * Expects: $reviews (array), $productId (int), $appUrl (string), $csrfToken (string).
 */
$appUrl = rtrim($appUrl ?? '', '/');
$userId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - LootLab</title>
</head>

<body>
    <h1>Product reviews</h1>

    <?php if (empty($reviews)): ?>
        <p>No reviews yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($reviews as $review): ?>
                <li>
                    <strong><?= (int) $review['rating'] ?>/5</strong>
                    <?= nl2br(htmlspecialchars((string) $review['comment'], ENT_QUOTES, 'UTF-8')) ?>

                    <?php if ($userId !== null && (int) $review['user_id'] === (int) $userId): ?>
                        <form action="<?= htmlspecialchars($appUrl . '/reviews/delete', ENT_QUOTES, 'UTF-8') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                            <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
                            <button type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($userId !== null): ?>
        <h2>Write a review</h2>
        <form action="<?= htmlspecialchars($appUrl . '/reviews', ENT_QUOTES, 'UTF-8') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="product_id" value="<?= (int) $productId ?>">

            <label for="rating">Rating (1-5)</label>
            <input type="number" id="rating" name="rating" min="1" max="5" required>

            <label for="comment">Comment</label>
            <textarea id="comment" name="comment" maxlength="5000" required></textarea>

            <button type="submit">Submit review</button>
        </form>
    <?php else: ?>
        <p><a href="<?= htmlspecialchars($appUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Log in</a> to write a review.</p>
    <?php endif; ?>
</body>

</html>