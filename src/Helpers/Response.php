<?php

namespace App\Helpers;

class Response
{
  public static bool $exitDisabled = false;
  public static function json(mixed $data, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    if (!self::$exitDisabled) {
      exit;
    }
  }


  public static function success(mixed $data = null, int $status = 200): void
  {
    self::json([
      'success' => true,
      'data' => $data,
    ], $status);
  }

  public static function created(mixed $data = null): void
  {
    self::success($data, 201);
  }

  public static function error(string $message, int $status = 400): void
  {
    self::json([
      'success' => false,
      'error' => $message,
    ], $status);
  }

  public static function notFound(string $message = 'Not found'): void
  {
    self::error($message, 404);
  }

  public static function unauthorized(string $message = 'Unauthorized'): void
  {
    self::error($message, 401);
  }

  public static function forbidden(string $message = 'Forbidden'): void
  {
    self::error($message, 403);
  }

  public static function conflict(string $message = 'Conflict'): void
  {
    self::error($message, 409);
  }
  public static function disableExit(): void
  {
    self::$exitDisabled = true;
  }

  public static function enableExit(): void
  {
    self::$exitDisabled = false;
  }
}
