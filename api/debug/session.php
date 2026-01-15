<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../utils/cors.php';

$config = require __DIR__ . '/../config/env.php';

handleCors($config);
startSession($config);

$sessionData = $_SESSION ?? [];
foreach ($sessionData as $key => $value) {
    if (stripos((string) $key, 'password') !== false) {
        unset($sessionData[$key]);
    }
}

jsonResponse([
    'success' => true,
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'session' => $sessionData,
]);
