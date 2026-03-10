<?php

namespace Tests\Models;

use App\Models\SnippetModel;
use App\Models\UserModel;
use App\Helpers\Response;
use PHPUnit\Framework\TestCase;

class SnippetModelTest extends TestCase
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
    $user = $this->users->create('snippetuser', 'snippet@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  public function test_create_saves_snippet_and_returns_it(): void
  {
    $user = $this->createTestUser();

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'Hello World',
      'code' => 'echo "Hello World";',
      'language' => 'php',
    ]);

    $this->createdSnippetIds[] = $snippet['id'];

    $this->assertNotNull($snippet['id']);
    $this->assertEquals('Hello World', $snippet['title']);
    $this->assertEquals('php', $snippet['language']);
    $this->assertEquals('private', $snippet['visibility']);
    $this->assertEquals(1, $snippet['anonymous']);
    $this->assertNull($snippet['description']);
  }

  public function test_find_by_user_id_returns_all_user_snippets(): void
  {
    $user = $this->createTestUser();

    $s1 = $this->snippets->create($user['id'], [
      'title' => 'Snippet One',
      'code' => 'echo 1;',
      'language' => 'php',
    ]);
    $s2 = $this->snippets->create($user['id'], [
      'title' => 'Snippet Two',
      'code' => 'echo 2;',
      'language' => 'php',
    ]);

    $this->createdSnippetIds[] = $s1['id'];
    $this->createdSnippetIds[] = $s2['id'];

    $results = $this->snippets->findByUserId($user['id']);

    $this->assertCount(2, $results);
  }

  public function test_update_changes_specified_fields(): void
  {
    $user = $this->createTestUser();

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'Original Title',
      'code' => 'echo 1',
      'language' => 'php',
    ]);

    $this->createdSnippetIds[] = $snippet['id'];

    $updated = $this->snippets->update($snippet['id'], [
      'title' => 'Updated Title',
      'visibility' => 'public',
    ]);

    $this->assertEquals('Updated Title', $updated['title']);
    $this->assertEquals('public', $updated['visibility']);
    $this->assertEquals('php', $updated['language']);
  }

  public function test_delete_remove_snippet(): void
  {
    $user = $this->createTestUser();

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'To Be Deleted',
      'code' => 'echo "bye";',
      'language' => 'php',
    ]);

    $this->snippets->delete($snippet['id']);

    $this->assertNull($this->snippets->findById($snippet['id']));
  }

  public function test_get_public_returns_only_public_snippets(): void
  {
    $user = $this->createTestUser();

    $public = $this->snippets->create($user['id'], [
      'title' => 'Public Snippet',
      'code' => 'echo "public";',
      'language' => 'php',
      'visibility' => 'public',
    ]);

    $private = $this->snippets->create($user['id'], [
      'title' => 'Private Snippet',
      'code' => 'echo "private";',
      'language' => 'php',
      'visibility' => 'private',
    ]);

    $this->createdSnippetIds[] = $public['id'];
    $this->createdSnippetIds[] = $private['id'];

    $results = $this->snippets->getPublic();

    $ids = array_column($results, 'id');
    $this->assertContains($public['id'], $ids);
    $this->assertNotContains($private['id'], $ids);
  }

  private function createSecondTestUser(): array
  {
    $user = $this->users->create('otheruser', 'other@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  public function test_get_by_following_returns_only_followed_users_snippets(): void
  {
    $user1 = $this->createTestUser();
    $user2 = $this->createSecondTestUser();

    $db = \App\Database\Database::getInstance()->getConnection();
    $db->prepare('INSERT INTO follows (follower_id, following_id) VALUES (:follower, :following)')
      ->execute(['follower' => $user1['id'], 'following' => $user2['id']]);

    $snippet = $this->snippets->create($user2['id'], [
      'title' => 'Followed User Snippet',
      'code' => 'echo "followed";',
      'language' => 'php',
      'visibility' => 'public',
    ]);

    $this->createdSnippetIds[] = $snippet['id'];

    $results = $this->snippets->getByFollowing($user1['id']);

    $ids = array_column($results, 'id');
    $this->assertContains($snippet['id'], $ids);
  }
}