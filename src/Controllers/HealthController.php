<?php

namespace App\Controllers;

use App\Database\Database;
use App\Helpers\Response;

class HealthController
{
  public function check(array $params): void
  {
    try {

      $db = Database::getInstance();
      $db->getConnection();

      Response::success([
        'status' => 'ok',
        'database' => 'connected',
      ]);
    } catch (\Exception $e) {

      http_response_code(503);
      echo json_encode([
        'status' => 'error',
        'database' => 'unreachable',
      ]);
    }
  }
}