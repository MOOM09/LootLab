
<?php
/**
 * Expects:
 * $orders (array)
 * $appUrl (string)
 */

$appUrl = rtrim($appUrl ?? '', '/');
$orders = $orders ?? [];

$pageTitle = 'My Orders';
require __DIR__ . '/../layouts/user-header.php';
?>

<section class="user-page">
    <div class="user-container">

        <p class="user-eyebrow">Your purchase history</p>

        <h1 class="user-page-title">My Orders</h1>

        <p class="user-page-subtitle">
            Track your order status and review previous purchases.
        </p>

        <?php if (empty($orders)): ?>

            <div class="user-empty-state">
                <span class="user-empty-icon" aria-hidden="true">
                    📦
                </span>

                <h2>No orders yet</h2>

                <p>
                    You haven't placed any orders yet.
                </p>

                <a
                    class="user-button"
                    href="<?= htmlspecialchars($appUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">
                    Start Shopping
                </a>
            </div>

        <?php else: ?>

            <div class="user-form-card">

                <div style="overflow-x: auto;">
                    <table class="user-table" style="width: 100%;">

                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Placed</th>
                                <th>Details</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>

                                    <td>
                                        #<?= (int) $order['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string) $order['total_amount'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string) $order['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <a
                                            class="user-button-secondary"
                                            href="<?= htmlspecialchars($appUrl . '/orders/show?id=' . (int) $order['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            View Order
                                        </a>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
