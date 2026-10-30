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
            ':favoriteId' => $favoriteId,
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
        // Start a database transaction.
        //? Guest favorites → copy to user favorites → delete guest favorites
        //* Two choices: 
        // Everything worked → commit() saves the changes.
        // Something failed → rollBack() undoes this operation’s database changes.
        $this->connection->beginTransaction();

        // Merge steps
        try {
            // 2. Find the guest collection 
            $guestFavorite = $this->findBySession($sessionId);
            if (!$guestFavorite) {
                $this->connection->commit();
                return;
            }

            $userFavorite = $this->findByUser($userId);
            if ($userFavorite === null) {
                $userFavoriteId = $this->createForUser($userId);
            } else {
                $userFavoriteId = (int) $userFavorite['fav_id'];
            }

            $guestFavoriteId = (int) $guestFavorite['fav_id'];

            $guestItems = $this->getItems($guestFavoriteId);

            foreach ($guestItems as $item) {
                $productId = (int) $item['product_id'];

                if (!$this->contains($userFavoriteId, $productId))
                    $this->addItem($userFavoriteId, $productId);
            }
            $stmt = $this->connection->prepare(
                'DELETE FROM favorites
                WHERE fav_id = :fav_id AND session_id = :session_id'
            );

            $stmt->execute([
                ':fav_id' => $guestFavoriteId,
                ':session_id' => $sessionId,
            ]);

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
