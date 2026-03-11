<?php

namespace Tests\Controllers;

use App\Controllers\TagController;
use App\Helpers\JWT;
use App\Helpers\Response;
use App\Models\TagModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class TagControllerTest extends TestCase
{
  private TagModel $tags;
  private UserModel $users;
  private array $createdUserIds = [];
  private array $createdTagIds = [];

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
    $this->tags = new TagModel();
    $this->users = new UserModel();
  }

  protected function tearDown(): void
  {
    Response::$exitDisabled = false;
    unset($GLOBALS['__mock_input']);
    unset($_SERVER['HTTP_AUTHORIZATION']);

    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdTagIds as $id) {
      $db->prepare('DELETE FROM tags WHERE id = :id')->execute(['id' => $id]);
    }
    foreach ($this->createdUserIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
  }

  private function mockInput(array $data): void
  {
    $GLOBALS['__mock_input'] = json_encode($data);
  }

  private function createTestUser(): array
  {
    $user = $this->users->create('taguser', 'tag@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  private function authAs(array $user): void
  {
    $token = JWT::encode(['user_id' => $user['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
  }

  public function test_index_returns_users_tags(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $tag = $this->tags->create($user['id'], 'php');
    $this->createdTagIds[] = $tag['id'];

    ob_start();
    (new TagController())->index([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $ids = array_column($body['data'], 'id');
    $this->assertContains($tag['id'], $ids);
  }

  public function test_store_creates_tag(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $this->mockInput(['name' => 'javascript']);

    ob_start();
    (new TagController())->store([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('javascript', $body['data']['name']);

    $this->createdTagIds[] = $body['data']['id'];
  }

  public function test_destroy_deletes_tag(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $tag = $this->tags->create($user['id'], 'to-delete');

    ob_start();
    (new TagController())->destroy(['id' => $tag['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertNull($this->tags->findById($tag['id']));
  }
}