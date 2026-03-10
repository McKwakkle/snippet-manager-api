<?php

namespace App\Models;

use App\Database\Database;

class SnippetLinkModel
{
  private \PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function create(int $snippetId, int $linkedSnippetId, ?string $label): array
  {
    $stmt = $this->db->prepare('
            INSERT INTO snippet_links (snippet_id, linked_snippet_id, label)
            VALUES (:snippet_id, :linked_snippet_id, :label)
        ');

    $stmt->execute([
      'snippet_id' => $snippetId,
      'linked_snippet_id' => $linkedSnippetId,
      'label' => $label,
    ]);

    return $this->findById((int) $this->db->lastInsertId());
  }

  public function findById(int $id): ?array
  {
    $stmt = $this->db->prepare('
            SELECT * FROM snippet_links WHERE id = :id LIMIT 1
        ');

    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function findBySnippetId(int $snippetId): array
  {
    $stmt = $this->db->prepare('
            SELECT * FROM snippet_links WHERE snippet_id = :snippet_id
        ');

    $stmt->execute(['snippet_id' => $snippetId]);
    return $stmt->fetchAll();
  }

  public function delete(int $id): void
  {
    $this->db->prepare('DELETE FROM snippet_links WHERE id = :id')->execute(['id' => $id]);
  }
}
