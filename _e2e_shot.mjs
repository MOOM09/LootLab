// Opens Stripe Checkout, clicks "Pay with card", and dumps the resulting controls.
import fs from 'node:fs';

export default async function run(page) {
  const base = 'http://localhost/animeproject/LootLab/public';
  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', 'audit_browser@example.invalid');
  await page.fill('input[name="password"]', 'AuditPass123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1200);

  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  const csrf = await page.getAttribute('input[name="csrf_token"]', 'value');
  await page.evaluate(async ({ b, csrf }) => {
    // Reset the cart first so this run pays for exactly two units.
    await fetch(b + '/cart/clear', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ csrf_token: csrf }) });
    for (const id of [38, 39]) {
      await fetch(b + '/cart/add', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ product_id: id, quantity: 1, csrf_token: csrf }) });
    }
  }, { b: base, csrf });

  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  await page.fill('textarea[name="shipping_address"]', 'Test Street 1');
  await page.fill('input[name="phone"]', '0790000000');
  await page.check('input[name="payment_method"][value="stripe"]');
  await page.click('button:has-text("Pay with Stripe")');
  await page.waitForURL(/checkout\.stripe\.com/, { timeout: 45000 });
  await page.waitForTimeout(6000);

  await page.screenshot({ path: '_stripe_before.png', fullPage: false });

  const btn = page.mainFrame().getByRole('button', { name: /Pay with card/i }).first();
  if (await btn.count()) {
    await btn.click();
    await page.waitForTimeout(6000);
  }
  await page.screenshot({ path: '_stripe_after.png', fullPage: false });

  const info = { frames: [] };
  for (const f of page.frames()) {
    try {
      const data = await f.evaluate(() => ({
        url: location.href.slice(0, 60),
        inputs: [...document.querySelectorAll('input')].map((e) => `${e.type}:${e.name || e.getAttribute('aria-label') || e.placeholder}`),
        buttons: [...document.querySelectorAll('button')].map((b) => (b.innerText || '').trim().slice(0, 30)).filter(Boolean),
        text: (document.body.innerText || '').replace(/\s+/g, ' ').slice(0, 200),
      }));
      info.frames.push(data);
    } catch { /* cross-origin */ }
  }
  fs.writeFileSync('_e2e_frames.json', JSON.stringify(info, null, 2));
  return info.frames.map((f) => ({ url: f.url, inputs: f.inputs, buttons: f.buttons }));
}
