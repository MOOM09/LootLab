<h1>Welcome To Products Page</h1>
<?php if ($isLoggedIn): ?>
    <form id="logout-form" method="post" action="<?= htmlspecialchars($appUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
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
                    window.location.href = result.data.redirect;
                    return;
                }

                logoutMessage.textContent = result.message || 'Unable to log out. Please try again.';
            } catch (error) {
                logoutMessage.textContent = 'Unable to log out. Please try again.';
            } finally {
                logoutButton.disabled = false;
            }
        });
    </script>
<?php endif; ?>
