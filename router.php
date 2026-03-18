<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . '/public' . $requestPath;

if (is_file($filePath)) {
    return false;
}

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Router\Router;
use App\Controllers\AuthController;
use App\Controllers\SnippetController;
use App\Controllers\FeedController;
use App\Controllers\SnippetLinkController;
use App\Controllers\TagController;
use App\Controllers\UserController;
use App\Controllers\HealthController;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$router = new Router();

$router->get('/healthz', [HealthController::class, 'check']);
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/auth/verify-reset-token/{token}', [AuthController::class, 'verifyResetToken']);
$router->post('/auth/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/auth/me', [AuthController::class, 'me']);
$router->put('/auth/profile', [AuthController::class, 'updateProfile']);

$router->get('/snippets', [SnippetController::class, 'index']);
$router->get('/snippets/{id}', [SnippetController::class, 'show']);
$router->post('/snippets', [SnippetController::class, 'store']);
$router->put('/snippets/{id}', [SnippetController::class, 'update']);
$router->delete('/snippets/{id}', [SnippetController::class, 'destroy']);

$router->get('/feed/public', [FeedController::class, 'publicFeed']);
$router->get('/feed/following', [FeedController::class, 'followingFeed']);
$router->get('/feed/public/{id}', [FeedController::class, 'publicSnippet']);

$router->post('/snippets/{id}/links', [SnippetLinkController::class, 'store']);
$router->delete('/snippets/{id}/links/{linkId}', [SnippetLinkController::class, 'destroy']);

$router->get('/tags', [TagController::class, 'index']);
$router->post('/tags', [TagController::class, 'store']);
$router->delete('/tags/{id}', [TagController::class, 'destroy']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{display_name}', [UserController::class, 'show']);
$router->post('/users/{display_name}/follow', [UserController::class, 'follow']);
$router->delete('/users/{display_name}/follow', [UserController::class, 'unfollow']);
$router->get('/users/{display_name}/followers', [UserController::class, 'followers']);
$router->get('/users/{display_name}/following', [UserController::class, 'following']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);