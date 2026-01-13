<?php

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function okResponse(array $data = [], int $status = 200): void
{
    jsonResponse(['ok' => true, 'data' => $data], $status);
}

function errorResponse(string $code, string $message, int $status = 400): void
{
    jsonResponse(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], $status);
}
