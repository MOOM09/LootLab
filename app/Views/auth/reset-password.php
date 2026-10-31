<?php
/** @var string $token */
/** @var string $csrfToken */
/** @var string $appUrl */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Reset password — LootLab</title>
</head>
<body>
    <h1>Choose a new password</h1>

    <!-- Submit the new password to the current reset page. -->
    <form method="post">
        <!-- Protect the form submission against CSRF. -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
        >

        <!-- Identify the reset request without asking for an email again. -->
        <input
            type="hidden"
            name="token"
            value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
        >

        <p>
            Use 8–72 bytes, including uppercase, lowercase,
            and a number.
        </p>

        <label for="password">New password</label>
        <input
            id="password"
            type="password"
            name="password"
            autocomplete="new-password"
            minlength="8"
            required
        >

        <label for="password_confirmation">Confirm password</label>
        <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            minlength="8"
            required
        >

        <button type="submit">Reset password</button>
    </form>
    <p>
        Remember your password?
        <a href="<?= htmlspecialchars($appUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Back to login</a>
    </p>
</body>
</html>
