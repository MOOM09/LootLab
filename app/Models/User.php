<?php

class User
{
    private PDO $connection;
    // Any model start with Database connection.
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query(
            'SELECT id, name, email, role, is_active, created_at
         FROM users
         ORDER BY created_at DESC, id DESC'
        );

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT id, name, email, phone, role, is_active, session_version
            FROM users WHERE id = :id'
        );

        $stmt->execute([':id' => $id]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT id, name, email, password, role, is_active, session_version
            FROM users WHERE email = :email'
        );

        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash
    ): int {
        $stmt = $this->connection->prepare(
            'INSERT INTO users (name, email, password)
         VALUES (:name, :email, :password)'
        );

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $passwordHash,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateProfile(
        int $id,
        string $name,
        string $phone
    ): bool {
        $stmt = $this->connection->prepare(
            'UPDATE users
         SET name = :name, phone = :phone
         WHERE id = :id'
        );

        return $stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':id' => $id,
        ]);
    }

    // Step 5 of recovery:Save the password hash and increment the session version.
    // Call inside the same transaction that consumes the reset token.
    public function updatePassword(
        int $userId,
        string $passwordHash
    ): bool {
        // Update active accounts only; exclude Google-only accounts without a local password.
        $stmt = $this->connection->prepare(
            'UPDATE users
         SET password = :password,
         session_version = session_version + 1
         WHERE id = :id
           AND is_active = 1
           AND password IS NOT NULL'
        );

        // passwordHash must come from password_hash, not plaintext user input.
        $stmt->execute([
            ':password' => $passwordHash,
            ':id' => $userId,
        ]);

        // True means one account was updated; otherwise roll back the reset transaction.
        return $stmt->rowCount() === 1;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->connection->prepare(
            'DELETE FROM users WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }


    public function ban(int $id): bool
    {
        $stmt = $this->connection->prepare(
            'UPDATE users
            SET is_active = 0
            WHERE id = :id AND is_active = 1'
        );

        $stmt->execute([":id" => $id]);

        return $stmt->rowCount() > 0;
    }

    public function unban(int $id): bool
    {
        $stmt = $this->connection->prepare(
            'UPDATE users
         SET is_active = 1
         WHERE id = :id AND is_active = 0'
        );

        $stmt->execute([
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    // ? Find a user using Google's unique account ID
    public function findByGoogleId(string $googleId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT id, name, email, role, is_active, google_id, session_version
         FROM users
         WHERE google_id = :google_id'
        );

        $stmt->execute([
            ':google_id' => $googleId,
        ]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    // ? Create a user who registered through Google
    public function createFromGoogle(
        string $name,
        string $email,
        string $googleId
    ): int {
        $stmt = $this->connection->prepare(
            'INSERT INTO users (name, email, google_id)
         VALUES (:name, :email, :google_id)'
        );

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':google_id' => $googleId,
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
