// ONE end-to-end Stripe sandbox payment with the official test card.
import fs from 'node:fs';

export default async function run(page) {
  const base = 'http://localhost/animeproject/LootLab/public';
  const log = [];
  const note = (s, o = {}) => { log.push({ s, ...o }); console.log('STEP', s, JSON.stringify(o).slice(0, 600)); };
  const save = (r) => fs.writeFileSync('_final_result.json', JSON.stringify({ log, ...r }, null, 2));

  // 1-3. Login -> checkout -> select Stripe -> submit
  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', 'audit_browser@example.invalid');
  await page.fill('input[name="password"]', 'AuditPass123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1200);

  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  await page.fill('textarea[name="shipping_address"]', 'Test Street 1');
  await page.fill('input[name="phone"]', '0790000000');
  await page.check('input[name="payment_method"][value="stripe"]');
  await page.click('button:has-text("Pay with Stripe")');

  // 4. Stripe Checkout must open
  await page.waitForURL(/checkout\.stripe\.com/, { timeout: 45000 });
  await page.waitForTimeout(7000);
  const stripeUrl = page.url();
  const sessionId = stripeUrl.match(/cs_test_[A-Za-z0-9]+/)?.[0] ?? null;
  note('stripe-opened', { sessionId, url: stripeUrl.slice(0, 60) });
  save({ stage: 'on-stripe', sessionId });

  const main = page.mainFrame();

  // Receipt email
  const email = main.locator('input[name="email"], input[type="email"]').first();
  if (await email.count()) await email.fill('audit_browser@example.invalid').catch(() => { });

  // 5. Select "Card" — click the visible card option row.
  const cardRow = main.locator('text=/\\bCard\\b/').first();
  await cardRow.click({ timeout: 10000 }).catch((e) => note('card-click-warn', { e: String(e).slice(0, 80) }));
  await page.waitForTimeout(5000);
  await page.screenshot({ path: '_final_card.png' });

  // Fill the card inputs wherever they are (top document or Stripe iframes).
  async function fillAny(names, value) {
    for (const frame of page.frames()) {
      for (const name of names) {
        const el = frame.locator(`input[name="${name}"]`).first();
        try {
          if ((await el.count()) > 0) {
            await el.fill(value, { timeout: 8000 });
            return frame.url().slice(0, 40) + ' | ' + name;
          }
        } catch { /* keep looking */ }
      }
    }
    return null;
  }

  const filled = {
    number: await fillAny(['cardnumber', 'number'], '4242 4242 4242 4242'),
    expiry: await fillAny(['exp-date', 'expiry', 'exp'], '12 / 34'),
    cvc: await fillAny(['cvc'], '123'),
    zip: await fillAny(['postal', 'postalCode', 'zip'], '11118'),
  };
  note('card-filled', filled);
  save({ stage: 'filled', sessionId, filled });

  if (!filled.number) {
    const inputs = [];
    for (const f of page.frames()) {
      try { inputs.push({ u: f.url().slice(0, 40), n: await f.evaluate(() => [...document.querySelectorAll('input')].map((e) => e.name || e.getAttribute('aria-label') || e.type)) }); } catch { }
    }
    note('inputs', inputs);
    return { ok: false, failedAt: 'selecting Card / filling card fields', log };
  }

  // 7. Pay
  const pay = main.locator('button[type="submit"]').filter({ hasText: /^\s*Pay\b/i }).first();
  note('pay-button', { count: await pay.count() });
  await pay.click({ timeout: 15000 }).catch((e) => note('pay-click', { e: String(e).slice(0, 80) }));

  // 8. Wait for return to LootLab.
  await page.waitForURL(/animeproject\/LootLab/, { timeout: 120000 }).catch(() => { });
  await page.waitForTimeout(4000);
  await page.screenshot({ path: '_final_result.png' });

  const finalUrl = page.url();
  const text = ((await page.textContent('body')) || '').replace(/\s+/g, ' ').slice(0, 500);
  const result = {
    ok: true,
    sessionId,
    finalUrl,
    successPage: /Payment received|placed successfully/i.test(text),
    verificationError: /could not verify/i.test(text),
    cartCleared: /items"?\s*:\s*\[\s*\]/.test(await page.evaluate(() => fetch('/animeproject/LootLab/public/cart').then((r) => r.text()))),
    text,
    log,
  };
  save(result);
  return result;
}
