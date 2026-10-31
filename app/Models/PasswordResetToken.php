<?php

class PasswordResetToken
{
    // Usage: save when requesting a link, then findValid when opening it.
    // On password submission: begin a transaction, lock and consume the token, update the password, then commit.
    // This model handles database access only; token generation, email delivery, and orchestration are not implemented here.
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Share this connection with User so both operations use the same transaction.
        $this->connection = $connection;
    }

    // Step 1: Store the token hash when a reset link is requested; never store the raw token.
    public function save(
        int $userId,
        string $tokenHash
    ): void {
        // Expire after 30 minutes in UTC; replace any previous request to invalidate its link.
        $stmt = $this->connection->prepare(
            'INSERT INTO password_reset_tokens
            (user_id, token_hash, expires_at)
         VALUES
            (:user_id, :token_hash,
             DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE))
         ON DUPLICATE KEY UPDATE
            token_hash = VALUES(token_hash),
            expires_at = VALUES(expires_at),
            created_at = CURRENT_TIMESTAMP'
        );

        // Execute the prepared statement with the supplied user ID and token hash.
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
        ]);
    }

    // Step 2: When the link is opened, find a matching unexpired hash without consuming it.
    public function findValid(string $tokenHash): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT user_id, token_hash, expires_at
         FROM password_reset_tokens
         WHERE token_hash = :token_hash
           AND expires_at > UTC_TIMESTAMP()
         LIMIT 1'
        );

        $stmt->execute([
            ':token_hash' => $tokenHash,
        ]);

        // Fetch the matching record, if any.
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        // Null means the token is missing, expired, replaced, or already consumed.
        return $token === false ? null : $token;
    }

    // Step 4: Consume the token inside the password-reset transaction, not when the link is opened.
    public function deleteValid(
        int $userId,
        string $tokenHash
    ): bool {
        // Delete only the matching valid hash for this user so a newer request is not removed.
        $stmt = $this->connection->prepare(
            'DELETE FROM password_reset_tokens
         WHERE user_id = :user_id
           AND token_hash = :token_hash
           AND expires_at > UTC_TIMESTAMP()'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
        ]);

        // Require true to continue; roll back if consumption or the password update fails.
        return $stmt->rowCount() === 1;
    }

    // Step 3: On password submission, recheck the token and lock its row until the transaction ends.
    public function findValidForUpdate(string $tokenHash): ?array
    {
        // A row lock requires an active transaction on this connection.
        if (!$this->connection->inTransaction()) {
            throw new LogicException(
                'An active transaction is required.'
            );
        }

        // FOR UPDATE makes another request locking the same row wait for the first transaction to finish.
        $stmt = $this->connection->prepare(
            'SELECT user_id, token_hash, expires_at
         FROM password_reset_tokens
         WHERE token_hash = :token_hash
           AND expires_at > UTC_TIMESTAMP()
         LIMIT 1
         FOR UPDATE'
        );

        $stmt->execute([
            ':token_hash' => $tokenHash,
        ]);

        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return the locked record, or null to reject an invalid token.
        return $token === false ? null : $token;
    }
}
