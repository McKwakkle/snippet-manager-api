<?php

namespace Tests\Middleware;

use App\Helpers\JWT;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
  public function test_handle_returns_decoded_payload_with_valid_token(): void
  {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . JWT::encode(['user_id' => 'abc123']);

    $result = AuthMiddleware::handle();

    $this->assertEquals('abc123', $result->user_id);
  }

  public function test_handle_exits_with_no_token(): void
  {
    unset($_SERVER['HTTP_AUTHORIZATION']);
    Response::$exitDisabled = true;

    ob_start();
    AuthMiddleware::handle();
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertFalse($body['success']);
    $this->assertEquals('No token provided', $body['error']);
  }

  public function test_handle_exits_with_invalid_token(): void
  {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer this.is.invalid';
    Response::$exitDisabled = true;

    ob_start();
    AuthMiddleware::handle();
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Invalid or expired token', $body['error']);
  }

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
    unset($_SERVER['HTTP_AUTHORIZATION']);
  }

  protected function tearDown(): void
  {
    Response::$exitDisabled = false;
    unset($_SERVER['HTTP_AUTHORIZATION']);
  }
}