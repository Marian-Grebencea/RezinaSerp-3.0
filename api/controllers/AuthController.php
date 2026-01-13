<?php

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';

class AuthController
{
    public static function register(PDO $pdo, array $config, array $input): void
    {
        $email = isset($input['email']) ? trim((string) $input['email']) : '';
        $phone = isset($input['phone']) ? trim((string) $input['phone']) : '';
        $password = (string) ($input['password'] ?? '');
        $fullName = isset($input['full_name']) ? trim((string) $input['full_name']) : '';

        if ($email === '' && $phone === '') {
            errorResponse('validation_error', 'Email or phone is required.');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorResponse('validation_error', 'Invalid email format.');
        }

        if ($phone !== '' && !preg_match('/^\+?[0-9\-\s]{7,20}$/', $phone)) {
            errorResponse('validation_error', 'Invalid phone format.');
        }

        if (mb_strlen($password) < 8) {
            errorResponse('validation_error', 'Password must be at least 8 characters.');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR phone = :phone');
        $stmt->execute([
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
        ]);
        if ($stmt->fetch()) {
            errorResponse('conflict', 'User already exists.', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (email, phone, password_hash, full_name, role, created_at) VALUES (:email, :phone, :hash, :full_name, :role, NOW())');
        $stmt->execute([
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'hash' => $hash,
            'full_name' => $fullName !== '' ? $fullName : null,
            'role' => 'client',
        ]);

        $userId = (int) $pdo->lastInsertId();
        okResponse(['id' => $userId], 201);
    }

    public static function login(PDO $pdo, array $config, array $input): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $identifier = isset($input['email']) ? trim((string) $input['email']) : '';
        if ($identifier === '') {
            $identifier = isset($input['phone']) ? trim((string) $input['phone']) : '';
        }
        $password = (string) ($input['password'] ?? '');

        if ($identifier === '' || $password === '') {
            errorResponse('validation_error', 'Credentials are required.');
        }

        $attemptKey = strtolower($identifier) . '|' . ($_SERVER['REMOTE_ADDR'] ?? '');
        $attempts = $_SESSION['login_attempts'][$attemptKey] ?? ['count' => 0, 'first' => time()];
        if (time() - $attempts['first'] > 300) {
            $attempts = ['count' => 0, 'first' => time()];
        }
        if ($attempts['count'] >= 5) {
            errorResponse('too_many_attempts', 'Too many attempts. Try later.', 429);
        }

        $stmt = $pdo->prepare('SELECT id, email, phone, password_hash, full_name, role, created_at FROM users WHERE email = :identifier OR phone = :identifier');
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $attempts['count']++;
            $_SESSION['login_attempts'][$attemptKey] = $attempts;
            errorResponse('invalid_credentials', 'Invalid credentials.', 401);
        }

        unset($_SESSION['login_attempts'][$attemptKey]);

        if (($config['AUTH_MODE'] ?? 'session') === 'jwt') {
            $payload = [
                'user_id' => (int) $user['id'],
                'exp' => time() + (int) ($config['JWT_TTL'] ?? 3600),
            ];
            $token = jwtEncode($payload, $config['JWT_SECRET']);
            okResponse(['token' => $token]);
        }

        $_SESSION['user_id'] = (int) $user['id'];
        okResponse(['id' => (int) $user['id']]);
    }

    public static function logout(array $config): void
    {
        if (($config['AUTH_MODE'] ?? 'session') === 'session') {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }

        okResponse();
    }
}
