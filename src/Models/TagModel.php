<?php

namespace App\Models;

use App\Database\Database;

class TagModel
{
  private \PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function create(int $userId, string $name): array
  {
    $stmt = $this->db->prepare('
            INSERT INTO tags (user_id, name)
            VALUES (:user_id, :name)
        ');

    $stmt->execute([
      'user_id' => $userId,
      'name' => $name,
    ]);

    return $this->findById((int) $this->db->lastInsertId());
  }

  public function findById(int $id): ?array
  {
    $stmt = $this->db->prepare('
            SELECT * FROM tags WHERE id = :id LIMIT 1
        ');

    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function findByUserId(int $userId): array
  {
    $stmt = $this->db->prepare('
            SELECT * FROM tags WHERE user_id = :user_id ORDER BY name ASC
        ');

    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
  }

  public function delete(int $id): void
  {
    $this->db->prepare('DELETE FROM tags WHERE id = :id')->execute(['id' => $id]);
  }
}
