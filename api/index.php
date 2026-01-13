<?php

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/utils/cors.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/OrderController.php';
require_once __DIR__ . '/controllers/BookingController.php';

$config = require __DIR__ . '/config/env.php';

handleCors($config);

$sessionCookie = $config['SESSION_COOKIE'] ?? [];
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (bool) ($sessionCookie['secure'] ?? false),
    'httponly' => (bool) ($sessionCookie['httponly'] ?? true),
    'samesite' => $sessionCookie['samesite'] ?? 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = getPdo($config);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

if (strpos($path, '/api/v1') === 0) {
    $path = substr($path, 7);
} elseif (strpos($path, '/api') === 0) {
    $path = substr($path, 4);
}
if ($path === '') {
    $path = '/';
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$rawBody = file_get_contents('php://input');
$body = [];
if ($rawBody) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

if ($method === 'POST' && $path === '/auth/register') {
    AuthController::register($pdo, $config, $body);
}
if ($method === 'POST' && $path === '/auth/login') {
    AuthController::login($pdo, $config, $body);
}
if ($method === 'POST' && $path === '/auth/logout') {
    AuthController::logout($config);
}
if ($method === 'GET' && $path === '/profile/me') {
    ProfileController::me($pdo, $config);
}
if ($method === 'PATCH' && $path === '/profile/me') {
    ProfileController::update($pdo, $config, $body);
}
if ($method === 'GET' && $path === '/orders/my') {
    OrderController::myOrders($pdo, $config);
}
if ($method === 'GET' && preg_match('#^/orders/my/(\d+)$#', $path, $matches)) {
    OrderController::myOrderDetails($pdo, $config, (int) $matches[1]);
}
if ($method === 'POST' && $path === '/orders') {
    OrderController::create($pdo, $config, $body);
}
if ($method === 'POST' && preg_match('#^/orders/(\d+)/cancel$#', $path, $matches)) {
    OrderController::cancel($pdo, $config, (int) $matches[1]);
}
if ($method === 'GET' && $path === '/booking/services') {
    BookingController::services($pdo);
}
if ($method === 'GET' && $path === '/booking/my') {
    BookingController::myBookings($pdo, $config);
}
if ($method === 'POST' && $path === '/booking') {
    BookingController::create($pdo, $config, $body);
}
if ($method === 'POST' && preg_match('#^/booking/(\d+)/cancel$#', $path, $matches)) {
    BookingController::cancel($pdo, $config, (int) $matches[1]);
}
if ($method === 'GET' && $path === '/booking/slots') {
    BookingController::slots($_GET);
}

errorResponse('not_found', 'Route not found.', 404);
