<?php

function startSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/RezinaSerp-3.0/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

startSession();
