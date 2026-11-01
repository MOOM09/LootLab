<?php

/**
 * Expects: $message (string). Used by ProductController and Admin\CategoryController.
 */
$message = $message ?? 'Page not found.';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not found - LootLab</title>
</head>

<body>
    <h1>Not found</h1>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
</body>

</html>