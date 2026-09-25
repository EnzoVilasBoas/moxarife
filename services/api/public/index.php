<?php

declare(strict_types=1);

use Moxarife\Api\Config\Environment;
use Moxarife\Api\Controllers\AuthController;
use Moxarife\Api\Controllers\HealthController;
use Moxarife\Api\Controllers\InventoryController;
use Moxarife\Api\Database\Connection;
use Moxarife\Api\Http\Middleware\AuthMiddleware;
use Moxarife\Api\Http\Middleware\CorsMiddleware;
use Moxarife\Api\Http\Middleware\JsonBodyMiddleware;
use Moxarife\Api\Http\Middleware\JsonErrorMiddleware;
use Moxarife\Api\Repositories\InventoryRepository;
use Moxarife\Api\Repositories\RefreshTokenRepository;
use Moxarife\Api\Repositories\UserRepository;
use Moxarife\Api\Services\AuthService;
use Moxarife\Api\Services\InventoryService;
use Moxarife\Api\Services\TokenService;
use Slim\Factory\AppFactory;

$apiRoot = is_file(dirname(__DIR__) . '/vendor/autoload.php') ? dirname(__DIR__) : __DIR__;

require $apiRoot . '/vendor/autoload.php';

$environment = Environment::fromSources($apiRoot . '/.env');
$pdo = Connection::fromEnvironment($environment);

$users = new UserRepository($pdo);
$refreshTokens = new RefreshTokenRepository($pdo);
$tokenService = new TokenService($environment, $users, $refreshTokens);
$authService = new AuthService($users, $tokenService);
$inventoryService = new InventoryService(new InventoryRepository($pdo));

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new JsonBodyMiddleware());
$app->add(new CorsMiddleware($environment));
$app->add(new JsonErrorMiddleware());

$health = new HealthController($pdo);
$auth = new AuthController($authService);
$inventory = new InventoryController($inventoryService);
$authMiddleware = new AuthMiddleware($environment, $users);

$app->get('/health', $health);
$app->post('/api/v1/auth/login', [$auth, 'login']);
$app->post('/api/v1/auth/refresh', [$auth, 'refresh']);
$app->post('/api/v1/auth/logout', [$auth, 'logout']);
$app->get('/api/v1/auth/me', [$auth, 'me'])->add($authMiddleware);

$app->get('/api/v1/inventory/items', [$inventory, 'index'])->add($authMiddleware);
$app->post('/api/v1/inventory/items', [$inventory, 'create'])->add($authMiddleware);
$app->get('/api/v1/inventory/items/{id}', [$inventory, 'show'])->add($authMiddleware);
$app->patch('/api/v1/inventory/items/{id}', [$inventory, 'update'])->add($authMiddleware);
$app->delete('/api/v1/inventory/items/{id}', [$inventory, 'delete'])->add($authMiddleware);
$app->get('/api/v1/inventory/items/{id}/movements', [$inventory, 'movements'])->add($authMiddleware);
$app->post('/api/v1/inventory/movements', [$inventory, 'createMovement'])->add($authMiddleware);

$app->run();
