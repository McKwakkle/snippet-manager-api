<?php

namespace Tests\Controllers;

use App\Controllers\SnippetController;
use App\Helpers\JWT;
use App\Helpers\Response;
use App\Models\SnippetModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class SnippetControllerTest extends TestCase
{
  private SnippetModel $snippets;
  private UserModel $users;
  private array $createdUserIds = [];
  private array $createdSnippetIds = [];

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
    $this->snippets = new SnippetModel();
    $this->users = new UserModel();
  }

  protected function tearDown(): void
  {
    Response::$exitDisabled = false;
    unset($GLOBALS['__mock_input']);
    unset($_SERVER['HTTP_AUTHORIZATION']);

    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdSnippetIds as $id) {
      $db->prepare('DELETE FROM snippets WHERE id = :id')->execute(['id' => $id]);
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
    $user = $this->users->create('snippetuser', 'snippet@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  private function authAs(array $user): void
  {
    $token = JWT::encode(['user_id' => $user['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
  }

  public function test_index_returns_users_snippets(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'My Snippet',
      'code' => 'echo 1;',
      'language' => 'php',
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    ob_start();
    (new SnippetController())->index([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $ids = array_column($body['data'], 'id');
    $this->assertContains($snippet['id'], $ids);
  }

  public function test_store_creates_snippet(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $this->mockInput([
      'title' => 'New Snippet',
      'code' => 'echo "hello";',
      'language' => 'php',
    ]);

    ob_start();
    (new SnippetController())->store([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('New Snippet', $body['data']['title']);

    $this->createdSnippetIds[] = $body['data']['id'];
  }

  public function test_show_returns_snippet(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'Show Me',
      'code' => 'echo 1;',
      'language' => 'php',
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    ob_start();
    (new SnippetController())->show(['id' => $snippet['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals($snippet['id'], $body['data']['id']);
  }

  public function test_show_returns_404_for_another_users_snippet(): void
  {
    $user1 = $this->createTestUser();
    $user2 = $this->users->create('otheruser', 'other@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user2['id'];

    $snippet = $this->snippets->create($user2['id'], [
      'title' => 'Not Yours',
      'code' => 'echo 2;',
      'language' => 'php',
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    $this->authAs($user1);

    ob_start();
    (new SnippetController())->show(['id' => $snippet['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
  }

  public function test_update_modifies_snippet(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'Original',
      'code' => 'echo 1;',
      'language' => 'php',
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    $this->mockInput(['title' => 'Updated']);

    ob_start();
    (new SnippetController())->update(['id' => $snippet['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('Updated', $body['data']['title']);
  }

  public function test_destroy_deletes_snippet(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'To Delete',
      'code' => 'echo 1;',
      'language' => 'php',
    ]);

    ob_start();
    (new SnippetController())->destroy(['id' => $snippet['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertNull($this->snippets->findById($snippet['id']));
  }
}