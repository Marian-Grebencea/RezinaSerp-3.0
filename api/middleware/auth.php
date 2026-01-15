<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';

function requireAuth(PDO $pdo, array $config): array
{
    if (($config['AUTH_MODE'] ?? 'session') === 'jwt') {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$header || !preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            errorResponse('unauthorized', 'Authorization required.', 401);
        }
        $payload = jwtDecode($matches[1], $config['JWT_SECRET']);
        if (!$payload || empty($payload['user_id'])) {
            errorResponse('unauthorized', 'Invalid token.', 401);
        }
        $stmt = $pdo->prepare('SELECT id, email, phone, full_name, created_at, last_login FROM users WHERE id = :id');
        $stmt->execute(['id' => $payload['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            errorResponse('unauthorized', 'User not found.', 401);
        }
        return $user;
    }

    startSession();

    if (empty($_SESSION['user_id'])) {
        errorResponse('unauthorized', 'Authorization required.', 401);
    }

    $stmt = $pdo->prepare('SELECT id, email, phone, full_name, created_at, last_login FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        errorResponse('unauthorized', 'User not found.', 401);
    }

    return $user;
}
