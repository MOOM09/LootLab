<?php

class AiConversation
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, created_at, updated_at
             FROM ai_conversations
             WHERE user_id = ?
             ORDER BY updated_at DESC, id DESC'
        );

        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getMessages(int $conversationId, int $userId): array
    {
        $this->requireOwnership($conversationId, $userId);

        $stmt = $this->db->prepare(
            'SELECT id, role, content, created_at
             FROM ai_messages
             WHERE conversation_id = ?
             ORDER BY id ASC'
        );

        $stmt->execute([$conversationId]);

        return $stmt->fetchAll();
    }

    public function saveExchange(
    int $userId,
    ?int $conversationId,
    string $message,
    string $reply,
    ?int &$assistantMessageId = null
): int {
    $assistantMessageId = null;
    $this->db->beginTransaction();

        try {
            if ($conversationId === null) {
                $title = preg_replace('/\s+/u', ' ', trim($message));
                $title = mb_substr($title, 0, 100, 'UTF-8');

                $stmt = $this->db->prepare(
                    'INSERT INTO ai_conversations (user_id, title)
                     VALUES (?, ?)'
                );

                $stmt->execute([$userId, $title]);

                $conversationId = (int) $this->db->lastInsertId();
            } else {
                $this->requireOwnership($conversationId, $userId);
            }

            $stmt = $this->db->prepare(
    'INSERT INTO ai_messages
        (conversation_id, role, content)
     VALUES (?, ?, ?)'
);

$stmt->execute([$conversationId, 'user', $message]);
$stmt->execute([$conversationId, 'assistant', $reply]);

$savedAssistantId = (int) $this->db->lastInsertId();
            $stmt = $this->db->prepare(
                'UPDATE ai_conversations
                 SET updated_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND user_id = ?'
            );

            $stmt->execute([$conversationId, $userId]);
            $this->db->commit();
            $assistantMessageId = $savedAssistantId;

            return $conversationId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $error;
        }
    }

    private function requireOwnership(
        int $conversationId,
        int $userId
    ): void {
        $stmt = $this->db->prepare(
            'SELECT id
             FROM ai_conversations
             WHERE id = ? AND user_id = ?'
        );

        $stmt->execute([$conversationId, $userId]);

        if ($stmt->fetch() === false) {
            throw new RuntimeException(
                'المحادثة غير موجودة أو لا تخص هذا المستخدم.'
            );
        }
    }

    public function rename(
    int $conversationId,
    int $userId,
    string $title
): void {
    $title = trim($title);

    if ($title === '' || mb_strlen($title, 'UTF-8') > 150) {
        throw new RuntimeException(
            'عنوان المحادثة لازم يكون من 1 إلى 150 حرف.'
        );
    }

    $this->requireOwnership($conversationId, $userId);

    $stmt = $this->db->prepare(
        'UPDATE ai_conversations
         SET title = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ? AND user_id = ?'
    );

    $stmt->execute([
        $title,
        $conversationId,
        $userId
    ]);
}

public function delete(
    int $conversationId,
    int $userId
): void {
    $this->requireOwnership($conversationId, $userId);

    $stmt = $this->db->prepare(
        'DELETE FROM ai_conversations
         WHERE id = ? AND user_id = ?'
    );

    $stmt->execute([
        $conversationId,
        $userId
    ]);
}
}