<?php

namespace App\Database;

use PDO;
use PDOException;

class Database
{
  private static ?Database $instance = null;
  private PDO $connection;

  private function __construct()
  {
    $host = $_ENV['DB_HOST'];
    $port = $_ENV['DB_PORT'];
    $name = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];

    // Using pgsql driver for Supabase (Postgres) in production.
    // For local MySQL development, change pgsql: to mysql: and append ;charset=utf8mb4
    $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
      $this->connection = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
      throw new PDOException($e->getMessage(), (int) $e->getCode());
    }
  }

  private function __clone()
  {
  }

  public function __wakeup(): never
  {
    throw new \Exception('Cannot unserialize a singleton.');
  }

  public static function getInstance(): Database
  {
    if (self::$instance === null) {
      self::$instance = new Database();
    }
    return self::$instance;
  }

  public function getConnection(): PDO
  {
    return $this->connection;
  }
}


