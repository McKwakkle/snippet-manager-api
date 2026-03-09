<?php

namespace App\Models;

use App\Database\Database;

class PasswordResetModel
{
  private \PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function create(string $email): array
  {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $this->db->prepare('
            INSERT INTO password_resets (email, token, expires_at)
            VALUES (:email, :token, :expires_at)
        ');

    $stmt->execute([
      'email' => $email,
      'token' => $token,
      'expires_at' => $expiresAt,
    ]);

    return $this->findByToken($token);
  }

  public function findByToken(string $token): ?array
  {
    $stmt = $this->db->prepare('
            SELECT * FROM password_resets WHERE token = :token LIMIT 1
        ');

    $stmt->execute(['token' => $token]);
    $result = $stmt->fetch();
    return $result ?: null;
  }

  public function isValid(array $reset): bool
  {
    if ($reset['used_at'] !== null) {
      return false;
    }

    if (strtotime($reset['expires_at']) < time()) {
      return false;
    }

    return true;
  }

  public function markUsed(string $token): void
  {
    $stmt = $this->db->prepare('
            UPDATE password_resets SET used_at = NOW() WHERE token = :token
        ');

    $stmt->execute(['token' => $token]);
  }
}