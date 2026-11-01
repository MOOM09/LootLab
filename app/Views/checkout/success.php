
<?php
// Order confirmation data
$orderId = (int) ($orderId ?? 0);
$appUrl = rtrim($appUrl ?? '', '/');
$paymentMethod = $paymentMethod ?? 'cod';
$paidAmount = $paidAmount ?? null;

$pageTitle = 'Order confirmation';
require __DIR__ . '/../layouts/user-header.php';
?>

<section class="user-page">
    <div class="user-container">
        <div class="user-empty-state">

            <span class="user-empty-icon" aria-hidden="true">
                ✓
            </span>

            <p class="user-eyebrow">
                Order confirmation
            </p>

            <?php if ($orderId > 0): ?>

                <h1 class="user-page-title">
                    Thank you for your order!
                </h1>

                <p>
                    Your order
                    <strong>
                        #<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8') ?>
                    </strong>
                    was placed successfully.
                </p>

                <?php if ($paymentMethod === 'stripe'): ?>

                    <p>
                        Payment received by card.
                    </p>

                    <?php if ($paidAmount !== null): ?>
                        <p>
                            Amount charged:
                            <strong>
                                <?= htmlspecialchars((string) $paidAmount, ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </p>
                    <?php endif; ?>

                <?php else: ?>

                    <p>
                        Payment method:
                        <strong>Cash on Delivery</strong>
                    </p>

                    <p>
                        You pay when your order arrives.
                    </p>

                <?php endif; ?>

            <?php else: ?>

                <h1 class="user-page-title">
                    No order confirmation available
                </h1>

                <p>
                    We could not find an order to confirm.
                </p>

            <?php endif; ?>

            <a
                class="user-button"
                href="<?= htmlspecialchars($appUrl . '/products', ENT_QUOTES, 'UTF-8') ?>">
                Continue shopping
            </a>

        </div>
    </div>
</section>

<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
