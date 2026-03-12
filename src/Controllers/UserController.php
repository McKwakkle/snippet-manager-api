<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Models\FollowModel;
use App\Models\UserModel;

class UserController
{
  private UserModel $users;
  private FollowModel $follows;

  public function __construct()
  {
    $this->users = new UserModel();
    $this->follows = new FollowModel();
  }

  public function index(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $allUsers = $this->users->getAll();

    $result = array_map(
      fn($user) => $this->users->publicProfile($user),
      $allUsers
    );

    Response::success($result);
  }

  public function show(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $user = $this->users->findByDisplayName($params['display_name']);

    if (!$user) {
      Response::notFound('User not found');
      return;
    }

    $profile = $this->users->publicProfile($user);
    $profile['is_following'] = $this->follows->exists($auth->user_id, $user['id']);

    Response::success($profile);
  }

  public function follow(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $user = $this->users->findByDisplayName($params['display_name']);

    if (!$user) {
      Response::notFound('User not found');
      return;
    }

    if ($user['id'] === $auth->user_id) {
      Response::error('You cannot follow yourself', 422);
      return;
    }

    if ($this->follows->exists($auth->user_id, $user['id'])) {
      Response::error('You are already following this user', 422);
      return;
    }

    $this->follows->create($auth->user_id, $user['id']);

    Response::success(['message' => 'User followed successfully']);
  }

  public function unfollow(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $user = $this->users->findByDisplayName($params['display_name']);

    if (!$user) {
      Response::notFound('User not found');
      return;
    }

    $this->follows->delete($auth->user_id, $user['id']);

    Response::success(['message' => 'User unfollowed successfully']);
  }

  public function followers(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $user = $this->users->findByDisplayName($params['display_name']);

    if (!$user) {
      Response::notFound('User not found');
      return;
    }

    Response::success($this->follows->getFollowers($user['id']));
  }

  public function following(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $user = $this->users->findByDisplayName($params['display_name']);

    if (!$user) {
      Response::notFound('User not found');
      return;
    }

    Response::success($this->follows->getFollowing($user['id']));
  }
}
