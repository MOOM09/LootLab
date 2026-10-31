<?php

/**
 * Expects: $category (row), $subcategories (array).
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?> - LootLab Admin</title>
</head>

<body>
    <h1><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></h1>

    <h2>Subcategories</h2>

    <?php if (empty($subcategories)): ?>
        <p>No subcategories.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($subcategories as $subcategory): ?>
                <li><?= htmlspecialchars($subcategory['name'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>

</html>