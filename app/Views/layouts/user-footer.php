</main>
<footer class="user-footer"><div class="user-footer-inner"><a class="user-brand user-brand-small" href="<?= htmlspecialchars($userAppUrl . '/', ENT_QUOTES, 'UTF-8') ?>">LOOT <strong>HUB</strong></a><span class="user-footer-note">© <?= date('Y') ?> LootLab. All rights reserved.</span></div></footer>
<div class="loote-assistant" data-loote-assistant>
<div class="loote-sparks" aria-hidden="true">✦ ✧ ✦</div>
<div class="loote-greeting" data-loote-greeting role="status" aria-live="polite" hidden><strong>MY NAME IS LOOTE!</strong><span>CAN I HELP YOU?</span><button type="button" data-loote-dismiss aria-label="Dismiss greeting">×</button></div>
<a class="loote-face" href="<?= htmlspecialchars($userAppUrl . '/ai.php', ENT_QUOTES, 'UTF-8') ?>" title="Chat with Loote" aria-label="Chat with Loote AI">
<svg viewBox="0 0 76 76" width="76" height="76" fill="none" aria-hidden="true">
  <defs><radialGradient id="looteCloudGlow" cx="0" cy="0" r="1" gradientTransform="translate(38 33) rotate(90) scale(40)"><stop stop-color="#5533a5"/><stop offset="1" stop-color="#151027"/></radialGradient></defs>
  <circle cx="38" cy="38" r="36" fill="url(#looteCloudGlow)" stroke="#9f7bea" stroke-width="1.5"/>
  <path d="M22 53c-8 0-12-5-12-12 0-6 4-11 10-12 2-10 9-16 19-16 9 0 16 6 18 14 7 1 11 6 11 13 0 8-6 13-14 13H22Z" fill="#9575e8" fill-opacity=".32" stroke="#e3d2ff" stroke-width="2.4" stroke-linejoin="round"/>
  <g stroke="#d8c9ff" stroke-width="2" stroke-linecap="round"><path d="M22 33v-8M38 22v-8M54 32v-7M22 52v7M38 52v10M54 51v8"/></g>
  <g fill="#f9e8ff"><circle cx="22" cy="25" r="3"/><circle cx="38" cy="14" r="3"/><circle cx="54" cy="25" r="3"/><circle cx="22" cy="59" r="3"/><circle cx="38" cy="62" r="3"/><circle cx="54" cy="59" r="3"/></g>
  <rect x="24" y="32" width="28" height="16" rx="7" fill="#120c29" stroke="#cbb2ff" stroke-width="1.5"/>
  <path d="M31 37h2M43 37h2" stroke="#a9fff0" stroke-width="3" stroke-linecap="round"/>
  <path d="M33 42c3 4 7 4 10 0" stroke="#a9fff0" stroke-width="2" stroke-linecap="round"/>
  <path d="m61 8 1.8 5.2L68 15l-5.2 1.8L61 22l-1.8-5.2L54 15l5.2-1.8Z" fill="#ffd77d"/>
</svg>
</a></div>
<div class="user-live-region" aria-live="polite" aria-atomic="true" data-user-live-region></div>
</body></html>
