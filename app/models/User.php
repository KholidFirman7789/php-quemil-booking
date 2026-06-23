<?php
/**
 * Model: User
 */
defined('BASE_PATH') or define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $phone, string $password): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')"
        );
        $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_BCRYPT)]);
        return (int) $this->db->lastInsertId();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function updateProfile(int $id, string $name, string $phone): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
        return $stmt->execute([$name, $phone, $id]);
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }

    public function getAll(): array
    {
        return $this->db->query(
            'SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC'
        )->fetchAll();
    }
}
