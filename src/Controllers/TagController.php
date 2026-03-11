<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Validator;
use App\Middleware\AuthMiddleware;
use App\Models\TagModel;

class TagController
{
  private TagModel $tags;

  public function __construct()
  {
    $this->tags = new TagModel();
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

    $tags = $this->tags->findByUserId($auth->user_id);

    Response::success($tags);
  }

  public function store(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $data = $this->getInput();

    $validator = (new Validator($data))
      ->required('name')
      ->max('name', 80);

    if ($validator->fails()) {
      Response::error(json_encode($validator->errors()), 422);
      return;
    }

    $tag = $this->tags->create($auth->user_id, $data['name']);

    Response::created($tag);
  }

  public function destroy(array $params): void
  {
    $auth = AuthMiddleware::handle();
    if ($auth === null)
      return;

    $tag = $this->tags->findById((int) $params['id']);

    if (!$tag || $tag['user_id'] !== $auth->user_id) {
      Response::notFound('Tag not found');
      return;
    }

    $this->tags->delete($tag['id']);

    Response::success(['message' => 'Tag deleted successfully']);
  }
}
