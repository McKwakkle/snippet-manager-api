<?php

namespace Tests\Controllers;

use App\Controllers\AuthController;
use App\Helpers\JWT;
use App\Helpers\Response;
use App\Models\PasswordResetModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
  private UserModel $users;
  private PasswordResetModel $resets;
  private array $createdUserIds = [];

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
    $this->users = new UserModel();
    $this->resets = new PasswordResetModel();
  }

  protected function tearDown(): void
  {
    Response::$exitDisabled = false;
    unset($GLOBALS['__mock_input']);
    unset($_SERVER['HTTP_AUTHORIZATION']);

    $db = \App\Database\Database::getInstance()->getConnection();
    foreach ($this->createdUserIds as $id) {
      $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }
  }

  private function mockInput(array $data): void
  {
    $GLOBALS['__mock_input'] = json_encode($data);
  }

  private function createTestUser(
    string $username = 'testuser',
    string $email = 'test@test.com',
    string $password = 'secret123'
  ): array {
    $user = $this->users->create($username, $email, password_hash($password, PASSWORD_BCRYPT));
    $this->createdUserIds[] = $user['id'];
    return $user;
  }

  public function test_register_creates_user_and_returns_token(): void
  {
    $this->mockInput([
      'username' => 'newuser',
      'email' => 'new@test.com',
      'password' => 'secret123',
      'password_confirmation' => 'secret123',
    ]);

    ob_start();
    (new AuthController())->register([]);
    $body = json_decode(ob_get_clean(), true);

    $user = $this->users->findByEmail('new@test.com');
    if ($user)
      $this->createdUserIds[] = $user['id'];

    $this->assertTrue($body['success']);
    $this->assertArrayHasKey('token', $body['data']);
    $this->assertEquals('newuser', $body['data']['user']['username']);
  }

  public function test_register_fails_with_missing_fields(): void
  {
    $this->mockInput(['username' => 'newuser']);

    ob_start();
    (new AuthController())->register([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
  }

  public function test_register_fails_with_duplicate_email(): void
  {
    $this->createTestUser();

    $this->mockInput([
      'username' => 'otheruser',
      'email' => 'test@test.com',
      'password' => 'secret123',
      'password_confirmation' => 'secret123',
    ]);

    ob_start();
    (new AuthController())->register([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Email is already in use', $body['error']);
  }

  public function test_register_fails_with_duplicate_username(): void
  {
    $this->createTestUser();

    $this->mockInput([
      'username' => 'testuser',
      'email' => 'other@test.com',
      'password' => 'secret123',
      'password_confirmation' => 'secret123',
    ]);

    ob_start();
    (new AuthController())->register([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Username is already taken', $body['error']);
  }

  public function test_login_returns_token_with_valid_credentials(): void
  {
    $this->createTestUser();

    $this->mockInput([
      'email' => 'test@test.com',
      'password' => 'secret123',
    ]);

    ob_start();
    (new AuthController())->login([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertArrayHasKey('token', $body['data']);
  }

  public function test_login_fails_with_wrong_password(): void
  {
    $this->createTestUser();

    $this->mockInput([
      'email' => 'test@test.com',
      'password' => 'wrongpassword',
    ]);

    ob_start();
    (new AuthController())->login([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Invalid email or password', $body['error']);
  }

  public function test_login_fails_with_unknown_email(): void
  {
    $this->mockInput([
      'email' => 'nobody@test.com',
      'password' => 'secret123',
    ]);

    ob_start();
    (new AuthController())->login([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Invalid email or password', $body['error']);
  }

  public function test_forgot_password_returns_success_for_existing_email(): void
  {
    $this->createTestUser();

    $this->mockInput(['email' => 'test@test.com']);

    ob_start();
    (new AuthController())->forgotPassword([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertArrayHasKey('debug_token', $body['data']);
  }

  public function test_forgot_password_returns_success_for_unknown_email(): void
  {
    $this->mockInput(['email' => 'nobody@test.com']);

    ob_start();
    (new AuthController())->forgotPassword([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertArrayNotHasKey('debug_token', $body['data']);
  }

  public function test_verify_reset_token_returns_valid_for_good_token(): void
  {
    $user = $this->createTestUser();
    $reset = $this->resets->create($user['email']);

    ob_start();
    (new AuthController())->verifyResetToken(['token' => $reset['token']]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
  }

  public function test_verify_reset_token_fails_for_invalid_token(): void
  {
    ob_start();
    (new AuthController())->verifyResetToken(['token' => 'notavalidtoken']);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
  }

  public function test_reset_password_updates_password_successfully(): void
  {
    $user = $this->createTestUser();
    $reset = $this->resets->create($user['email']);

    $this->mockInput([
      'token' => $reset['token'],
      'password' => 'newpassword123',
      'password_confirmation' => 'newpassword123',
    ]);

    ob_start();
    (new AuthController())->resetPassword([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);

    $updated = $this->users->findByEmail($user['email']);
    $this->assertTrue(password_verify('newpassword123', $updated['password_hash']));
  }

  public function test_reset_password_fails_with_invalid_token(): void
  {
    $this->mockInput([
      'token' => 'invalidtoken',
      'password' => 'newpassword123',
      'password_confirmation' => 'newpassword123',
    ]);

    ob_start();
    (new AuthController())->resetPassword([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
  }

  public function test_me_returns_current_user(): void
  {
    $user = $this->createTestUser();
    $token = JWT::encode(['user_id' => $user['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

    ob_start();
    (new AuthController())->me([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('testuser', $body['data']['username']);
  }

  public function test_me_fails_without_token(): void
  {
    ob_start();
    (new AuthController())->me([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
  }

  public function test_update_profile_saves_display_name(): void
  {
    $user = $this->createTestUser();
    $token = JWT::encode(['user_id' => $user['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

    $this->mockInput(['display_name' => 'Kellan', 'bio' => 'Developer']);

    ob_start();
    (new AuthController())->updateProfile([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertTrue($body['success']);
    $this->assertEquals('Kellan', $body['data']['display_name']);
  }

  public function test_update_profile_fails_with_duplicate_display_name(): void
  {
    $user1 = $this->createTestUser('user1', 'user1@test.com');
    $user2 = $this->createTestUser('user2', 'user2@test.com');

    $this->users->update($user1['id'], ['display_name' => 'TakenName']);

    $token = JWT::encode(['user_id' => $user2['id']]);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

    $this->mockInput(['display_name' => 'TakenName']);

    ob_start();
    (new AuthController())->updateProfile([]);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Display name is already taken', $body['error']);
  }
}