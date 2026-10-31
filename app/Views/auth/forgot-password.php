<?php
/** @var string $csrfToken */
/** @var string $appUrl */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password — LootLab</title>
</head>
<body>
    <h1>Forgot password?</h1>
    <p>Enter your account email to request a password reset link.</p>

    <!-- Submit to the current page using POST. -->
    <form method="post">
        <!-- Protect the request with the current session's CSRF token. -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="email">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            maxlength="255"
            autocomplete="email"
            required
        >

        <button type="submit">Send reset link</button>
    </form>
    <p>
        Remember your password?
        <a href="<?= htmlspecialchars($appUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Back to login</a>
    </p>
</body>
</html>
