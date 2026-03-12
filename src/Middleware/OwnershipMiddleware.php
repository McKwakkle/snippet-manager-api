<?php

namespace App\Middleware;

use App\Helpers\Response;

class OwnershipMiddleware
{
  public static function handle(object $auth, array $snippet): bool
  {
    if ($auth->user_id !== $snippet['user_id']) {
      Response::notFound('Snippet not found');
      return false;
    }

    return true;
  }
}