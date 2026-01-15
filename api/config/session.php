<?php

function startSession(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionCookie = $config['SESSION_COOKIE'] ?? [];
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $sessionCookie['path'] ?? '/RezinaSerp-3.0/',
        'domain' => '',
        'secure' => (bool) ($sessionCookie['secure'] ?? false),
        'httponly' => (bool) ($sessionCookie['httponly'] ?? true),
        'samesite' => $sessionCookie['samesite'] ?? 'Lax',
    ]);

    session_start();
}
