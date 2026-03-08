<?php

namespace App\Middleware;

use App\Helpers\Response;

class VisibilityMiddleware
{
  public static function handle(object $snippet, ?object $auth = null): void
  {
    if ($snippet->visibility === 'public') {
      return;
    }

    if ($auth === null) {
      Response::notFound('Snippet not found');
      return;
    }

    if ($auth->user_id !== $snippet->user_id) {
      Response::notFound('Snippet not found');
      return;
    }
  }
}
