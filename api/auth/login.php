<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$config = require __DIR__ . '/../config/env.php';

handleCors($config);

$sessionCookie = $config['SESSION_COOKIE'] ?? [];
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $sessionCookie['path'] ?? '/',
    'domain' => '',
    'secure' => (bool) ($sessionCookie['secure'] ?? false),
    'httponly' => (bool) ($sessionCookie['httponly'] ?? true),
    'samesite' => $sessionCookie['samesite'] ?? 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo = getPdo($config);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'POST') {
    errorResponse('method_not_allowed', 'Method not allowed.', 405);
}

$rawBody = file_get_contents('php://input');
$body = [];
if ($rawBody) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

AuthController::login($pdo, $config, $body);
