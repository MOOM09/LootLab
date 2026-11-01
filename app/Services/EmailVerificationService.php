<?php

final class EmailVerificationService
{
    public function __construct(
        private PDO $db,
        private MailService $mail,
        private string $appUrl
    ) {
        $this->appUrl = rtrim($appUrl, '/');
    }

    public function sendForUser(int $userId, string $email, string $name): void
    {
        // Store a slow hash, not the six-digit code. A new code invalidates the old one.
        $code = (string) random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('Unable to prepare verification code.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at, attempts)
             VALUES (:uid, :hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE), 0)
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash),
                                     expires_at = VALUES(expires_at),
                                     attempts = 0,
                                     created_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([':uid' => $userId, ':hash' => $hash]);

        $safeName = trim(preg_replace('/[\r\n]+/', ' ', $name)) ?: 'there';
        $this->mail->send(
            $email,
            'Your LootHub verification code',
            "Hi {$safeName},\n\nWelcome to LootHub!\n\n"
            . "Your email verification code is: {$code}\n\n"
            . "Enter this code in LootHub to activate your account.\n"
            . "The code expires in 10 minutes and can be used once.\n\n"
            . "If you did not create this account, you can ignore this email."
        );
    }

    // Return verified user ID or null. Atomic attempt tracking prevents code guessing.
    public function confirmCode(string $email, string $code): ?int
    {
        if (!preg_match('/\A[0-9]{6}\z/D', $code)) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT t.user_id, t.token_hash, t.attempts
                 FROM email_verification_tokens t
                 INNER JOIN users u ON u.id = t.user_id
                 WHERE u.email = :email AND u.email_verified_at IS NULL
                   AND u.is_active = 1 AND u.password IS NOT NULL
                   AND t.expires_at > UTC_TIMESTAMP()
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([':email' => $email]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$record) {
                $this->db->rollBack();
                return null;
            }

            $userId = (int) $record['user_id'];
            $attempts = (int) $record['attempts'];
            if ($attempts >= 5) {
                $this->db->rollBack();
                return null;
            }

            if (!password_verify($code, (string) $record['token_hash'])) {
                $stmt = $this->db->prepare(
                    'UPDATE email_verification_tokens SET attempts = attempts + 1
                     WHERE user_id = :uid'
                );
                $stmt->execute([':uid' => $userId]);
                $this->db->commit();
                return null;
            }

            $stmt = $this->db->prepare(
                'UPDATE users SET email_verified_at = UTC_TIMESTAMP()
                 WHERE id = :uid AND email_verified_at IS NULL AND is_active = 1'
            );
            $stmt->execute([':uid' => $userId]);
            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return null;
            }

            $stmt = $this->db->prepare(
                'DELETE FROM email_verification_tokens WHERE user_id = :uid'
            );
            $stmt->execute([':uid' => $userId]);
            $this->db->commit();
            return $userId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
