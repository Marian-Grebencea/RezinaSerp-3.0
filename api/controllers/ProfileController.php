<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class ProfileController
{
    public static function me(PDO $pdo, array $config): void
    {
        $user = requireAuth($pdo, $config);
        okResponse(['user' => $user]);
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

        $stmt = $pdo->prepare('SELECT id, email, phone, full_name, role, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);
        $updated = $stmt->fetch();

        okResponse(['user' => $updated]);
    }
}
