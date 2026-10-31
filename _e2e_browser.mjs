// End-to-end Stripe sandbox test driven in a real headless browser.
// Uses Stripe's official test card 4242 4242 4242 4242. Test mode only.
import fs from 'node:fs';

export default async function run(page, ui) {
  const base = 'http://localhost/animeproject/LootLab/public';
  const email = 'audit_browser@example.invalid';
  const steps = [];
  const note = (s, extra = {}) => { steps.push({ step: s, ...extra }); console.log('STEP', s, JSON.stringify(extra)); };

  // 1. Login
  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', 'AuditPass123');
  await page.click('button[type="submit"], input[type="submit"]');
  await page.waitForTimeout(1500);
  note('after-login', { url: page.url(), body: (await page.textContent('body'))?.slice(0, 160) });

  // 2. Put two products in the cart through the real API (with the page's CSRF token).
  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  const csrf = await page.getAttribute('input[name="csrf_token"]', 'value');
  const addResult = await page.evaluate(async ({ base, csrf }) => {
    const out = [];
    for (const productId of [38, 39]) {
      const r = await fetch(base + '/cart/add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ product_id: productId, quantity: 1, csrf_token: csrf }),
      });
      out.push({ productId, status: r.status, body: (await r.text()).slice(0, 120) });
    }
    const cart = await fetch(base + '/cart');
    return { out, cart: (await cart.text()).slice(0, 400) };
  }, { base, csrf });
  note('cart-filled', addResult);

  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  const radios = await page.locator('input[name="payment_method"]').count();
  const hasStripeBtn = await page.locator('button:has-text("Pay with Stripe")').count();
  note('checkout-ui', { radios, hasStripeBtn });

  // 3. Fill the form, select Stripe, submit
  await page.fill('textarea[name="shipping_address"]', 'Test Street 1');
  await page.fill('input[name="phone"]', '0790000000');
  await page.check('input[name="payment_method"][value="stripe"]');
  await page.click('button:has-text("Pay with Stripe")');

  // 4. Wait for the Stripe hosted page
  await page.waitForURL(/checkout\.stripe\.com/, { timeout: 45000 });
  await page.waitForTimeout(4000);
  const title = await page.title();
  const stripeUrl = page.url();
  note('on-stripe', { url: stripeUrl, title });

  // Read what Stripe shows (amount/currency/methods) from the rendered page.
  const text = (await page.textContent('body')) || '';
  const amount = text.match(/\$?\s?([0-9]+\.[0-9]{2})/)?.[1] ?? null;
  const cardField = await page.locator('input[name="cardnumber"], input[placeholder*="1234"], input[autocomplete="cc-number"]').count();
  const methodHints = ['Card', 'Link', 'Klarna', 'Cash App', 'Bank'].filter((m) => new RegExp(m, 'i').test(text));
  note('stripe-content', { amount, cardField, methods: methodHints, textSample: text.replace(/\s+/g, ' ').slice(0, 240) });

  if (!cardField) {
    // Card fields are usually inside an iframe.
    const frames = page.frames().map((f) => f.url()).filter(Boolean);
    note('frames', { frames });
  }

  // 5. Pay with the official test card.
  // Stripe splits the card number into a frame; try the top document first, then frames.
  let paid = false;
  const cardSelectors = [
    'input[name="cardnumber"]',
    'input[autocomplete="cc-number"]',
    'input[placeholder="1234 1234 1234 1234"]',
    'input[placeholder*="1234"]',
  ];

  for (const frame of [page.mainFrame(), ...page.frames()]) {
    for (const sel of cardSelectors) {
      const loc = frame.locator(sel).first();
      if ((await loc.count()) > 0) {
        await loc.fill('4242 4242 4242 4242').catch(() => { });
        const exp = ['input[name="exp-date"]', 'input[autocomplete="cc-exp"]', 'input[placeholder="MM / YY"]', 'input[placeholder*="MM"]'];
        for (const s of exp) {
          const el = frame.locator(s).first();
          if ((await el.count()) > 0) { await el.fill('12 / 34').catch(() => { }); break; }
        }
        const cvc = ['input[name="cvc"]', 'input[autocomplete="cc-csc"]', 'input[placeholder="CVC"]', 'input[placeholder*="CVC"]'];
        for (const s of cvc) {
          const el = frame.locator(s).first();
          if ((await el.count()) > 0) { await el.fill('123').catch(() => { }); break; }
        }
        note('card-filled', { frame: frame.url().slice(0, 60), selector: sel });
        paid = true;
        break;
      }
    }
    if (paid) break;
  }

  if (!paid) {
    // Card is often a collapsed accordion: click it, then look again.
    const cardOption = page.getByText(/^Card$|Pay with card/i).first();
    if (await cardOption.count()) {
      await cardOption.click().catch(() => {});
      await page.waitForTimeout(3000);
    }
    for (const frame of [page.mainFrame(), ...page.frames()]) {
      for (const sel of cardSelectors) {
        const loc = frame.locator(sel).first();
        if ((await loc.count()) > 0) {
          await loc.fill('4242 4242 4242 4242').catch(() => {});
          paid = true;
          break;
        }
      }
      if (paid) break;
    }
  }

  if (!paid) {
    const bodyText = ((await page.textContent('body')) || '').replace(/\s+/g, ' ');
    const out = {
      steps,
      paid: false,
      stripeUrl: page.url(),
      stripeBody: bodyText.slice(0, 600),
      snapshot: (await ui.snapshot()).slice(0, 1200),
      frames: page.frames().map((f) => f.url().slice(0, 90)),
    };
    fs.writeFileSync('_e2e_result.json', JSON.stringify(out, null, 2));
    return out;
  }

  // Name/email if requested.
  for (const [sel, val] of [['input[name="name"]', 'Audit Tester'], ['input[name="email"]', 'audit_browser@example.invalid']]) {
    const el = page.locator(sel).first();
    if ((await el.count()) > 0) { await el.fill(val).catch(() => { }); }
  }

  // 6. Submit and wait to come back to LootLab.
  const payBtn = page.locator('button[type="submit"], button:has-text("Pay")').first();
  await payBtn.click({ timeout: 15000 }).catch(async () => { await page.keyboard.press('Enter'); });
  note('submitted', { url: page.url() });

  await page.waitForURL(/animeproject\/LootLab/, { timeout: 60000 }).catch(() => { });
  await page.waitForTimeout(2500);
  const finalUrl = page.url();
  const finalText = ((await page.textContent('body')) || '').replace(/\s+/g, ' ').slice(0, 400);

  const result = {
    steps,
    stripeUrl,
    stripeTitle: title,
    finalUrl,
    finalText,
    successPage: /Thank you|Payment received/i.test(finalText),
    orderRef: finalText.match(/#(\d+)/)?.[1] ?? null,
  };
  fs.writeFileSync('_e2e_result.json', JSON.stringify(result, null, 2));
  return result;
}
