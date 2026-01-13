<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class BookingController
{
    public static function services(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT id, name, duration_min, price_from_rub FROM services ORDER BY id');
        $services = $stmt->fetchAll();
        okResponse(['services' => $services]);
    }

    public static function myBookings(PDO $pdo, array $config): void
    {
        $user = requireAuth($pdo, $config);
        $stmt = $pdo->prepare('SELECT id, service_id, start_at, status, comment, created_at, car_info FROM appointments WHERE user_id = :user_id ORDER BY start_at DESC');
        $stmt->execute(['user_id' => $user['id']]);
        $bookings = $stmt->fetchAll();
        okResponse(['bookings' => $bookings]);
    }

    public static function create(PDO $pdo, array $config, array $input): void
    {
        $user = requireAuth($pdo, $config);

        $serviceId = (int) ($input['service_id'] ?? 0);
        $startAt = isset($input['start_at']) ? trim((string) $input['start_at']) : '';
        $carInfo = isset($input['car_info']) ? trim((string) $input['car_info']) : null;
        $comment = isset($input['comment']) ? trim((string) $input['comment']) : null;
        $customerName = isset($input['customer_name']) ? trim((string) $input['customer_name']) : ($user['full_name'] ?? null);
        $customerPhone = isset($input['customer_phone']) ? trim((string) $input['customer_phone']) : ($user['phone'] ?? null);

        if ($serviceId <= 0 || $startAt === '') {
            errorResponse('validation_error', 'Service and start time are required.');
        }

        try {
            $startDate = new DateTime($startAt);
        } catch (Throwable $e) {
            errorResponse('validation_error', 'Invalid start time.');
        }

        if (!$customerName) {
            errorResponse('validation_error', 'Customer name is required.');
        }
        if (!$customerPhone || !preg_match('/^\+?[0-9\-\s]{7,20}$/', $customerPhone)) {
            errorResponse('validation_error', 'Customer phone is required.');
        }

        $stmt = $pdo->prepare('SELECT id FROM services WHERE id = :id');
        $stmt->execute(['id' => $serviceId]);
        if (!$stmt->fetch()) {
            errorResponse('validation_error', 'Service not found.');
        }

        $stmt = $pdo->prepare('INSERT INTO appointments (user_id, customer_name, customer_phone, car_info, service_id, start_at, status, comment, created_at) VALUES (:user_id, :customer_name, :customer_phone, :car_info, :service_id, :start_at, :status, :comment, NOW())');
        $stmt->execute([
            'user_id' => $user['id'],
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'car_info' => $carInfo,
            'service_id' => $serviceId,
            'start_at' => $startDate->format('Y-m-d H:i:s'),
            'status' => 'new',
            'comment' => $comment,
        ]);

        okResponse(['id' => (int) $pdo->lastInsertId()], 201);
    }

    public static function cancel(PDO $pdo, array $config, int $bookingId): void
    {
        $user = requireAuth($pdo, $config);
        $stmt = $pdo->prepare('UPDATE appointments SET status = :status WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'status' => 'canceled',
            'id' => $bookingId,
            'user_id' => $user['id'],
        ]);

        if ($stmt->rowCount() === 0) {
            errorResponse('not_found', 'Booking not found.', 404);
        }

        okResponse();
    }

    public static function slots(array $query): void
    {
        $date = $query['date'] ?? null;
        okResponse(['date' => $date, 'slots' => []]);
    }
}
