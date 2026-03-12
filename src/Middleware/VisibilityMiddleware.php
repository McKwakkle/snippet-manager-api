<?php

namespace App\Middleware;

use App\Helpers\Response;

class VisibilityMiddleware
{
  public static function handle(array $snippet, ?object $auth = null): bool
  {

    if ($snippet['visibility'] === 'public') {
      return true;
    }

    if ($auth === null) {
      Response::notFound('Snippet not found');
      return false;
    }

    if ($auth->user_id !== $snippet['user_id']) {
      Response::notFound('Snippet not found');
      return false;
    }

    return true;
  }
}