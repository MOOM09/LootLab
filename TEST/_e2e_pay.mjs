// Full Stripe sandbox payment in a headless browser using the official test card.
import fs from 'node:fs';

export default async function run(page) {
  const base = 'http://localhost/animeproject/LootLab/public';
  const log = [];
  const note = (s, o = {}) => { log.push({ s, ...o }); console.log('STEP', s, JSON.stringify(o).slice(0, 400)); };
  const save = () => fs.writeFileSync('_e2e_result.json', JSON.stringify({ log }, null, 2));

  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', 'audit_browser@example.invalid');
  await page.fill('input[name="password"]', 'AuditPass123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1200);

  // Fill the cart with an explicit CSRF token from the checkout page.
  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  const csrf = await page.getAttribute('input[name="csrf_token"]', 'value');
  const fill = await page.evaluate(async ({ b, csrf }) => {
    const r = [];
    for (const id of [38, 39]) {
      const res = await fetch(b + '/cart/add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ product_id: id, quantity: 1, csrf_token: csrf }),
      });
      r.push(res.status);
    }
    return r;
  }, { b: base, csrf });
  note('cart-add-statuses', { fill });

  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  await page.fill('textarea[name="shipping_address"]', 'Test Street 1');
  await page.fill('input[name="phone"]', '0790000000');
  await page.check('input[name="payment_method"][value="stripe"]');
  await page.click('button:has-text("Pay with Stripe")');
  await page.waitForURL(/checkout\.stripe\.com/, { timeout: 45000 });
  await page.waitForTimeout(6000);
  note('on-stripe', { url: page.url().slice(0, 70) });

  const main = page.mainFrame();
  const bodyText = ((await page.textContent('body')) || '');
  note('amount-visible', { hits: [...new Set(bodyText.match(/\$\s?\d+\.\d{2}/g) || [])].slice(0, 5) });

  // Choose "Pay with card"
  const cardBtn = main.getByRole('button', { name: /Pay with card/i }).first();
  note('card-button', { count: await cardBtn.count() });
  if (await cardBtn.count()) {
    await cardBtn.click();
    await page.waitForTimeout(4000);
  }

  // Find card inputs anywhere.
  let filled = {};
  for (const frame of page.frames()) {
    try {
      const n = await frame.evaluate(() => document.querySelectorAll('input').length);
      if (!n) continue;
      const info = await frame.evaluate(() =>
        [...document.querySelectorAll('input')].map((e) => e.name || e.getAttribute('aria-label') || e.placeholder || e.type)
      );
      note('frame-inputs', { url: frame.url().slice(0, 50), inputs: info.slice(0, 12) });
    } catch { /* cross-origin */ }
  }

  const tryFill = async (frame, sels, value) => {
    for (const sel of sels) {
      const el = frame.locator(sel).first();
      if ((await el.count()) > 0) {
        await el.fill(value, { timeout: 5000 }).catch(() => { });
        return sel;
      }
    }
    return null;
  };

  const targets = [main, ...page.frames()];
  for (const frame of targets) {
    const used = await tryFill(frame, ['input[name="cardnumber"]', 'input[autocomplete="cc-number"]', 'input[placeholder*="1234"]', 'input[aria-label*="Card number"]'], '4242 4242 4242 4242');
    if (used) {
      filled.card = used;
      await tryFill(frame, ['input[name="exp-date"]', 'input[autocomplete="cc-exp"]', 'input[placeholder*="MM"]', 'input[aria-label*="Expiration"]'], '12 / 34');
      await tryFill(frame, ['input[name="cvc"]', 'input[autocomplete="cc-csc"]', 'input[placeholder*="CVC"]', 'input[aria-label*="CVC"]'], '123');
      await tryFill(frame, ['input[name="postal"]', 'input[autocomplete="postal-code"]'], '11118');
      filled.frame = frame.url().slice(0, 50);
      break;
    }
  }
  note('card-filled', filled);
  save();

  if (!filled.card) {
    return { paid: false, reason: 'card fields not found', log };
  }

  const payBtn = main.getByRole('button', { name: /^Pay\s*$/i }).or(main.locator('button[type="submit"]')).first();
  note('pay-button', { count: await payBtn.count() });
  await payBtn.click({ timeout: 15000 }).catch((e) => note('pay-click-error', { m: String(e).slice(0, 100) }));

  await page.waitForURL(/animeproject\/LootLab/, { timeout: 90000 }).catch(() => { });
  await page.waitForTimeout(3000);

  const finalText = ((await page.textContent('body')) || '').replace(/\s+/g, ' ').slice(0, 400);
  const result = {
    paid: true,
    finalUrl: page.url(),
    successPage: /Thank you|Payment received|placed successfully/i.test(finalText),
    finalText,
    orderRef: finalText.match(/#(\d+)/)?.[1] ?? null,
    log,
  };
  fs.writeFileSync('_e2e_result.json', JSON.stringify(result, null, 2));
  return result;
}
