<?php

/**
 * Expects: $categories (array). Also rendered by CategoryController::destroy() with $error.
 */
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - LootLab Admin</title>
</head>

<body>
    <h1>Categories</h1>

    <?php if ($error !== null): ?>
        <p style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (empty($categories)): ?>
        <p>No categories yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($categories as $category): ?>
                <li>
                    <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= (int) ($category['subcategories_count'] ?? 0) ?> subcategories)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>

</html>