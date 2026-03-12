<?php

namespace Tests\Models;

use App\Models\FollowModel;
use App\Models\UserModel;
use App\Helpers\Response;
use PHPUnit\Framework\TestCase;

class FollowModelTest extends TestCase
{
  private FollowModel $follows;
  private UserModel $users;
  private array $createdUserIds = [];

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
    $this->follows = new FollowModel();
    $this->users = new UserModel();
  }

  protected function tearDown(): void
  {
    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdUserIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
    Response::$exitDisabled = false;
  }

  private function createTestUser(string $username, string $email): array
  {
    $user = $this->users->create($username, $email, password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  public function test_create_follow_and_exists_returns_true(): void
  {
    $user1 = $this->createTestUser('follower', 'follower@test.com');
    $user2 = $this->createTestUser('following', 'following@test.com');

    $this->follows->create($user1['id'], $user2['id']);

    $this->assertTrue($this->follows->exists($user1['id'], $user2['id']));
  }

  public function test_delete_removes_follow(): void
  {
    $user1 = $this->createTestUser('follower', 'follower@test.com');
    $user2 = $this->createTestUser('following', 'following@test.com');

    $this->follows->create($user1['id'], $user2['id']);
    $this->follows->delete($user1['id'], $user2['id']);

    $this->assertFalse($this->follows->exists($user1['id'], $user2['id']));
  }

  public function test_get_followers_returns_users_who_follow_me(): void
  {
    $user1 = $this->createTestUser('follower', 'follower@test.com');
    $user2 = $this->createTestUser('following', 'following@test.com');

    $this->follows->create($user1['id'], $user2['id']);

    $followers = $this->follows->getFollowers($user2['id']);

    $ids = array_column($followers, 'id');
    $this->assertContains($user1['id'], $ids);
  }

  public function test_get_following_returns_users_i_follow(): void
  {
    $user1 = $this->createTestUser('follower', 'follower@test.com');
    $user2 = $this->createTestUser('following', 'following@test.com');

    $this->follows->create($user1['id'], $user2['id']);

    $following = $this->follows->getFollowing($user1['id']);

    $ids = array_column($following, 'id');
    $this->assertContains($user2['id'], $ids);
  }
}