<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Models\SnippetModel;

class FeedController
{
  private SnippetModel $snippets;

  public function __construct()
  {
    $this->snippets = new SnippetModel();
  }

  public function publicFeed(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $snippets = $this->snippets->getPublic();

    foreach ($snippets as &$snippet) {
      if ($snippet['anonymous']) {
        unset($snippet['username']);
        unset($snippet['display_name']);
        unset($snippet['user_id']);
      }
      $snippet['is_owner'] = ($snippet['user_id'] ?? null) === $auth->user_id;
    }

    Response::success($snippets);
  }

  public function followingFeed(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $snippets = $this->snippets->getByFollowing($auth->user_id);

    foreach ($snippets as &$snippet) {
      if ($snippet['anonymous']) {
        unset($snippet['username']);
        unset($snippet['display_name']);
        unset($snippet['user_id']);
      }
      $snippet['is_owner'] = ($snippet['user_id'] ?? null) === $auth->user_id;
    }

    Response::success($snippets);
  }

  public function publicSnippet(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $snippet = $this->snippets->findById((int) $params['id']);

    if (!$snippet || $snippet['visibility'] !== 'public') {
      Response::notFound('Snippet not found');
      return;
    }

    if ($snippet['anonymous']) {
      unset($snippet['user_id']);
    }

    $snippet['is_owner'] = ($snippet['user_id'] ?? null) === $auth->user_id;

    Response::success($snippet);
  }
}
