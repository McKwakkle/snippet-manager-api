<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\OwnershipMiddleware;
use App\Models\SnippetModel;
use App\Models\SnippetLinkModel;
use App\Helpers\Validator;

class SnippetLinkController
{
  private SnippetModel $snippets;
  private SnippetLinkModel $links;

  public function __construct()
  {
    $this->snippets = new SnippetModel();
    $this->links = new SnippetLinkModel();
  }

  private function getInput(): array
  {
    if (isset($GLOBALS['__mock_input'])) {
      return json_decode($GLOBALS['__mock_input'], true) ?? [];
    }
    return json_decode(file_get_contents('php://input'), true) ?? [];
  }

  public function store(array $params): void
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

    $validator = (new Validator(($data)))
      ->required('linked_snippet_id')
      ->integer('linked_snippet_id');

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $linkedSnippet = $this->snippets->findById((int) $data['linked_snippet_id']);

    if (!$linkedSnippet) {
      Response::notFound('Linked snippet not found');
      return;
    }

    $link = $this->links->create($snippet['id'], $linkedSnippet['id'], $data['label'] ?? null);

    Response::created($link);
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

    $link = $this->links->findById((int) $params['linkId']);

    if (!$link || $link['snippet_id'] !== $snippet['id']) {
      Response::notFound('Link not found');
      return;
    }

    $this->links->delete($link['id']);

    Response::success(['message' => 'Link deleted successfully']);
  }
}