<?php

class PasswordResetController extends Controller
{
    private PasswordResetService $resetService;
    private PasswordResetRateLimit $rateLimit;
    private string $appUrl;

    public function __construct(array $config)
    {
        $this->appUrl = rtrim($config['app_url'], '/');
        // 1. Open a database connection.
        $database = new Database($config['database']);

        // Prepare database-backed limits before processing reset requests.
        $this->rateLimit = new PasswordResetRateLimit(
            $database->getConnection()
        );

        // 2. Configure the email sender.
        $mail = new MailService($config['mail']);

        // 3. Prepare the service that coordinates password recovery.
        $this->resetService = new PasswordResetService(
            $database->getConnection(),
            $mail,
            $config['app_url']
        );
    }

    public function showForgot(): void
    {
        // 1. Avoid caching the form and its session-specific CSRF token.
        header('Cache-Control: no-store');

        // 2. Display the email form with a CSRF token.
        $this->view('auth/forgot-password', [
            'csrfToken' => $this->csrfToken(),
            'appUrl' => $this->appUrl,
        ]);
    }

    public function showReset(): void
    {
        // 1. Prevent caching and avoid leaking the token through referrers.
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');

        // 2. Read the token from the email link.
        $token = $_GET['token'] ?? '';

        // 3. Reject missing, malformed, expired, or consumed tokens.
        if (
            !is_string($token) ||
            !$this->resetService->isValidToken($token)
        ) {
            Response::error('Invalid or expired reset link.', 400);
        }

        // 4. Display the password form without consuming the token.
        $this->view('auth/reset-password', [
            'token' => $token,
            'csrfToken' => $this->csrfToken(),
            'appUrl' => $this->appUrl,
        ]);
    }

    public function requestReset(): void
    {
        // 1. Read form or JSON input and validate CSRF.
        $input = $this->requestData();
        $this->validateCsrf($input);

        header('Cache-Control: no-store');

        // 2. Validate the email address.
        $email = $input['email'] ?? '';

        if (
            !is_string($email) ||
            strlen(trim($email)) > 255 ||
            !filter_var(trim($email), FILTER_VALIDATE_EMAIL)
        ) {
            Response::error('Enter a valid email address.', 422);
        }

        $email = trim($email);

        // 3. Limit requests from the connecting IP.
        // Do not trust client-supplied forwarding headers here.
        $ipKey = hash(
            'sha256',
            'reset-request-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );

        if (!$this->rateLimit->allow($ipKey, 20)) {
            Response::error('Too many requests. Try again later.', 429);
        }

        // 4. Limit requests for this email, even if no account exists.
        $emailKey = hash(
            'sha256',
            'reset-request-email:' . mb_strtolower($email, 'UTF-8')
        );

        $allowed = $this->rateLimit->allow($emailKey, 3);

        // 5. Request delivery only when the email limit allows it.
        if ($allowed) {
            try {
                $this->resetService->requestReset($email);
            } catch (Throwable $exception) {
                // Do not expose account details, credentials, or reset tokens.
                error_log(
                    'Password reset request failed: '
                        . get_class($exception)
                );
            }
        }

        // 6. Use the same public response regardless of account existence,
        // email throttling, or delivery failure.
        Response::success(
            null,
            200,
            'If this account is eligible, a reset link will be emailed.'
        );
    }

    public function resetPassword(): void
    {
        // 1. Read the submitted data and validate CSRF.
        $input = $this->requestData();
        $this->validateCsrf($input);

        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');

        // 2. Limit reset submissions from the connecting IP.
        $rateKey = hash(
            'sha256',
            'reset-submit-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );

        if (!$this->rateLimit->allow($rateKey, 20)) {
            Response::error('Too many requests. Try again later.', 429);
        }

        // 3. Accept strings only; the service checks their contents.
        $token = $input['token'] ?? '';
        $password = $input['password'] ?? '';
        $confirmation = $input['password_confirmation'] ?? '';

        if (
            !is_string($token) ||
            !is_string($password) ||
            !is_string($confirmation)
        ) {
            Response::error('Invalid input.', 422);
        }

        try {
            // 4. Consume the token and update the password atomically.
            $this->resetService->resetPassword(
                $token,
                $password,
                $confirmation
            );
        } catch (InvalidArgumentException $exception) {
            // 5. Report validation errors without exposing internal details.
            Response::error($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            // 6. Log the failure type without passwords or tokens.
            error_log(
                'Password reset failed: ' . get_class($exception)
            );

            Response::error(
                'Unable to reset your password. Please try again later.',
                500
            );
        }

        // 7. Require a fresh login with the new password.
        Response::success(
            null,
            200,
            'Password reset successfully. Please log in.'
        );
    }
}
