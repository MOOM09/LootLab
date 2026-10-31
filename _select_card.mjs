// Select the Card payment method on Stripe Checkout, then probe for card fields.
import fs from 'node:fs';

export default async function run(page) {
  const url = fs.readFileSync(new URL('./_reuse_url.txt', import.meta.url), 'utf8').trim();
  const out = {};

  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(9000);

  const em = page.locator('input[name="email"], input#email').first();
  if (await em.count()) await em.fill('audit_browser@example.invalid').catch(() => { });

  // Enumerate visible radio inputs in the main document.
  out.radiosBefore = await page.evaluate(() =>
    [...document.querySelectorAll('input[type="radio"]')].map((r) => ({
      value: r.value, checked: r.checked,
      label: (r.closest('label')?.innerText || document.querySelector(`label[for="${r.id}"]`)?.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 40),
    }))
  );

  // Choose the "Card" radio by its associated label text.
  const cardInput = page.locator('input[type="radio"]').filter({ has: page.locator('xpath=.') });
  const radios = page.locator('input[type="radio"]');
  const n = await radios.count();
  let clicked = false;
  for (let i = 0; i < n; i++) {
    const r = radios.nth(i);
    const label = ((await r.evaluate((el) => el.closest('label')?.innerText || document.querySelector(`label[for="${el.id}"]`)?.innerText || '')) || '').trim();
    if (/^card/i.test(label) || /card/i.test(await r.getAttribute('value') || '')) {
      await r.check({ force: true }).catch(async () => { await r.click({ force: true }).catch(() => { }); });
      out.clickedLabel = label.slice(0, 40);
      clicked = true;
      break;
    }
  }
  out.clicked = clicked;

  await page.waitForTimeout(8000);

  // Re-probe frames for card fields.
  out.frames = page.frames().map((f) => f.url().slice(0, 90));
  out.hits = [];
  for (const f of page.frames()) {
    try {
      const inputs = await f.evaluate(() =>
        [...document.querySelectorAll('input')].map((e) => ({
          name: e.name, type: e.type, ph: e.placeholder || null,
          aria: e.getAttribute('aria-label'), disabled: e.disabled,
        }))
      );
      if (inputs.length) out.hits.push({ frame: f.url().slice(0, 70), inputs });
    } catch { /* opaque */ }
  }

  fs.writeFileSync('_frames2.json', JSON.stringify(out, null, 2));
  await page.screenshot({ path: '_card_selected.png' });
  return out;
}
