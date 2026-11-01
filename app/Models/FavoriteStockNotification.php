<?php

class FavoriteStockNotification
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function createPending(
        int $userId,
        int $productId,
        string $notificationType,
        int $stockQuantity,
        string $stockEventKey
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO favorite_stock_notifications (
                user_id,
                product_id,
                notification_type,
                stock_quantity,
                stock_event_key,
                status
             ) VALUES (
                :user_id,
                :product_id,
                :notification_type,
                :stock_quantity,
                :stock_event_key,
                \'pending\'
             )
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );

        $statement->execute([
            'user_id' => $userId,
            'product_id' => $productId,
            'notification_type' => $notificationType,
            'stock_quantity' => $stockQuantity,
            'stock_event_key' => $stockEventKey,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function getPendingNotifications(int $limit = 100): array
    {
        if ($limit < 1 || $limit > 500) {
            throw new InvalidArgumentException(
                'The pending notification limit must be between 1 and 500.'
            );
        }

        $statement = $this->connection->prepare(
            'SELECT
                id,
                user_id,
                product_id,
                notification_type,
                stock_quantity,
                stock_event_key,
                status,
                failure_reason,
                created_at,
                updated_at,
                processed_at
             FROM favorite_stock_notifications
             WHERE status = \'pending\'
             ORDER BY id ASC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markPendingAsSent(int $notificationId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE favorite_stock_notifications
             SET status = \'sent\',
                 failure_reason = NULL,
                 processed_at = CURRENT_TIMESTAMP
             WHERE id = :id AND status = \'pending\''
        );
        $statement->execute(['id' => $notificationId]);

        return $statement->rowCount() > 0;
    }

    public function markPendingAsFailed(
        int $notificationId,
        string $failureReason
    ): bool {
        if (trim($failureReason) === '') {
            throw new InvalidArgumentException(
                'A failure reason is required.'
            );
        }

        $statement = $this->connection->prepare(
            'UPDATE favorite_stock_notifications
             SET status = \'failed\',
                 failure_reason = :failure_reason,
                 processed_at = CURRENT_TIMESTAMP
             WHERE id = :id AND status = \'pending\''
        );
        $statement->execute([
            'failure_reason' => $failureReason,
            'id' => $notificationId,
        ]);

        return $statement->rowCount() > 0;
    }
}
