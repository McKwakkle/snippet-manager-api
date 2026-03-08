<?php

namespace Tests\Helpers;

use App\Helpers\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
  protected function setUp(): void
  {
    if (!headers_sent()) {
      header_remove();
    }
  }

  private function captureResponse(callable $fn): array
  {
    Response::disableExit();
    ob_start();
    $fn();
    $output = ob_get_clean();
    Response::enableExit();

    return json_decode($output, true) ?? [];
  }

  public function test_success_returns_correct_shape(): void
  {
    $response = $this->captureResponse(function () {
      Response::success(['id' => 1, 'name' => 'test']);
    });

    $this->assertTrue($response['success']);
    $this->assertEquals(['id' => 1, 'name' => 'test'], $response['data']);
  }

  public function test_created_returns_correct_shape(): void
  {
    $response = $this->captureResponse(function () {
      Response::created(['id' => 1]);
    });

    $this->assertTrue($response['success']);
    $this->assertEquals(['id' => 1], $response['data']);
  }

  public function test_error_returns_correct_shape(): void
  {
    $response = $this->captureResponse(function () {
      Response::error('Something went wrong');
    });

    $this->assertFalse($response['success']);
    $this->assertEquals('Something went wrong', $response['error']);
  }

  public function test_not_found_returns_correct_shape(): void
  {
    $response = $this->captureResponse(function () {
      Response::notFound('User not found');
    });

    $this->assertFalse($response['success']);
    $this->assertEquals('User not found', $response['error']);
  }

  public function test_unauthorized_returns_correct_shape(): void
  {
    $response = $this->captureResponse(function () {
      Response::unauthorized();
    });

    $this->assertFalse($response['success']);
    $this->assertEquals('Unauthorized', $response['error']);
  }

  public function test_forbidden_returns_correct_shape(): void
  {
    $response = $this->captureResponse(function () {
      Response::forbidden();
    });

    $this->assertFalse($response['success']);
    $this->assertEquals('Forbidden', $response['error']);
  }

  public function test_conflict_returns_correct_shape(): void
  {
    $response = $this->captureResponse(function () {
      Response::conflict('Already exists');
    });

    $this->assertFalse($response['success']);
    $this->assertEquals('Already exists', $response['error']);
  }

  public function test_success_with_no_data_returns_null(): void
  {
    $response = $this->captureResponse(function () {
      Response::success();
    });

    $this->assertTrue($response['success']);
    $this->assertNull($response['data']);
  }
}