<?php

final class EmailVerificationController extends Controller
{
    private PDO $db;
    private EmailVerificationService $verification;
    private PasswordResetRateLimit $rateLimit;
    private string $appUrl;
    private Database $database;

    public function __construct(array $config)
    {
        $this->appUrl = rtrim($config['app_url'], '/');
        $this->database = new Database($config['database']);
        $this->db = $this->database->getConnection();
        $this->verification = new EmailVerificationService(
            $this->db, new MailService($config['mail']), $this->appUrl
        );
        $this->rateLimit = new PasswordResetRateLimit($this->db);
    }

    public function pending(): void
    {
        header('Cache-Control: no-store');
        $this->view('auth/verify-email-pending', [
            'appUrl' => $this->appUrl,
            'csrfToken' => $this->csrfToken(),
            'email' => (string) ($_SESSION['pending_verification_email'] ?? ''),
        ]);
    }

    // Old email links are retired; show the code-entry form instead.
    public function verify(): void
    {
        $this->redirect($this->appUrl . '/verify-email/pending');
    }

    public function confirm(): void
    {
        $data = $this->requestData();
        $this->validateCsrf($data);
        header('Cache-Control: no-store');

        $email = $data['email'] ?? null;
        $code = $data['code'] ?? null;
        if (!is_string($email) || !is_string($code) ||
            strlen($email) > 255 || !filter_var(trim($email), FILTER_VALIDATE_EMAIL) ||
            !preg_match('/\A[0-9]{6}\z/D', trim($code))) {
            Response::error('Enter a valid email and 6-digit code.', 422);
        }
        $email = trim($email);
        $code = trim($code);

        // Rate limit guessing, in addition to the five-attempt per-code limit.
        $ipKey = hash('sha256', 'verify-otp-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $emailKey = hash('sha256', 'verify-otp-email:' . mb_strtolower($email, 'UTF-8'));
        if (!$this->rateLimit->allow($ipKey, 30) || !$this->rateLimit->allow($emailKey, 10)) {
            Response::error('Too many attempts. Please try again in 15 minutes.', 429);
        }

        try {
            $userId = $this->verification->confirmCode($email, $code);
        } catch (Throwable $e) {
            error_log('Email OTP confirmation failed: ' . get_class($e));
            Response::error('Unable to verify your email right now. Please try again.', 500);
        }

        if ($userId === null) {
            Response::error('Incorrect or expired code. Check your email or request a new code.', 422);
        }

        // Only auto-login in the browser that created this specific account.
        // Resend from another device can verify the email, but must log in with password.
        $registrationUserId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);
        unset($_SESSION['pending_verification_email'], $_SESSION['pending_verification_user_id']);
        if ($registrationUserId !== $userId || isset($_SESSION['user_id'])) {
            Response::success(['redirect' => $this->appUrl . '/login'], 200,
                'Email verified successfully! You can now log in.');
        }

        $stmt = $this->db->prepare(
            'SELECT id, is_active, session_version, email_verified_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || (int) $user['is_active'] !== 1 || empty($user['email_verified_at'])) {
            Response::error('Account is unavailable. Please try logging in.', 403);
        }

        // Match normal login: preserve guest favorites and cart when possible.
        try {
            $favorite = new Favorite($this->db);
            $favorite->mergeGuestIntoUser(session_id(), $userId);
        } catch (Throwable $e) {
            error_log('Email OTP favorites merge failed: ' . get_class($e));
        }
        if (!empty($_SESSION['guest_cart'])) {
            try {
                $merge = new CartMergeService($this->database);
                $merge->mergeGuestCart($userId);
                unset($_SESSION['guest_cart']);
            } catch (Throwable $e) {
                error_log('Email OTP cart merge failed: ' . get_class($e));
            }
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_version'] = (int) $user['session_version'];
        Response::success(['redirect' => $this->appUrl . '/products'], 200,
            'Email verified! Welcome to LootHub.');
    }

    public function resend(): void
    {
        $data = $this->requestData();
        $this->validateCsrf($data);
        header('Cache-Control: no-store');
        $email = $data['email'] ?? '';
        if (!is_string($email) || strlen($email) > 255 || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            Response::error('Enter a valid email address.', 422);
        }
        $email = trim($email);

        $ipKey = hash('sha256', 'verify-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $emailKey = hash('sha256', 'verify-email:' . mb_strtolower($email, 'UTF-8'));
        if (!$this->rateLimit->allow($ipKey, 20) || !$this->rateLimit->allow($emailKey, 3)) {
            Response::error('Too many requests. Try again in 15 minutes.', 429);
        }

        $stmt = $this->db->prepare(
            'SELECT id, name, email FROM users
             WHERE email = :email AND email_verified_at IS NULL
               AND password IS NOT NULL AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            try {
                $this->verification->sendForUser((int) $user['id'], $user['email'], $user['name']);
            } catch (Throwable $e) {
                error_log('Verification resend failed: ' . get_class($e));
            }
        }
        Response::success(null, 200, 'If this account needs verification, a new code will be sent.');
    }
}
