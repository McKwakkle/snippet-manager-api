<?php

namespace Tests\Router;

use App\Helpers\Response;
use App\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
  public function test_get_route_dispatches_correctly(): void
  {
    $router = new Router();
    $router->get('/snippets', [TestController::class, 'index']);

    ob_start();
    $router->dispatch('GET', '/snippets');
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertTrue($body['success']);
    $this->assertEquals('index called', $body['data']);
  }

  public function test_post_route_dispatches_correctly(): void
  {
    $router = new Router();
    $router->post('/snippets', [TestController::class, 'store']);

    ob_start();
    $router->dispatch('POST', '/snippets');
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertTrue($body['success']);
    $this->assertEquals('store called', $body['data']);
  }

  public function test_dynamic_segment_is_extracted_and_passed_to_controller(): void
  {
    $router = new Router();
    $router->get('/snippets/{id}', [TestController::class, 'show']);

    ob_start();
    $router->dispatch('GET', '/snippets/42');
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertTrue($body['success']);
    $this->assertEquals('42', $body['data']);
  }

  public function test_returns_404_for_unknown_route(): void
  {
    $router = new Router();

    ob_start();
    $router->dispatch('GET', '/unknown');
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Route not found', $body['error']);
  }

  public function test_returns_405_for_wrong_method_on_known_route(): void
  {
    $router = new Router();
    $router->get('/snippets', [TestController::class, 'index']);

    ob_start();
    $router->dispatch('DELETE', '/snippets');
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertFalse($body['success']);
    $this->assertEquals('Method not allowed', $body['error']);
  }

  public function test_trailing_slash_is_handled(): void
  {
    $router = new Router();
    $router->get('/snippets', [TestController::class, 'index']);

    ob_start();
    $router->dispatch('GET', '/snippets/');
    $output = ob_get_clean();

    $body = json_decode($output, true);

    $this->assertTrue($body['success']);
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

class TestController
{
  public function index(array $params): void
  {
    Response::success('index called');
  }

  public function store(array $params): void
  {
    Response::success('store called');
  }

  public function show(array $params): void
  {
    Response::success($params['id']);
  }
}