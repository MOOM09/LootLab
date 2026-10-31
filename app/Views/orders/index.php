<?php

/**
 * Expects: $orders (array of orders rows), $appUrl (string).
 */
$appUrl = rtrim($appUrl ?? '', '/');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - LootLab</title>
</head>

<body>
    <h1>My Orders</h1>

    <?php if (empty($orders)): ?>
        <p>You have no orders yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Placed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <a href="<?= htmlspecialchars($appUrl . '/orders/show?id=' . (int) $order['id'], ENT_QUOTES, 'UTF-8') ?>">
                                #<?= (int) $order['id'] ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars((string) $order['total_amount'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $order['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>