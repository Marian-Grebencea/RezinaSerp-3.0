<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$config = require __DIR__ . '/../config/env.php';

handleCors($config);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'POST') {
    errorResponse('method_not_allowed', 'Method not allowed.', 405);
}

AuthController::logout($config);
