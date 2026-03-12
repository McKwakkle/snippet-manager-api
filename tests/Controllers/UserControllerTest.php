<?php

namespace Tests\Controllers;

use App\Controllers\UserController;
use App\Helpers\JWT;
use App\Helpers\Response;
use App\Models\FollowModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
  private UserModel $users;
  private FollowModel $follows;
  private array $createdUserIds = [];

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
    $this->users = new UserModel();
    $this->follows = new FollowModel();
  }

  protected function tearDown(): void
  {
    Response::$exitDisabled = false;
    unset($_SERVER['HTTP_AUTHORIZATION']);

    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdUserIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
  }

  private function createTestUser(string $username, string $email): array
  {
    $user = $this->users->create($username, $email, password_hash('secret123', PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  private function authAs(array $user): void
  {
    $token = JWT::encode(['user_id' => $user['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
  }

  public function test_index_returns_list_of_users(): void
  {
    $user = $this->createTestUser('userctrluser', 'userctrl@test.com');
    $this->authAs($user);

    ob_start();
    (new UserController())->index([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $ids = array_column($body['data'], 'id');
    $this->assertContains($user['id'], $ids);
  }

  public function test_show_returns_user_profile(): void
  {
    $user = $this->createTestUser('userctrluser', 'userctrl@test.com');
    $user2 = $this->createTestUser('userctrluser2', 'userctrl2@test.com');

    $this->users->update($user2['id'], ['display_name' => 'TestDisplay']);

    $this->authAs($user);

    ob_start();
    (new UserController())->show(['display_name' => 'TestDisplay']);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('userctrluser2', $body['data']['username']);
    $this->assertArrayHasKey('is_following', $body['data']);
  }

  public function test_follow_user(): void
  {
    $user = $this->createTestUser('followerctrl', 'followerctrl@test.com');
    $user2 = $this->createTestUser('followingctrl', 'followingctrl@test.com');

    $this->users->update($user2['id'], ['display_name' => 'FollowTarget']);

    $this->authAs($user);

    ob_start();
    (new UserController())->follow(['display_name' => 'FollowTarget']);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('User followed successfully', $body['data']['message']);
  }

  public function test_unfollow_user(): void
  {
    $user = $this->createTestUser('unfollowerctrl', 'unfollowerctrl@test.com');
    $user2 = $this->createTestUser('unfollowingctrl', 'unfollowingctrl@test.com');

    $this->users->update($user2['id'], ['display_name' => 'UnfollowTarget']);

    $this->authAs($user);

    ob_start();
    (new UserController())->follow(['display_name' => 'UnfollowTarget']);
    ob_end_clean();

    ob_start();
    (new UserController())->unfollow(['display_name' => 'UnfollowTarget']);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('User unfollowed successfully', $body['data']['message']);
  }

  public function test_get_followers(): void
  {
    $user = $this->createTestUser('followersctrl', 'followersctrl@test.com');
    $user2 = $this->createTestUser('followersctrl2', 'followersctrl2@test.com');

    $this->users->update($user['id'], ['display_name' => 'FollowersTarget']);

    $this->authAs($user2);

    ob_start();
    (new UserController())->follow(['display_name' => 'FollowersTarget']);
    ob_end_clean();

    $this->authAs($user);

    ob_start();
    (new UserController())->followers(['display_name' => 'FollowersTarget']);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertCount(1, $body['data']);
    $this->assertEquals('followersctrl2', $body['data'][0]['username']);
  }

  public function test_get_following(): void
  {
    $user = $this->createTestUser('followingctrlA', 'followingctrlA@test.com');
    $user2 = $this->createTestUser('followingctrlB', 'followingctrlB@test.com');

    $this->users->update($user['id'], ['display_name' => 'FollowingSource']);
    $this->users->update($user2['id'], ['display_name' => 'FollowingTarget']);

    $this->authAs($user);

    ob_start();
    (new UserController())->follow(['display_name' => 'FollowingTarget']);
    ob_end_clean();

    ob_start();
    (new UserController())->following(['display_name' => 'FollowingSource']);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertCount(1, $body['data']);
    $this->assertEquals('followingctrlB', $body['data'][0]['username']);
  }
}