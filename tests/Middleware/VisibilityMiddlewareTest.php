<?php

namespace Tests\Middleware;

use App\Helpers\Response;
use App\Middleware\VisibilityMiddleware;
use PHPUnit\Framework\TestCase;

class VisibilityMiddlewareTest extends TestCase
{
  public function test_public_snippet_passes_with_no_auth(): void
  {
    $snippet = ['visibility' => 'public', 'user_id' => 'abc123'];

    $result = VisibilityMiddleware::handle($snippet);

    $this->assertTrue($result);
  }

  public function test_public_snippet_passes_with_auth(): void
  {
    $snippet = ['visibility' => 'public', 'user_id' => 'abc123'];
    $auth = (object) ['user_id' => 'different_user'];

    $result = VisibilityMiddleware::handle($snippet, $auth);

    $this->assertTrue($result);
  }

  public function test_private_snippet_passes_when_owner_requests_it(): void
  {
    $snippet = ['visibility' => 'private', 'user_id' => 'abc123'];
    $auth = (object) ['user_id' => 'abc123'];

    $result = VisibilityMiddleware::handle($snippet, $auth);

    $this->assertTrue($result);
  }

  public function test_private_snippet_fails_with_no_auth(): void
  {
    $snippet = ['visibility' => 'private', 'user_id' => 'abc123'];

    ob_start();
    $result = VisibilityMiddleware::handle($snippet);
    $body = json_decode(ob_get_clean(), true);

    $this->assertFalse($result);
    $this->assertFalse($body['success']);
    $this->assertEquals('Snippet not found', $body['error']);
  }

  public function test_private_snippet_fails_when_non_owner_requests_it(): void
  {
    $snippet = ['visibility' => 'private', 'user_id' => 'abc123'];
    $auth = (object) ['user_id' => 'different_user'];

    ob_start();
    $result = VisibilityMiddleware::handle($snippet, $auth);
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