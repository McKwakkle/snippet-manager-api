<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Middleware\OwnershipMiddleware;
use App\Models\SnippetModel;

class SnippetController
{
  private SnippetModel $snippets;

  public function __construct()
  {
    $this->snippets = new SnippetModel();
  }

  private function getInput(): array
  {
    if (isset($GLOBALS['__mock_input'])) {
      return json_decode($GLOBALS['__mock_input'], true) ?? [];
    }
    return json_decode(file_get_contents('php://input'), true) ?? [];
  }

  public function index(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $snippets = $this->snippets->findByUserId($auth->user_id);

    Response::success($snippets);
  }

  public function store(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $data = $this->getInput();

    $validator = (new Validator($data))
      ->required('title')
      ->max('title', 255)
      ->required('code')
      ->required('language')
      ->max('language', 50);

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $snippet = $this->snippets->create($auth->user_id, $data);

    Response::created($snippet);
  }

  public function show(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $snippet = $this->snippets->findById((int) $params['id']);

    // Check existence first — middleware needs a valid array to work with
    if (!$snippet) {
      Response::notFound('Snippet not found');
      return;
    }

    if (!OwnershipMiddleware::handle($auth, $snippet))
      return;

    Response::success($snippet);
  }

  public function update(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $snippet = $this->snippets->findById((int) $params['id']);

    if (!$snippet) {
      Response::notFound('Snippet not found');
      return;
    }

    if (!OwnershipMiddleware::handle($auth, $snippet))
      return;

    $data = $this->getInput();

    $validator = (new Validator($data))
      ->max('title', 255)
      ->max('language', 50);

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $updated = $this->snippets->update($snippet['id'], $data);

    Response::success($updated);
  }

  public function destroy(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $snippet = $this->snippets->findById((int) $params['id']);

    if (!$snippet) {
      Response::notFound('Snippet not found');
      return;
    }

    if (!OwnershipMiddleware::handle($auth, $snippet))
      return;

    $this->snippets->delete($snippet['id']);

    Response::success(['message' => 'Snippet deleted successfully']);
  }
}