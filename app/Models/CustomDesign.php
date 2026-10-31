<?php

class CustomDesign
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createCompleted(
        int $userId,
        string $resultPath
    ): int {
        $statement = $this->db->prepare(
            "INSERT INTO custom_designs
                (user_id, template_id, face_image, result_image, status)
             VALUES
                (:user_id, NULL, NULL, :result_image, 'completed')"
        );

        $statement->execute([
            'user_id' => $userId,
            'result_image' => $resultPath,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByUser(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, result_image, created_at
             FROM custom_designs
             WHERE user_id = :user_id
               AND status = 'completed'
               AND result_image IS NOT NULL
             ORDER BY created_at DESC, id DESC"
        );

        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findForUser(
        int $designId,
        int $userId
    ): ?array {
        $statement = $this->db->prepare(
            "SELECT id, user_id, result_image, status, created_at
             FROM custom_designs
             WHERE id = :id
               AND user_id = :user_id
               AND status = 'completed'
             LIMIT 1"
        );

        $statement->execute([
            'id' => $designId,
            'user_id' => $userId,
        ]);

        $design = $statement->fetch(PDO::FETCH_ASSOC);

        return $design === false ? null : $design;
    }
}