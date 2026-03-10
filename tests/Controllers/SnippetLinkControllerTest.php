<?php

namespace Tests\Controllers;

use App\Controllers\SnippetLinkController;
use App\Helpers\JWT;
use App\Helpers\Response;
use App\Models\SnippetModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class SnippetLinkControllerTest extends TestCase
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
    $user = $this->users->create('linkuser', 'link@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  private function authAs(array $user): void
  {
    $token = JWT::encode(['user_id' => $user['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
  }

  private function createTestSnippet(int $userId, string $title = 'Test Snippet'): array
  {
    $snippet = $this->snippets->create($userId, [
      'title' => $title,
      'code' => 'echo 1;',
      'language' => 'php',
    ]);
    $this->createdSnippetIds[] = $snippet['id'];
    return $snippet;
  }

  public function test_store_creates_link_between_snippets(): void
  {
    $user = $this->createTestUser();
    $snippet1 = $this->createTestSnippet($user['id'], 'Snippet A');
    $snippet2 = $this->createTestSnippet($user['id'], 'Snippet B');

    $this->authAs($user);
    $this->mockInput([
      'linked_snippet_id' => $snippet2['id'],
      'label' => 'Related',
    ]);

    ob_start();
    (new SnippetLinkController())->store(['id' => $snippet1['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals($snippet1['id'], $body['data']['snippet_id']);
    $this->assertEquals($snippet2['id'], $body['data']['linked_snippet_id']);
  }

  public function test_destroy_deletes_link(): void
  {
    $user = $this->createTestUser();
    $snippet1 = $this->createTestSnippet($user['id'], 'Snippet A');
    $snippet2 = $this->createTestSnippet($user['id'], 'Snippet B');

    $this->authAs($user);
    $this->mockInput([
      'linked_snippet_id' => $snippet2['id'],
      'label' => 'To Delete',
    ]);

    ob_start();
    (new SnippetLinkController())->store(['id' => $snippet1['id']]);
    $stored = json_decode(ob_get_clean(), true);

    ob_start();
    (new SnippetLinkController())->destroy([
      'id' => $snippet1['id'],
      'linkId' => $stored['data']['id'],
    ]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
  }
}