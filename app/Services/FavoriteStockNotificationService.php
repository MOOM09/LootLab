<?php

class FavoriteStockNotificationService
{
    private Favorite $favorite;
    private FavoriteStockNotification $notification;

    public function __construct(PDO $connection)
    {
        $this->favorite = new Favorite($connection);
        $this->notification = new FavoriteStockNotification($connection);
    }

    public function handleStockChange(
        int $productId,
        int $previousStock,
        int $newStock,
        string $stockEventKey
    ): void {
        if ($previousStock === $newStock) {
            return;
        }

        $notificationTypes = [];

        if ($previousStock > 5 && $newStock <= 5) {
            $notificationTypes[] = 'stock_reached_5';
        }

        if ($previousStock > 1 && $newStock <= 1) {
            $notificationTypes[] = 'stock_reached_1';
        }

        if ($previousStock === 0 && $newStock > 0) {
            $notificationTypes[] = 'stock_restocked';
        }

        if ($notificationTypes === []) {
            return;
        }

        $userIds = $this->favorite->findUserIdsByProduct($productId);

        foreach ($userIds as $userId) {
            foreach ($notificationTypes as $notificationType) {
                $this->notification->createPending(
                    $userId,
                    $productId,
                    $notificationType,
                    $newStock,
                    $stockEventKey
                );
            }
        }
    }

    public function getPendingNotifications(int $limit = 100): array
    {
        return $this->notification->getPendingNotifications($limit);
    }

    public function markNotificationAsSent(int $notificationId): bool
    {
        return $this->notification->markPendingAsSent($notificationId);
    }

    public function markNotificationAsFailed(
        int $notificationId,
        string $failureReason
    ): bool {
        return $this->notification->markPendingAsFailed(
            $notificationId,
            $failureReason
        );
    }

    public function createStockEventKey(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
