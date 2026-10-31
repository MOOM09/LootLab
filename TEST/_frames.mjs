// Inspect Stripe Checkout's frame structure and locate the card fields.
import fs from 'node:fs';

export default async function run(page) {
  const url = fs.readFileSync(new URL('./_reuse_url.txt', import.meta.url), 'utf8').trim();
  const out = { url: url.slice(0, 60) };

  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(9000);

  // Email for the receipt (top document).
  const em = page.locator('input[name="email"], input#email').first();
  if (await em.count()) await em.fill('audit_browser@example.invalid').catch(() => { });

  // Recursively describe the frame tree.
  const describe = async (frame, depth = 0) => {
    const rows = [];
    try {
      const info = await frame.evaluate(() => {
        const els = [...document.querySelectorAll('input,button,iframe')];
        return { url: location.href.slice(0, 70), els: els.map((e) => `${e.tagName.toLowerCase()}|${e.getAttribute('name') || e.getAttribute('aria-label') || e.getAttribute('placeholder') || e.type || ''}`) };
      });
      rows.push({ depth, url: info.url, els: info.els });
    } catch (e) {
      rows.push({ depth, url: frame.url().slice(0, 70), error: 'cross-origin/opaque' });
    }
    for (const child of frame.childFrames()) {
      rows.push(...(await describe(child, depth + 1)));
    }
    return rows;
  };

  out.mainFrameInputs = (await describe(page.mainFrame()));
  out.flatFrames = page.frames().map((f) => f.url().slice(0, 80));
  out.childFramesMain = page.mainFrame().childFrames().map((f) => f.url().slice(0, 80));

  // Try to find the card number input via locator APIs across all frames.
  out.probes = [];
  for (const f of page.frames()) {
    for (const sel of ['[name="cardnumber"]', '[name="number"]', '[autocomplete="cc-number"]', 'input[name="exp-date"]', 'input[name="cvc"]']) {
      try {
        const n = await f.locator(sel).count();
        if (n) out.probes.push({ frame: f.url().slice(0, 60), sel, count: n, visible: await f.locator(sel).first().isVisible().catch(() => false) });
      } catch { /* ignore */ }
    }
  }

  fs.writeFileSync('_frames.json', JSON.stringify(out, null, 2));
  await page.screenshot({ path: '_frames.png' });
  return out;
}
