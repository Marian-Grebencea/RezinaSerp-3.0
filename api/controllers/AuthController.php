<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';

class AuthController
{
    public static function register(PDO $pdo, array $config, array $input): void
    {
        if (!empty($config['DEBUG'])) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }

        $email = isset($input['email']) ? trim((string) $input['email']) : '';
        $phone = isset($input['phone']) ? trim((string) $input['phone']) : '';
        $password = (string) ($input['password'] ?? '');
        $fullName = isset($input['full_name']) ? trim((string) $input['full_name']) : '';

        if ($fullName === '') {
            errorResponse('validation_error', 'Full name is required.');
        }

        if ($email === '') {
            errorResponse('validation_error', 'Email is required.');
        }

        if ($phone === '') {
            errorResponse('validation_error', 'Phone is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorResponse('validation_error', 'Invalid email format.');
        }

        if (!preg_match('/^\+?[0-9\-\s]{7,20}$/', $phone)) {
            errorResponse('validation_error', 'Invalid phone format.');
        }

        if (mb_strlen($password) < 8) {
            errorResponse('validation_error', 'Password must be at least 8 characters.');
        }

        try {
            $stmt = $pdo->prepare('SELECT email, phone FROM users WHERE email = :email OR phone = :phone');
            $stmt->execute([
                'email' => $email,
                'phone' => $phone,
            ]);
            $existing = $stmt->fetch();
            if ($existing) {
                if (!empty($existing['email']) && $existing['email'] === $email) {
                    errorResponse('conflict', 'Email already exists.', 409);
                }
                if (!empty($existing['phone']) && $existing['phone'] === $phone) {
                    errorResponse('conflict', 'Phone already exists.', 409);
                }
                errorResponse('conflict', 'User already exists.', 409);
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (email, phone, password_hash, full_name, created_at) VALUES (:email, :phone, :hash, :full_name, NOW())');
            $stmt->execute([
                'email' => $email,
                'phone' => $phone,
                'hash' => $hash,
                'full_name' => $fullName,
            ]);
        } catch (PDOException $exception) {
            $errorInfo = $exception->errorInfo ?? [];
            if (($errorInfo[0] ?? '') === '23000' && (int) ($errorInfo[1] ?? 0) === 1062) {
                $message = (string) ($errorInfo[2] ?? $exception->getMessage());
                if (stripos($message, 'email') !== false) {
                    errorResponse('conflict', 'Email already exists.', 409);
                }
                if (stripos($message, 'phone') !== false) {
                    errorResponse('conflict', 'Phone already exists.', 409);
                }
                errorResponse('conflict', 'User already exists.', 409);
            }

            errorResponse('db_error', 'Database error while creating user.', 500);
        }

        $userId = (int) $pdo->lastInsertId();

        if (($config['AUTH_MODE'] ?? 'session') === 'session') {
            startSession();
            $_SESSION['user_id'] = $userId;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['full_name'] = $fullName;
        }

        okResponse(['id' => $userId], 201);
    }

    public static function login(PDO $pdo, array $config, array $input): void
    {
        startSession();

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

        $stmt = $pdo->prepare('SELECT id, email, phone, password_hash, full_name, created_at, last_login FROM users WHERE email = :identifier OR phone = :identifier');
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
        $_SESSION['email'] = $user['email'] ?? null;
        $_SESSION['phone'] = $user['phone'] ?? null;
        $_SESSION['full_name'] = $user['full_name'] ?? null;
        $updateStmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $updateStmt->execute(['id' => $user['id']]);
        okResponse(['id' => (int) $user['id']]);
    }

    public static function logout(array $config): void
    {
        if (($config['AUTH_MODE'] ?? 'session') === 'session') {
            startSession();
            session_unset();
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
