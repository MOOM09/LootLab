<?php

class PasswordResetService
{
    private PDO $connection;
    private User $user;
    private PasswordResetToken $tokens;
    private MailService $mail;
    private string $appUrl;

    public function __construct(
        PDO $connection,
        MailService $mail,
        string $appUrl
    ) {
        // Share one connection so password and token changes
        // can be committed or rolled back together.
        $this->connection = $connection;
        $this->user = new User($connection);
        $this->tokens = new PasswordResetToken($connection);

        // Use the configured mail sender and trusted application URL.
        $this->mail = $mail;
        $this->appUrl = rtrim($appUrl, '/');
    }

    public function requestReset(string $email): void
    {
        // 1. Find the account associated with this email.
        $user = $this->user->findByEmail($email);

        // 2. Only active accounts with a local password can use this flow.
        // Return silently so the controller can show the same public message.
        if (
            $user === null ||
            (int) $user['is_active'] !== 1 ||
            !is_string($user['password'])
        ) {
            return;
        }

        // 3. Generate a random token for the email link.
        $token = bin2hex(random_bytes(32));

        // 4. Store only its SHA-256 hash in the database.
        $this->tokens->save(
            (int) $user['id'],
            hash('sha256', $token)
        );

        // 5. Build the link using the trusted application URL.
        $resetUrl = $this->appUrl
            . '/reset-password?token='
            . rawurlencode($token);

        // 6. Send the raw token only through the email link.
        $this->mail->send(
            $user['email'],
            'Reset your LootLab password',
            "Open this link to reset your password:\n\n"
                . $resetUrl
                . "\n\nIf you did not request this, ignore this email."
        );
    }

    public function resetPassword(
        string $token,
        string $password,
        string $confirmation
    ): void {
        // 1. Validate the token format.
        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            throw new InvalidArgumentException('Invalid reset link.');
        }

        // 2. Validate the new password and confirmation.
        if (
            $password !== $confirmation ||
            strlen($password) < 8 ||
            strlen($password) > 72 ||
            str_contains($password, "\0") ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            throw new InvalidArgumentException(
                'Passwords must match and contain 8–72 bytes, '
                    . 'including uppercase, lowercase, and a number.'
            );
        }

        // 3. Prepare the hashes before locking database records.
        $tokenHash = hash('sha256', $token);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 4. Start one transaction for all database changes.
        $this->connection->beginTransaction();

        try {
            // 5. Recheck validity and lock the token against concurrent use.
            $record = $this->tokens->findValidForUpdate($tokenHash);

            if ($record === null) {
                throw new InvalidArgumentException(
                    'Invalid or expired reset link.'
                );
            }

            $userId = (int) $record['user_id'];

            // 6. Consume the token; a failed transaction will restore it.
            if (!$this->tokens->deleteValid($userId, $tokenHash)) {
                throw new InvalidArgumentException(
                    'Invalid or expired reset link.'
                );
            }

            // 7. Save the password and increment the session version.
            if (!$this->user->updatePassword($userId, $passwordHash)) {
                throw new InvalidArgumentException(
                    'This account cannot reset its password.'
                );
            }

            // 8. Save both changes together.
            $this->connection->commit();
        } catch (Throwable $exception) {
            // 9. Undo both changes if any step fails.
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    public function isValidToken(string $token): bool
    {
        // 1. Reject malformed tokens before querying the database.
        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return false;
        }

        // 2. Hash the token received from the email link.
        $tokenHash = hash('sha256', $token);

        // 3. Check whether its stored hash exists and has not expired.
        return $this->tokens->findValid($tokenHash) !== null;
    }
}
