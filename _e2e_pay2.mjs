// Pays a Stripe sandbox Checkout Session with the official test card, then verifies LootLab's success page.
import fs from 'node:fs';

export default async function run(page) {
  const base = 'http://localhost/animeproject/LootLab/public';
  const out = [];
  const note = (s, o = {}) => { out.push({ s, ...o }); console.log('STEP', s, JSON.stringify(o).slice(0, 500)); };
  const save = (r) => fs.writeFileSync('_e2e_result.json', JSON.stringify({ log: out, ...r }, null, 2));

  // Login
  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', 'audit_browser@example.invalid');
  await page.fill('input[name="password"]', 'AuditPass123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1200);

  // Exactly two units: 8.96 JOD -> 12.63 USD
  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  const csrf = await page.getAttribute('input[name="csrf_token"]', 'value');
  await page.evaluate(async ({ b, csrf }) => {
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
  await page.waitForTimeout(7000);

  const stripeUrl = page.url();
  const main = page.mainFrame();
  const amountShown = ((await page.textContent('body')) || '').match(/\$?\s?(\d+\.\d{2})/)?.[1] ?? null;
  note('stripe-page', { amountShown, url: stripeUrl.slice(0, 60) });

  // Email for the receipt
  const emailInput = main.locator('input[name="email"], input[type="email"]').first();
  if (await emailInput.count()) { await emailInput.fill('audit_browser@example.invalid'); }

  // Select the "Card" radio (visible in the Payment method list)
  const cardRadio = main.getByRole('radio', { name: /^Card$/i }).first();
  note('card-radio', { count: await cardRadio.count() });
  if (await cardRadio.count()) {
    await cardRadio.check();
    await page.waitForTimeout(5000);
  }

  // Card fields (may be in the top document or an iframe)
  const fill = async (sels, val) => {
    for (const frame of [main, ...page.frames()]) {
      for (const s of sels) {
        const el = frame.locator(s).first();
        if ((await el.count()) > 0) {
          await el.fill(val, { timeout: 8000 }).catch(() => { });
          return `${frame.url().slice(0, 30)}|${s}`;
        }
      }
    }
    return null;
  };

  const used = {
    number: await fill(['input[name="cardnumber"]', 'input[autocomplete="cc-number"]', 'input[placeholder*="1234"]', 'input[aria-label*="Card number"]'], '4242 4242 4242 4242'),
    exp: await fill(['input[name="exp-date"]', 'input[autocomplete="cc-exp"]', 'input[placeholder*="MM"]', 'input[aria-label*="Expiration"]'], '12 / 34'),
    cvc: await fill(['input[name="cvc"]', 'input[autocomplete="cc-csc"]', 'input[placeholder*="CVC"]', 'input[aria-label*="CVC"]'], '123'),
    zip: await fill(['input[name="postal"]', 'input[autocomplete="postal-code"]', 'input[aria-label*="Postal"]'], '11118'),
  };
  note('card-filled', used);
  await page.screenshot({ path: '_stripe_filled.png' });
  save({ paid: false, stage: 'filled' });

  if (!used.number) {
    const inputs = [];
    for (const f of page.frames()) {
      try { inputs.push({ url: f.url().slice(0, 40), i: await f.evaluate(() => [...document.querySelectorAll('input')].map((e) => `${e.type}:${e.name || e.getAttribute('aria-label') || e.placeholder}`)) }); } catch { }
    }
    note('inputs-found', { inputs });
    return { paid: false, reason: 'no card field found', log: out };
  }

  // Pay
  await main.getByRole('button', { name: /^Pay\s*$/i }).first().click({ timeout: 15000 }).catch((e) => note('pay-click', { e: String(e).slice(0, 80) }));

  await page.waitForURL(/animeproject\/LootLab/, { timeout: 120000 }).catch(() => { });
  await page.waitForTimeout(4000);
  await page.screenshot({ path: '_stripe_result.png' });

  const finalText = ((await page.textContent('body')) || '').replace(/\s+/g, ' ').slice(0, 500);
  const result = {
    paid: true,
    finalUrl: page.url(),
    successPage: /Payment received|placed successfully/i.test(finalText),
    finalText,
    orderRef: finalText.match(/#(\d+)/)?.[1] ?? null,
    amountShown,
    log: out,
  };
  save(result);
  return result;
}
