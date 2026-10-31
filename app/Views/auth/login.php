<?php

/** @var string $appUrl */
/** @var string $csrfToken */

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — LootLab</title>
</head>

<body>
    <h1>Login</h1>

    <form
        id="login-form"
        method="post"
        action="<?= htmlspecialchars($appUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            autocomplete="username"
            required>

        <label for="password">Password</label>
        <input
            id="password"
            type="password"
            name="password"
            autocomplete="current-password"
            required>

        <button type="submit">Login</button>
    </form>

    <a href="<?= htmlspecialchars(
                    $appUrl . '/auth/google',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">
        Continue with Google
    </a>

    <div id="messages" role="alert" aria-live="polite"></div>

    <a href="<?= htmlspecialchars($appUrl . '/register', ENT_QUOTES, 'UTF-8') ?>">
        Create an account
    </a>

    <br>
    <a href="<?= htmlspecialchars(
                    $appUrl . '/forgot-password',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">
        Forgot password?
    </a>

    <script>
        const form = document.getElementById('login-form');
        const messages = document.getElementById('messages');
        const button = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            messages.replaceChildren();
            button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams(new FormData(form))
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    window.location.href = result.data.redirect;
                    return;
                }

                const errors = result.errors ?
                    Object.values(result.errors).flat() : [result.message || 'Login failed.'];

                for (const error of errors) {
                    const paragraph = document.createElement('p');
                    paragraph.textContent = String(error);
                    messages.appendChild(paragraph);
                }
            } catch (error) {
                messages.textContent =
                    'Unable to complete login. Please try again.';
            } finally {
                button.disabled = false;
            }
        });
    </script>
</body>

</html>