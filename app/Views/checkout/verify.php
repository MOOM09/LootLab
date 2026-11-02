<?php
$pageTitle = 'Verify Phone';
$appUrl = rtrim($appUrl ?? '', '/');
require __DIR__ . '/../layouts/user-header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($appUrl . '/css/checkout-map.css?v=20261102-2', ENT_QUOTES, 'UTF-8') ?>">
<section class="user-page">
  <div class="user-container">
    <div class="user-form-card checkout-otp-card">
      <p class="user-eyebrow">Secure checkout</p>
      <h1 class="user-page-title">Verify your phone</h1>
      <p class="checkout-otp-subtitle">We sent a 6-digit SMS code to <strong><?= htmlspecialchars((string) $maskedPhone, ENT_QUOTES, 'UTF-8') ?></strong>. Enter it below before proceeding to payment or placing your order.</p>
      <?php if (!empty($error)): ?>
        <p class="user-form-message" role="alert" style="color:#ff9b9b"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php if (!empty($notice)): ?>
        <p class="user-form-message" role="status"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <form class="user-form" method="POST" action="<?= htmlspecialchars($appUrl . '/checkout/verify', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="user-field">
          <label for="otp">Verification code</label>
          <input class="user-input" type="text" name="otp" id="otp" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
        </div>
        <button class="user-button" type="submit">Verify &amp; continue</button>
      </form>
      <p class="checkout-otp-help">The code expires in 5 minutes. Maximum 5 attempts. You can request a new SMS after 60 seconds (up to 3 SMS per 15 minutes).</p>
      <form method="POST" action="<?= htmlspecialchars($appUrl . '/checkout/verify/resend', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button class="user-button-secondary" type="submit">Resend SMS code</button>
      </form>
      <p><a href="<?= htmlspecialchars($appUrl . '/checkout', ENT_QUOTES, 'UTF-8') ?>">← Edit shipping address or phone number</a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
