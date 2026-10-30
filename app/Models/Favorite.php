<?php

class Favorite
{
    private PDO $connection;
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function findByUser(int $userId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT fav_id, user_id, session_id
         FROM favorites
         WHERE user_id = :user_id'
        );

        $stmt->execute([
            ':user_id' => $userId,
        ]);

        $favorite = $stmt->fetch();

        return $favorite === false ? null : $favorite;
    }

    public function createForUser(int $userId): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO favorites (user_id)
         VALUES (:user_id)'
        );

        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function getItems(int $favoriteId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM favorite_items WHERE fav_id = :favorite_id'
        );

        $stmt->execute([
            ':favorite_id' => $favoriteId,
        ]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $items;
    }

    public function contains(int $favoriteId, int $productId): bool
    {
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM favorite_items WHERE fav_id = :favoriteId AND product_id = :product_id'
        );

        $stmt->execute([
            '
        :favoriteId' => $favoriteId,
            ':product_id' => $productId
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function addItem(int $favoriteId, int $productId): bool
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO favorite_items (fav_id, product_id)
         VALUES (:fav_id, :product_id)'
        );

        return $stmt->execute([
            ':fav_id' => $favoriteId,
            ':product_id' => $productId,
        ]);
    }


    public function removeItem(int $favoriteId, int $productId): bool
    {
        $stmt = $this->connection->prepare(
            'DELETE FROM favorite_items
         WHERE fav_id = :fav_id AND product_id = :product_id'
        );

        $stmt->execute([
            ':fav_id' => $favoriteId,
            ':product_id' => $productId,
        ]);

        return $stmt->rowCount() > 0;
    }

    // ? Guest collections are owned by a server-generated session ID.
    // PHP provides the current session ID through: session_id ();
    public function findBySession(string $sessionId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT fav_id, user_id, session_id
         FROM favorites
         WHERE session_id = :session_id'
        );

        $stmt->execute([
            ':session_id' => $sessionId,
        ]);

        $favorite = $stmt->fetch();

        return $favorite === false ? null : $favorite;
    }

    public function createForSession(string $sessionId): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO favorites (session_id)
         VALUES (:session_id)'
        );

        $stmt->execute([
            ':session_id' => $sessionId,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    // TODO: Merge without duplicate products, then remove the old guest collection.
    // Call during successful login/registration using the guest ID captured before session regeneration.
    public function mergeGuestIntoUser(string $sessionId, int $userId): void
    {
        // 1. Start a database transaction.

        // 2. Find the guest collection using findBySession($sessionId).
        // If none exists, commit and return.

        // 3. Find the user's collection using findByUser($userId).
        // If none exists, create it using createForUser($userId).

        // 4. Get the guest's products using getItems($guestFavoriteId).

        // 5. For each guest product:
        // Check whether the user's collection already contains it.
        // If it does not, add it to the user's collection.
        // Keep the unique constraint as the final protection against duplicates.

        // 6. Delete the guest collection from the favorites table.
        // Its favorite_items are automatically deleted by ON DELETE CASCADE.

        // 7. Commit the transaction.

        // 8. On an exception, roll back the transaction and rethrow the exception.
    }
}
