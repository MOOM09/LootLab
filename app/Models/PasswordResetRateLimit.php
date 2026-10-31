<?php

class PasswordResetRateLimit
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Store the connection used to track reset attempts.
        $this->connection = $connection;
    }
    public function allow(string $rateKey, int $limit): bool
    {
        // 1. Count and check the attempt in one transaction.
        $this->connection->beginTransaction();

        try {
            // 2. Start a 15-minute window for a new key.
            // For an existing key, reset an expired window or increment its count.
            // Keep blocked counts capped at one above the limit.
            $stmt = $this->connection->prepare(
                'INSERT INTO password_reset_rate_limits
                (rate_key, attempts, expires_at)
             VALUES
                (:rate_key, 1,
                 DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE))
             ON DUPLICATE KEY UPDATE
                attempts = IF(
                    expires_at <= UTC_TIMESTAMP(),
                    1,
                    LEAST(attempts + 1, :blocked_count)
                ),
                expires_at = IF(
                    expires_at <= UTC_TIMESTAMP(),
                    DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE),
                    expires_at
                )'
            );

            $stmt->execute([
                ':rate_key' => $rateKey,
                ':blocked_count' => $limit + 1,
            ]);

            // 3. Read the count while this transaction still holds the row lock.
            $stmt = $this->connection->prepare(
                'SELECT attempts
             FROM password_reset_rate_limits
             WHERE rate_key = :rate_key'
            );

            $stmt->execute([
                ':rate_key' => $rateKey,
            ]);

            $attempts = (int) $stmt->fetchColumn();

            // 4. Save the attempt even when it exceeds the limit.
            $this->connection->commit();

            // 5. Allow only attempts within the configured limit.
            return $attempts <= $limit;
        } catch (Throwable $exception) {
            // 6. Undo an incomplete database operation.
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }
}
