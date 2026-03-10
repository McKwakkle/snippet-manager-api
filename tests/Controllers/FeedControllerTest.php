<?php

namespace Tests\Controllers;

use App\Controllers\FeedController;
use App\Helpers\JWT;
use App\Helpers\Response;
use App\Models\SnippetModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class FeedControllerTest extends TestCase
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
    unset($_SERVER['HTTP_AUTHORIZATION']);

    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdSnippetIds as $id) {
      $db->prepare('DELETE FROM snippets WHERE id = :id')->execute(['id' => $id]);
    }
    foreach ($this->createdUserIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
  }

  private function createTestUser(): array
  {
    $user = $this->users->create('feeduser', 'feed@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  private function authAs(array $user): void
  {
    $token = JWT::encode(['user_id' => $user['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
  }

  public function test_public_feed_returns_only_public_snippets(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $public = $this->snippets->create($user['id'], [
      'title' => 'Public One',
      'code' => 'echo 1;',
      'language' => 'php',
      'visibility' => 'public',
      'anonymous' => 0,
    ]);
    $private = $this->snippets->create($user['id'], [
      'title' => 'Private One',
      'code' => 'echo 2;',
      'language' => 'php',
      'visibility' => 'private',
    ]);

    $this->createdSnippetIds[] = $public['id'];
    $this->createdSnippetIds[] = $private['id'];

    ob_start();
    (new FeedController())->publicFeed([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $ids = array_column($body['data'], 'id');
    $this->assertContains($public['id'], $ids);
    $this->assertNotContains($private['id'], $ids);
  }

  public function test_public_feed_strips_user_info_for_anonymous_snippets(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'Anon Snippet',
      'code' => 'echo 1;',
      'language' => 'php',
      'visibility' => 'public',
      'anonymous' => 1,
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    ob_start();
    (new FeedController())->publicFeed([]);
    $body = json_decode(ob_get_clean(), true);

    $ids = array_column($body['data'], 'id');
    $index = array_search($snippet['id'], $ids);

    $this->assertArrayNotHasKey('username', $body['data'][$index]);
    $this->assertArrayNotHasKey('user_id', $body['data'][$index]);
  }

  public function test_public_feed_sets_is_owner_correctly(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'My Public Snippet',
      'code' => 'echo 1;',
      'language' => 'php',
      'visibility' => 'public',
      'anonymous' => 0,
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    ob_start();
    (new FeedController())->publicFeed([]);
    $body = json_decode(ob_get_clean(), true);

    $ids = array_column($body['data'], 'id');
    $index = array_search($snippet['id'], $ids);

    $this->assertTrue($body['data'][$index]['is_owner']);
  }

  public function test_following_feed_returns_snippets_from_followed_users(): void
  {
    $user1 = $this->createTestUser();
    $user2 = $this->users->create('otheruser', 'other@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user2['id'];

    $db = \App\Database\Database::getInstance()->getConnection();
    $db->prepare('INSERT INTO follows (follower_id, following_id) VALUES (:follower, :following)')
      ->execute(['follower' => $user1['id'], 'following' => $user2['id']]);

    $snippet = $this->snippets->create($user2['id'], [
      'title' => 'Followed Snippet',
      'code' => 'echo 1;',
      'language' => 'php',
      'visibility' => 'public',
      'anonymous' => 0,
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    $this->authAs($user1);

    ob_start();
    (new FeedController())->followingFeed([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $ids = array_column($body['data'], 'id');
    $this->assertContains($snippet['id'], $ids);
  }

  public function test_public_snippet_returns_public_snippet(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'Public Snippet',
      'code' => 'echo 1;',
      'language' => 'php',
      'visibility' => 'public',
      'anonymous' => 0,
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    ob_start();
    (new FeedController())->publicSnippet(['id' => $snippet['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals($snippet['id'], $body['data']['id']);
  }

  public function test_public_snippet_returns_404_for_private_snippet(): void
  {
    $user = $this->createTestUser();
    $this->authAs($user);

    $snippet = $this->snippets->create($user['id'], [
      'title' => 'Private Snippet',
      'code' => 'echo 1;',
      'language' => 'php',
      'visibility' => 'private',
    ]);
    $this->createdSnippetIds[] = $snippet['id'];

    ob_start();
    (new FeedController())->publicSnippet(['id' => $snippet['id']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
  }
}