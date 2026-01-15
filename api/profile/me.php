<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../controllers/ProfileController.php';

$config = require __DIR__ . '/../config/env.php';

handleCors($config);
startSession($config);

$pdo = getPdo($config);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    ProfileController::me($pdo, $config);
}

if ($method === 'PATCH') {
    $rawBody = file_get_contents('php://input');
    $body = [];
    if ($rawBody) {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $body = $decoded;
        }
    }

    ProfileController::update($pdo, $config, $body);
}

errorResponse('method_not_allowed', 'Method not allowed.', 405);
