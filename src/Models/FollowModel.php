<?php

namespace App\Models;

use App\Database\Database;

class FollowModel
{
  private \PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function create(int $followerId, int $followingId): void
  {
    $stmt = $this->db->prepare('
            INSERT IGNORE INTO follows (follower_id, following_id)
            VALUES (:follower_id, :following_id)
        ');

    $stmt->execute([
      'follower_id' => $followerId,
      'following_id' => $followingId,
    ]);
  }

  public function delete(int $followerId, int $followingId): void
  {
    $stmt = $this->db->prepare('
            DELETE FROM follows
            WHERE follower_id = :follower_id
            AND following_id = :following_id
        ');

    $stmt->execute([
      'follower_id' => $followerId,
      'following_id' => $followingId,
    ]);
  }

  public function exists(int $followerId, int $followingId): bool
  {
    $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM follows
            WHERE follower_id = :follower_id
            AND following_id = :following_id
        ');

    $stmt->execute([
      'follower_id' => $followerId,
      'following_id' => $followingId,
    ]);

    return (bool) $stmt->fetchColumn();
  }

  public function getFollowers(int $userId): array
  {
    $stmt = $this->db->prepare('
            SELECT u.id, u.username, u.display_name
            FROM follows f
            JOIN users u ON u.id = f.follower_id
            WHERE f.following_id = :user_id
        ');

    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
  }

  public function getFollowing(int $userId): array
  {
    $stmt = $this->db->prepare('
            SELECT u.id, u.username, u.display_name
            FROM follows f
            JOIN users u ON u.id = f.following_id
            WHERE f.follower_id = :user_id
        ');

    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
  }
}
