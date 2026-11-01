<?php
/** @var string $appUrl */
/** @var bool $verified */
$pageTitle = $verified ? 'Email verified' : 'Verification link invalid';
require __DIR__ . '/../layouts/user-header.php';
?>
<section class="user-page"><div class="user-container"><div class="user-form-card">
    <p class="user-eyebrow">LootHub account</p>
    <h1 class="user-page-title"><?= $verified ? 'Email verified successfully!' : 'Verification link expired or invalid' ?></h1>
    <p class="user-page-subtitle"><?= $verified ? 'Your email address is confirmed. You can now log in to LootHub.' : 'The link may have expired or already been used. Request a fresh link.' ?></p>
    <div class="user-form-actions"><a class="user-button" href="<?= htmlspecialchars($appUrl . ($verified ? '/login' : '/verify-email/pending'), ENT_QUOTES, 'UTF-8') ?>"><?= $verified ? 'Log in' : 'Resend verification email' ?></a></div>
</div></div></section>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
