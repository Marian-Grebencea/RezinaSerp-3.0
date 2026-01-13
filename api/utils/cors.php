<?php

function handleCors(array $config): void
{
    if (empty($config['CORS_ENABLED'])) {
        return;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = $config['CORS_ORIGINS'] ?? [];

    if ($origin && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
