<?php

namespace App\Models;

use App\Database\Database;

class UserModel
{
  private \PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function findByEmail(string $email): ?array
  {
    $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function findByUsername(string $username): ?array
  {
    $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function findByDisplayName(string $displayName): ?array
  {
    $stmt = $this->db->prepare('SELECT * FROM users WHERE display_name = :display_name LIMIT 1');
    $stmt->execute(['display_name' => $displayName]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function findById(string $id): ?array
  {
    $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function create(string $username, string $email, string $passwordHash): array
  {
    $stmt = $this->db->prepare('
            INSERT INTO users (username, email, password_hash)
            VALUES (:username, :email, :password_hash)
        ');

    $stmt->execute([
      'username' => $username,
      'email' => $email,
      'password_hash' => $passwordHash,
    ]);

    return $this->findById($this->db->lastInsertId());
  }

  public function update(string $id, array $fields): ?array
  {
    if (empty($fields)) {
      return $this->findById($id);
    }

    $setClauses = implode(', ', array_map(
      fn($key) => "{$key} = :{$key}",
      array_keys($fields)
    ));

    $fields['id'] = $id;

    $stmt = $this->db->prepare("
            UPDATE users SET {$setClauses}, updated_at = NOW() WHERE id = :id
        ");

    $stmt->execute($fields);

    return $this->findById($id);
  }

  public function publicProfile(array $user): array
  {
    return [
      'id' => $user['id'],
      'username' => $user['username'],
      'display_name' => $user['display_name'],
      'bio' => $user['bio'],
      'created_at' => $user['created_at'],
    ];
  }

  public function getAll(): array
  {
    return $this->db->query('SELECT * FROM users')->fetchAll();
  }
}