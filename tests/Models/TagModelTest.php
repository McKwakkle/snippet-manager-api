<?php

namespace Tests\Models;

use App\Models\TagModel;
use App\Models\UserModel;
use App\Helpers\Response;
use PHPUnit\Framework\TestCase;

class TagModelTest extends TestCase
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
    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdTagIds as $id) {
      $db->prepare('DELETE FROM tags WHERE id = :id')->execute(['id' => $id]);
    }
    foreach ($this->createdUserIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
    Response::$exitDisabled = false;
  }

  private function createTestUser(): array
  {
    $user = $this->users->create('taguser', 'tag@test.com', password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  public function test_create_saves_tag_and_returns_it(): void
  {
    $user = $this->createTestUser();

    $tag = $this->tags->create($user['id'], 'php');
    $this->createdTagIds[] = $tag['id'];

    $this->assertNotNull($tag['id']);
    $this->assertEquals('php', $tag['name']);
    $this->assertEquals($user['id'], $tag['user_id']);
  }

  public function test_find_by_user_id_returns_all_user_tags(): void
  {
    $user = $this->createTestUser();

    $tag1 = $this->tags->create($user['id'], 'php');
    $tag2 = $this->tags->create($user['id'], 'javascript');

    $this->createdTagIds[] = $tag1['id'];
    $this->createdTagIds[] = $tag2['id'];

    $results = $this->tags->findByUserId($user['id']);

    $this->assertCount(2, $results);
  }

  public function test_delete_removes_tag(): void
  {
    $user = $this->createTestUser();

    $tag = $this->tags->create($user['id'], 'php');

    $this->tags->delete($tag['id']);

    $this->assertNull($this->tags->findById($tag['id']));
  }
}