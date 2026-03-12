<?php

namespace Tests\Middleware;

use App\Helpers\Response;
use App\Middleware\OwnershipMiddleware;
use PHPUnit\Framework\TestCase;

class OwnershipMiddlewareTest extends TestCase
{
  public function test_handle_passes_when_user_owns_snippet(): void
  {
    $auth = (object) ['user_id' => 'abc123'];
    $snippet = ['user_id' => 'abc123'];

    $result = OwnershipMiddleware::handle($auth, $snippet);

    $this->assertTrue($result);
  }

  public function test_handle_fails_when_user_does_not_own_snippet(): void
  {
    $auth = (object) ['user_id' => 'abc123'];
    $snippet = ['user_id' => 'different_user'];

    ob_start();
    $result = OwnershipMiddleware::handle($auth, $snippet);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($result);
    $this->assertFalse($body['success']);
    $this->assertEquals('Snippet not found', $body['error']);
  }

  protected function setUp(): void
  {
    Response::$exitDisabled = true;
  }

  protected function tearDown(): void
  {
    Response::$exitDisabled = false;
  }
}