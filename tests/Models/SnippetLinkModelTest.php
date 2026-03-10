<?php

namespace Tests\Models;

use App\Models\SnippetLinkModel;
use App\Models\SnippetModel;
use App\Models\UserModel;
use App\Helpers\Response;
use PHPUnit\Framework\TestCase;

class SnippetLinkModelTest extends TestCase
{
  private SnippetLinkModel $links;
  private SnippetModel $snippets;
  private UserModel $users;
  private array $createdUserIds = [];
  private array $createdSnippetIds = [];

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
    $this->links = new SnippetLinkModel();
    $this->snippets = new SnippetModel();
    $this->users = new UserModel();
  }

  protected function tearDown(): void
  {
    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdSnippetIds as $id) {
      $db->prepare('DELETE FROM snippets WHERE id = :id')->execute(['id' => $id]);
    }
    foreach ($this->createdUserIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
    Response::$exitDisabled = false;
  }

  private function createTestUser(): array
  {
    $user = $this->users->create('linkuser', 'link@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
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

  public function test_create_saves_link_and_returns_it(): void
  {
    $user = $this->createTestUser();
    $snippet1 = $this->createTestSnippet($user['id'], 'Snippet A');
    $snippet2 = $this->createTestSnippet($user['id'], 'Snippet B');

    $link = $this->links->create($snippet1['id'], $snippet2['id'], 'Related');

    $this->assertNotNull($link['id']);
    $this->assertEquals($snippet1['id'], $link['snippet_id']);
    $this->assertEquals($snippet2['id'], $link['linked_snippet_id']);
    $this->assertEquals('Related', $link['label']);
  }

  public function test_find_by_snippet_id_returns_all_links(): void
  {
    $user = $this->createTestUser();
    $snippet1 = $this->createTestSnippet($user['id'], 'Snippet A');
    $snippet2 = $this->createTestSnippet($user['id'], 'Snippet B');
    $snippet3 = $this->createTestSnippet($user['id'], 'Snippet C');

    $this->links->create($snippet1['id'], $snippet2['id'], 'Link 1');
    $this->links->create($snippet1['id'], $snippet3['id'], 'Link 2');

    $results = $this->links->findBySnippetId($snippet1['id']);

    $this->assertCount(2, $results);
  }

  public function test_delete_removes_link(): void
  {
    $user = $this->createTestUser();
    $snippet1 = $this->createTestSnippet($user['id'], 'Snippet A');
    $snippet2 = $this->createTestSnippet($user['id'], 'Snippet B');

    $link = $this->links->create($snippet1['id'], $snippet2['id'], 'To Delete');

    $this->links->delete($link['id']);

    $this->assertNull($this->links->findById($link['id']));
  }
}