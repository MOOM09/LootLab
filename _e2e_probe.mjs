// Probe: what interactive elements exist on the Stripe Checkout page (frames included)?
import fs from 'node:fs';

export default async function run(page) {
  const base = 'http://localhost/animeproject/LootLab/public';
  const out = { steps: [], frames: [] };

  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', 'audit_browser@example.invalid');
  await page.fill('input[name="password"]', 'AuditPass123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1200);

  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  const csrf = await page.getAttribute('input[name="csrf_token"]', 'value');
  await page.evaluate(async (b) => {
    for (const id of [38, 39]) {
      await fetch(b + '/cart/add', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ product_id: id, quantity: 1 }) });
    }
  }, base);

  await page.goto(`${base}/checkout`, { waitUntil: 'domcontentloaded' });
  await page.fill('textarea[name="shipping_address"]', 'Test Street 1');
  await page.fill('input[name="phone"]', '0790000000');
  await page.check('input[name="payment_method"][value="stripe"]');
  await page.click('button:has-text("Pay with Stripe")');
  await page.waitForURL(/checkout\.stripe\.com/, { timeout: 45000 });
  await page.waitForTimeout(6000);

  // Readable text of the checkout page (top document).
  const bodyText = ((await page.textContent('body')) || '').replace(/\s+/g, ' ');
  out.bodyText = bodyText.slice(0, 500);

  // List every input/button across all frames.
  for (const frame of page.frames()) {
    try {
      const info = await frame.evaluate(() => {
        const els = [...document.querySelectorAll('input,button,select,[role="button"],[role="radio"]')];
        return {
          url: location.href.slice(0, 60),
          count: els.length,
          items: els.slice(0, 12).map((e) => ({
            tag: e.tagName.toLowerCase(),
            type: e.type || null,
            name: e.getAttribute('name'),
            placeholder: e.getAttribute('placeholder'),
            aria: e.getAttribute('aria-label'),
            text: (e.innerText || '').slice(0, 40),
          })),
        };
      });
      if (info.count > 0) out.frames.push(info);
    } catch (e) {
      // cross-origin frame without evaluate access
    }
  }

  // Is the amount visible anywhere in the rendered text?
  out.amountHits = [...new Set((bodyText.match(/\$\s?\d+\.\d{2}/g) || []))];
  out.jodMentions = (bodyText.match(/JOD/gi) || []).length;

  fs.writeFileSync('_e2e_probe.json', JSON.stringify(out, null, 2));
  return { frames: out.frames.map((f) => ({ url: f.url, count: f.count, items: f.items.map((i) => `${i.tag}/${i.type}/${i.name}/${i.placeholder}/${i.aria}/${i.text}`) })), amountHits: out.amountHits };
}
