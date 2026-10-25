<?php
declare(strict_types=1);

namespace ArchonAI\Repositories;

use PDO;
use ArchonAI\Helpers\Logger;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/Logger.php';

class UserRepository {
    private PDO $db;

    public function __construct() {
        $this->db = db();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, created_at, updated_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function emailExists(string $email): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        return (bool)$stmt->fetchColumn();
    }

    public function create(string $name, string $email, string $password): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password_hash, created_at, updated_at) 
             VALUES (:name, :email, :password_hash, NOW(), NOW())"
        );
        $stmt->execute([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password_hash' => $hash,
        ]);

        $userId = (int)$this->db->lastInsertId();
        Logger::info("New user registered successfully", ['user_id' => $userId, 'email' => $email]);
        return $userId;
    }
}
