<?php

namespace Tests\Models;

use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
  private UserModel $model;
  private array $createdIds = [];

  protected function setUp(): void
  {
    $this->model = new UserModel();
  }

  protected function tearDown(): void
  {
    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
  }

  private function createTestUser(
    string $username = 'testuser',
    string $email = 'test@test.com',
    string $password = 'secret123'
  ): array {
    $user = $this->model->create($username, $email, password_hash($password, PASSWORD_BCRYPT));
    $this->createdIds[] = $user['id'];
    return $user;
  }


  public function test_create_returns_user_with_id(): void
  {
    $user = $this->createTestUser();

    $this->assertArrayHasKey('id', $user);
    $this->assertEquals('testuser', $user['username']);
    $this->assertEquals('test@test.com', $user['email']);
  }

  public function test_create_stores_hashed_password(): void
  {
    $user = $this->createTestUser();

    $this->assertArrayHasKey('password_hash', $user);
    $this->assertTrue(password_verify('secret123', $user['password_hash']));
  }


  public function test_find_by_email_returns_user(): void
  {
    $this->createTestUser();

    $found = $this->model->findByEmail('test@test.com');

    $this->assertNotNull($found);
    $this->assertEquals('testuser', $found['username']);
  }

  public function test_find_by_email_returns_null_when_not_found(): void
  {
    $found = $this->model->findByEmail('nobody@test.com');

    $this->assertNull($found);
  }


  public function test_find_by_username_returns_user(): void
  {
    $this->createTestUser();

    $found = $this->model->findByUsername('testuser');

    $this->assertNotNull($found);
    $this->assertEquals('test@test.com', $found['email']);
  }

  public function test_find_by_username_returns_null_when_not_found(): void
  {
    $found = $this->model->findByUsername('nobody');

    $this->assertNull($found);
  }


  public function test_find_by_id_returns_user(): void
  {
    $created = $this->createTestUser();

    $found = $this->model->findById($created['id']);

    $this->assertNotNull($found);
    $this->assertEquals('testuser', $found['username']);
  }

  public function test_find_by_id_returns_null_when_not_found(): void
  {
    $found = $this->model->findById('99999');

    $this->assertNull($found);
  }


  public function test_find_by_display_name_returns_user(): void
  {
    $created = $this->createTestUser();
    $this->model->update($created['id'], ['display_name' => 'Kellan']);

    $found = $this->model->findByDisplayName('Kellan');

    $this->assertNotNull($found);
    $this->assertEquals('testuser', $found['username']);
  }

  public function test_find_by_display_name_returns_null_when_not_found(): void
  {
    $found = $this->model->findByDisplayName('nobody');

    $this->assertNull($found);
  }


  public function test_update_modifies_fields(): void
  {
    $created = $this->createTestUser();

    $updated = $this->model->update($created['id'], ['display_name' => 'Kellan', 'bio' => 'Developer']);

    $this->assertEquals('Kellan', $updated['display_name']);
    $this->assertEquals('Developer', $updated['bio']);
  }

  public function test_update_with_empty_fields_returns_existing_user(): void
  {
    $created = $this->createTestUser();

    $result = $this->model->update($created['id'], []);

    $this->assertEquals($created['id'], $result['id']);
  }


  public function test_public_profile_excludes_sensitive_fields(): void
  {
    $created = $this->createTestUser();

    $public = $this->model->publicProfile($created);

    $this->assertArrayNotHasKey('password_hash', $public);
    $this->assertArrayNotHasKey('id', $public);
    $this->assertArrayHasKey('username', $public);
    $this->assertArrayHasKey('display_name', $public);
  }
}