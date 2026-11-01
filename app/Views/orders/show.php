
<?php
/**
 * Expects:
 * $order (array)
 * $appUrl (string)
 *
 * IMPORTANT:
 * The controller must verify that the order
 * belongs to the authenticated user.
 */

$appUrl = rtrim($appUrl ?? '', '/');
$order = $order ?? null;

$pageTitle = 'Order Details';
require __DIR__ . '/../layouts/user-header.php';
?>

<section class="user-page">
    <div class="user-container">

        <p class="user-eyebrow">Purchase details</p>

        <?php if (!empty($order) && isset($order['id'])): ?>

            <h1 class="user-page-title">
                Order #<?= (int) $order['id'] ?>
            </h1>

            <p class="user-page-subtitle">
                Review your order information.
            </p>

            <div class="user-form-card">

                <dl class="user-order-details">

                    <dt>Order Number</dt>
                    <dd>
                        #<?= (int) $order['id'] ?>
                    </dd>

                    <dt>Status</dt>
                    <dd>
                        <?= htmlspecialchars((string) ($order['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </dd>

                    <dt>Total</dt>
                    <dd>
                        <?= htmlspecialchars((string) ($order['total_amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </dd>

                    <dt>Shipping Address</dt>
                    <dd>
                        <?= nl2br(htmlspecialchars((string) ($order['shipping_address'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                    </dd>

                    <dt>Phone Number</dt>
                    <dd>
                        <?= htmlspecialchars((string) ($order['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </dd>

                    <dt>Placed On</dt>
                    <dd>
                        <?= htmlspecialchars((string) ($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </dd>

                </dl>

            </div>

            <div class="user-form-actions">
                <a
                    class="user-button-secondary"
                    href="<?= htmlspecialchars($appUrl . '/orders', ENT_QUOTES, 'UTF-8') ?>">
                    Back to Orders
                </a>
            </div>

        <?php else: ?>

            <div class="user-empty-state">

                <span class="user-empty-icon" aria-hidden="true">
                    📦
                </span>

                <h1 class="user-page-title">
                    Order Not Found
                </h1>

                <p>
                    This order is unavailable.
                </p>

                <a
                    class="user-button"
                    href="<?= htmlspecialchars($appUrl . '/orders', ENT_QUOTES, 'UTF-8') ?>">
                    Back to Orders
                </a>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
