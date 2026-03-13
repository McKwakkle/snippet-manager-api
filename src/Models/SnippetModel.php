<?php
namespace App\Models;

use App\Database\Database;

class SnippetModel
{
  private \PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function create(int $userId, array $data): array
  {
    $stmt = $this->db->prepare('
            INSERT INTO snippets (user_id, title, description, code, language, visibility, anonymous)
            VALUES (:user_id, :title, :description, :code, :language, :visibility, :anonymous)
        ');

    $stmt->execute([
      'user_id' => $userId,
      'title' => $data['title'],
      'description' => $data['description'] ?? null,
      'code' => $data['code'],
      'language' => $data['language'],
      'visibility' => $data['visibility'] ?? 'private',
      'anonymous' => $data['anonymous'] ?? 1,
    ]);

    return $this->findById((int) $this->db->lastInsertId());
  }

  public function findById(int $id): ?array
  {
    $stmt = $this->db->prepare('
            SELECT * FROM snippets WHERE id = :id LIMIT 1
        ');

    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function findByUserId(int $userId): array
  {
    $stmt = $this->db->prepare('
        SELECT * FROM snippets WHERE user_id = :user_id ORDER BY created_at DESC
    ');

    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
  }

  public function update(int $id, array $data): ?array
  {
    $allowed = ['title', 'description', 'code', 'language', 'visibility', 'anonymous'];
    $fields = array_filter(
      $data,
      fn($key) => in_array($key, $allowed),
      ARRAY_FILTER_USE_KEY
    );

    if (empty($fields)) {
      return $this->findById($id);
    }

    $setParts = array_map(fn($key) => "$key = :$key", array_keys($fields));
    $sql = 'UPDATE snippets SET ' . implode(', ', $setParts) . ' WHERE id = :id';

    $fields['id'] = $id;
    $this->db->prepare($sql)->execute($fields);

    return $this->findById($id);
  }

  public function delete(int $id): void
  {
    $this->db->prepare('DELETE FROM snippets WHERE id = :id')->execute(['id' => $id]);
  }

  public function getPublic(): array
  {
    $stmt = $this->db->prepare('
    SELECT s.*, u.username, u.display_name
        FROM snippets s
        JOIN users u ON s.user_id = u.id
        WHERE s.visibility = :visibility
        ORDER BY s.created_at DESC
    ');

    $stmt->execute(['visibility' => 'public']);
    return $stmt->fetchAll();
  }

  public function getByFollowing(int $userId): array
  {
    $stmt = $this->db->prepare('
        SELECT s.*, u.username, u.display_name
        FROM snippets s
        JOIN users u ON s.user_id = u.id
        JOIN follows f ON f.following_id = s.user_id
        WHERE f.follower_id = :user_id
        AND s.visibility = :visibility
        ORDER BY s.created_at DESC
    ');

    $stmt->execute([
      'user_id' => $userId,
      'visibility' => 'public',
    ]);

    return $stmt->fetchAll();
  }
}
