<?php

namespace Tests\Database;

use App\Database\Database;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
  public function test_can_get_database_instance(): void
  {
    $instance = Database::getInstance();

    $this->assertInstanceOf(Database::class, $instance);
  }

  public function test_returns_same_instance_every_time(): void
  {
    $instance1 = Database::getInstance();
    $instance2 = Database::getInstance();

    $this->assertSame($instance1, $instance2);
  }

  public function test_can_get_pdo_connection(): void
  {
    $connection = Database::getInstance()->getConnection();

    $this->assertInstanceOf(PDO::class, $connection);
  }

  public function test_connection_is_alive(): void
  {
    $connection = Database::getInstance()->getConnection();
    $result = $connection->query('SELECT 1');

    $this->assertNotFalse($result);
  }
}