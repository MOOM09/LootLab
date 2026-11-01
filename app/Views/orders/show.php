
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
$items = isset($items) && is_array($items) ? $items : [];
$canReview = $canReview ?? false;

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

                    <?php if (isset($order['delivery_lat'], $order['delivery_lng'])): ?>
                        <dt>Delivery Meeting Point</dt>
                        <dd>
                            <?= htmlspecialchars((string) ($order['meeting_point_label'] ?? 'Pinned location'), ENT_QUOTES, 'UTF-8') ?>
                            <a target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= rawurlencode((string) $order['delivery_lat'] . ',' . (string) $order['delivery_lng']) ?>">Open directions in Google Maps</a>
                        </dd>
                    <?php endif; ?>

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

            <?php if ($items !== []): ?>
            <div class="user-form-card"><h2>Ordered products</h2><div class="user-order-items">
                <?php foreach ($items as $item): ?>
                <div class="user-order-item-row">
                    <span><?= htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8') ?> × <?= (int)$item['quantity'] ?></span>
                    <span><?= number_format((float)$item['unit_price'], 2) ?> JD</span>
                    <?php if ($canReview): ?>
                    <a class="user-button-secondary" href="<?= htmlspecialchars($userAppUrl . '/products/show?id=' . (int)$item['product_id'] . '#user-reviews-heading', ENT_QUOTES, 'UTF-8') ?>">Rate this item (optional)</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div></div>
            <?php endif; ?>
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
