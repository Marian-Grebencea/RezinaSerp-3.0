<?php

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class OrderController
{
    public static function myOrders(PDO $pdo, array $config): void
    {
        $user = requireAuth($pdo, $config);
        $stmt = $pdo->prepare('SELECT id, status, total_rub, customer_comment, created_at FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $user['id']]);
        $orders = $stmt->fetchAll();
        okResponse(['orders' => $orders]);
    }

    public static function myOrderDetails(PDO $pdo, array $config, int $orderId): void
    {
        $user = requireAuth($pdo, $config);
        $stmt = $pdo->prepare('SELECT id, status, total_rub, customer_comment, created_at FROM orders WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $orderId, 'user_id' => $user['id']]);
        $order = $stmt->fetch();
        if (!$order) {
            errorResponse('not_found', 'Order not found.', 404);
        }

        $stmt = $pdo->prepare('SELECT id, title, qty, price_rub FROM order_items WHERE order_id = :order_id');
        $stmt->execute(['order_id' => $orderId]);
        $items = $stmt->fetchAll();

        okResponse(['order' => $order, 'items' => $items]);
    }

    public static function create(PDO $pdo, array $config, array $input): void
    {
        $user = requireAuth($pdo, $config);
        $items = $input['items'] ?? null;
        $comment = isset($input['comment']) ? trim((string) $input['comment']) : null;

        if (!is_array($items) || count($items) === 0) {
            errorResponse('validation_error', 'Order items are required.');
        }

        $total = 0.0;
        foreach ($items as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $qty = (int) ($item['qty'] ?? 0);
            $price = (float) ($item['price_rub'] ?? 0);

            if ($title === '' || $qty <= 0 || $price < 0) {
                errorResponse('validation_error', 'Invalid order item.');
            }
            $total += $qty * $price;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO orders (user_id, status, total_rub, customer_comment, created_at) VALUES (:user_id, :status, :total_rub, :comment, NOW())');
            $stmt->execute([
                'user_id' => $user['id'],
                'status' => 'new',
                'total_rub' => $total,
                'comment' => $comment,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, title, qty, price_rub) VALUES (:order_id, :title, :qty, :price_rub)');
            foreach ($items as $item) {
                $stmt->execute([
                    'order_id' => $orderId,
                    'title' => trim((string) $item['title']),
                    'qty' => (int) $item['qty'],
                    'price_rub' => (float) $item['price_rub'],
                ]);
            }

            $pdo->commit();
            okResponse(['id' => $orderId], 201);
        } catch (Throwable $e) {
            $pdo->rollBack();
            errorResponse('server_error', 'Failed to create order.', 500);
        }
    }

    public static function cancel(PDO $pdo, array $config, int $orderId): void
    {
        $user = requireAuth($pdo, $config);
        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'status' => 'canceled',
            'id' => $orderId,
            'user_id' => $user['id'],
        ]);

        if ($stmt->rowCount() === 0) {
            errorResponse('not_found', 'Order not found.', 404);
        }

        okResponse();
    }
}
