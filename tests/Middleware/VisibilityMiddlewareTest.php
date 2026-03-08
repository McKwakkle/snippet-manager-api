<?php

namespace Tests\Middleware;

use App\Helpers\Response;
use App\Middleware\VisibilityMiddleware;
use PHPUnit\Framework\TestCase;

class VisibilityMiddlewareTest extends TestCase
{

  public function test_public_snippet_passes_with_no_auth(): void
  {
    $snippet = (object) ['visibility' => 'public', 'user_id' => 'abc123'];

    VisibilityMiddleware::handle($snippet);

    $this->assertTrue(true);
  }

  public function test_public_snippet_passes_with_auth(): void
  {
    $snippet = (object) ['visibility' => 'public', 'user_id' => 'abc123'];
    $auth = (object) ['user_id' => 'different_user'];

    VisibilityMiddleware::handle($snippet, $auth);

    $this->assertTrue(true);
  }

  public function test_private_snippet_passes_when_owner_requests_it(): void
  {
    $snippet = (object) ['visibility' => 'private', 'user_id' => 'abc123'];
    $auth = (object) ['user_id' => 'abc123'];

    VisibilityMiddleware::handle($snippet, $auth);

    $this->assertTrue(true);
  }

  public function test_private_snippet_exits_with_no_auth(): void
  {
    $snippet = (object) ['visibility' => 'private', 'user_id' => 'abc123'];

    ob_start();
    VisibilityMiddleware::handle($snippet);
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Snippet not found', $body['error']);
  }

  public function test_private_snippet_exits_when_non_owner_requests_it(): void
  {
    $snippet = (object) ['visibility' => 'private', 'user_id' => 'abc123'];
    $auth = (object) ['user_id' => 'different_user'];

    ob_start();
    VisibilityMiddleware::handle($snippet, $auth);
    $output = ob_get_clean();

    $body = json_decode($output, true);

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