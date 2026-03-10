<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Models\SnippetModel;
use App\Models\SnippetLinkModel;

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

    if (!$snippet || $snippet['user_id'] !== $auth->user_id) {
      Response::notFound('Snippet not found');
      return;
    }

    $data = $this->getInput();

    $linkedSnippet = $this->snippets->findById((int) ($data['linked_snippet_id'] ?? 0));

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

    if (!$snippet || $snippet['user_id'] !== $auth->user_id) {
      Response::notFound('Snippet not found');
      return;
    }

    $link = $this->links->findById((int) $params['linkId']);

    if (!$link || $link['snippet_id'] !== $snippet['id']) {
      Response::notFound('Link not found');
      return;
    }

    $this->links->delete($link['id']);

    Response::success(['message' => 'Link deleted successfully']);
  }
}