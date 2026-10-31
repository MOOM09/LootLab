<?php

/** @var array $user */
/** @var string $appUrl */
/** @var string $csrfToken */
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — LootLab</title>
</head>

<body>

        <!--
    Data  comes from ProfileController::index()
    Inside view(), extract($data) creates $user

    Session user_id → User model → Database row
    → Controller passes it to view → $user['name']
        -->
        <h1> <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>'s Profile</h1>

        <form id="logout-form" method="post"
              action="<?= htmlspecialchars($appUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Logout</button>
        </form>

        <p id="logout-message" role="alert" aria-live="polite"></p>

        <script>
            const logoutForm = document.getElementById('logout-form');
            const logoutMessage = document.getElementById('logout-message');
            const logoutButton = logoutForm.querySelector('button');

            logoutForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                logoutButton.disabled = true;
                logoutMessage.textContent = '';

                try {
                    const response = await fetch(logoutForm.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new URLSearchParams(new FormData(logoutForm))
                    });
                    const result = await response.json();

                    if (response.ok && result.success) {
                        window.location.assign(result.data.redirect);
                        return;
                    }

                    logoutMessage.textContent = result.message || 'Logout failed.';
                } catch (error) {
                    logoutMessage.textContent = 'Unable to log out. Please try again.';
                } finally {
                    logoutButton.disabled = false;
                }
            });
        </script>
</body>

</html>
