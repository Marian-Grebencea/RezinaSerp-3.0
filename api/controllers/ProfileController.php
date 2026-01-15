<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../config/session.php';

class ProfileController
{
    public static function me(PDO $pdo, array $config): void
    {
        if (($config['AUTH_MODE'] ?? 'session') === 'jwt') {
            $user = requireAuth($pdo, $config);
            jsonResponse([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'] ?? null,
                    'phone' => $user['phone'] ?? null,
                    'email' => $user['email'] ?? null,
                ],
            ]);
        }

        startSession($config);

        if (empty($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $stmt = $pdo->prepare('SELECT id, full_name, phone, email FROM users WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'] ?? null,
                'phone' => $user['phone'] ?? null,
                'email' => $user['email'] ?? null,
            ],
        ]);
    }

    public static function update(PDO $pdo, array $config, array $input): void
    {
        $user = requireAuth($pdo, $config);

        $email = array_key_exists('email', $input) ? trim((string) $input['email']) : $user['email'];
        $phone = array_key_exists('phone', $input) ? trim((string) $input['phone']) : $user['phone'];
        $fullName = array_key_exists('full_name', $input) ? trim((string) $input['full_name']) : $user['full_name'];

        if ($email !== '' && $email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorResponse('validation_error', 'Invalid email format.');
        }
        if ($phone !== '' && $phone !== null && !preg_match('/^\+?[0-9\-\s]{7,20}$/', $phone)) {
            errorResponse('validation_error', 'Invalid phone format.');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE (email = :email OR phone = :phone) AND id != :id');
        $stmt->execute([
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'id' => $user['id'],
        ]);
        if ($stmt->fetch()) {
            errorResponse('conflict', 'Email or phone already in use.', 409);
        }

        $stmt = $pdo->prepare('UPDATE users SET email = :email, phone = :phone, full_name = :full_name WHERE id = :id');
        $stmt->execute([
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'full_name' => $fullName !== '' ? $fullName : null,
            'id' => $user['id'],
        ]);

        $stmt = $pdo->prepare('SELECT id, email, phone, full_name, created_at, last_login FROM users WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);
        $updated = $stmt->fetch();

        okResponse(['user' => $updated]);
    }
}
