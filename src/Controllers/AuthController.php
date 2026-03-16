<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Validator;
use App\Helpers\JWT;
use App\Middleware\AuthMiddleware;
use App\Models\UserModel;
use App\Models\PasswordResetModel;

class AuthController
{
  private UserModel $users;
  private PasswordResetModel $resets;

  public function __construct()
  {
    $this->users = new UserModel();
    $this->resets = new PasswordResetModel();
  }

  private function getInput(): array
  {
    if (isset($GLOBALS['__mock_input'])) {
      return json_decode($GLOBALS['__mock_input'], true) ?? [];
    }
    return json_decode(file_get_contents('php://input'), true) ?? [];
  }

  public function register(array $params): void
  {
    $data = $this->getInput();

    $validator = (new Validator($data))
      ->required('username')
      ->min('username', 3)
      ->max('username', 50)
      ->required('email')
      ->email('email')
      ->required('password')
      // password() includes the min 8 check plus complexity rules,
      // so ->min('password', 8) is no longer needed here
      ->password('password')
      ->required('password_confirmation')
      ->matches('password', 'password_confirmation');

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    if ($this->users->findByEmail($data['email'])) {
      Response::conflict('Email is already in use');
      return;
    }

    if ($this->users->findByUsername($data['username'])) {
      Response::conflict('Username is already taken');
      return;
    }

    $user = $this->users->create(
      $data['username'],
      $data['email'],
      password_hash($data['password'], PASSWORD_BCRYPT)
    );

    $token = JWT::encode(['user_id' => $user['id']]);

    Response::created([
      'token' => $token,
      'user' => $this->users->publicProfile($user),
    ]);
  }

  public function login(array $params): void
  {
    $data = $this->getInput();

    $validator = (new Validator($data))
      ->required('email')
      ->email('email')
      ->required('password');

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $user = $this->users->findByEmail($data['email']);

    // Both "user not found" and "wrong password" return the same message —
    // this prevents attackers from using the response to confirm valid emails
    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
      Response::unauthorized('Invalid email or password');
      return;
    }

    $token = JWT::encode(['user_id' => $user['id']]);

    Response::success([
      'token' => $token,
      'user' => $this->users->publicProfile($user),
    ]);
  }

  public function forgotPassword(array $params): void
  {
    $data = $this->getInput();

    $validator = (new Validator($data))
      ->required('email')
      ->email('email');

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $user = $this->users->findByEmail($data['email']);

    if (!$user) {
      Response::success(['message' => 'If that email exists, a reset link has been sent']);
      return;
    }

    $reset = $this->resets->create($data['email']);

    // TODO: Integrate an email service here (e.g. SendGrid, Mailgun)
    // to dispatch a password reset email containing $reset['token'].
    // The token must never be returned in the API response in production.
    Response::success([
      'message' => 'If that email exists, a reset link has been sent',
    ]);
  }

  public function verifyResetToken(array $params): void
  {
    $token = $params['token'] ?? '';
    $reset = $this->resets->findByToken($token);

    if (!$reset || !$this->resets->isValid($reset)) {
      Response::notFound('Invalid or expired reset token');
      return;
    }

    Response::success(['message' => 'Token is valid']);
  }

  public function resetPassword(array $params): void
  {
    $data = $this->getInput();

    $validator = (new Validator($data))
      ->required('token')
      ->required('password')
      // Same complexity rules apply when setting a new password via reset
      ->password('password')
      ->required('password_confirmation')
      ->matches('password', 'password_confirmation');

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $reset = $this->resets->findByToken($data['token']);

    if (!$reset || !$this->resets->isValid($reset)) {
      Response::notFound('Invalid or expired reset token');
      return;
    }

    $user = $this->users->findByEmail($reset['email']);

    if (!$user) {
      Response::notFound('User not found');
      return;
    }

    $this->users->update(
      $user['id'],
      ['password_hash' => password_hash($data['password'], PASSWORD_BCRYPT)]
    );

    $this->resets->markUsed($data['token']);

    Response::success(['message' => 'Password has been reset successfully']);
  }

  public function me(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $user = $this->users->findById($auth->user_id);

    if (!$user) {
      Response::notFound('User not found');
      return;
    }

    Response::success($this->users->publicProfile($user));
  }

  public function updateProfile(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $data = $this->getInput();

    $validator = (new Validator($data))
      // min 3 ensures display names are meaningful, not just a single character
      ->min('display_name', 3)
      ->max('display_name', 80)
      ->max('bio', 500);

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $allowed = ['display_name', 'bio'];
    $fields = array_filter(
      $data,
      fn($key) => in_array($key, $allowed),
      ARRAY_FILTER_USE_KEY
    );

    if (!empty($fields['display_name'])) {
      $existing = $this->users->findByDisplayName($fields['display_name']);
      if ($existing && $existing['id'] !== $auth->user_id) {
        Response::conflict('Display name is already taken');
        return;
      }
    }

    $user = $this->users->update($auth->user_id, $fields);

    Response::success($this->users->publicProfile($user));
  }
}