import { chromium, devices } from 'playwright';
import fs from 'fs';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const OUT = 'tests/screenshots';
const widths = [375, 390];

let pass = 0;
let fail = 0;

function report(ok, label, detail = '') {
  if (ok) { pass++; console.log(`[PASS] ${label}`); }
  else { fail++; console.log(`[FAIL] ${label}${detail ? ` — ${detail}` : ''}`); }
}

if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });

for (const WIDTH of widths) {
  const context = await browser.newContext({
    viewport: { width: WIDTH, height: 812 },
    locale: 'ar-SA',
    ...devices['iPhone SE'],
    extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
  });
  const page = await context.newPage();

  try {
    await page.goto(`${BASE}/?v=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
    await page.waitForSelector('.ac-deal-strip', { timeout: 15000 });

    const deals = await page.$$('.ac-deal-strip');
    report(deals.length > 0, `${WIDTH}px has deal strips`, `count=${deals.length}`);

    const metrics = await page.evaluate(() => {
      const strips = [...document.querySelectorAll('.ac-deal-strip')];
      return strips.slice(0, 3).map((strip, i) => {
        const stripRect = strip.getBoundingClientRect();
        const price = strip.querySelector('.ac-price-val');
        const timer = strip.querySelector('.ac-timer-digits, .ac-timer-date, .ac-timer-ended-msg');
        const priceRect = price?.getBoundingClientRect();
        const timerRect = timer?.getBoundingClientRect();
        const stripStyle = getComputedStyle(strip);
        const overflows = strip.scrollWidth > strip.clientWidth + 1;
        const priceOverflow = price && price.scrollWidth > price.clientWidth + 1;
        const timerOverflow = timer && timer.scrollWidth > timer.clientWidth + 1;
        const overlap = priceRect && timerRect
          ? !(priceRect.right <= timerRect.left || timerRect.right <= priceRect.left)
          : false;
        return {
          i,
          stripW: Math.round(stripRect.width),
          stripH: Math.round(stripRect.height),
          flexDir: stripStyle.flexDirection,
          priceText: price?.textContent?.trim().slice(0, 30),
          priceW: priceRect ? Math.round(priceRect.width) : 0,
          timerW: timerRect ? Math.round(timerRect.width) : 0,
          overflows,
          priceOverflow,
          timerOverflow,
          overlap,
        };
      });
    });

    metrics.forEach((m) => {
      report(!m.overflows, `${WIDTH}px strip#${m.i} no horizontal overflow`, JSON.stringify(m));
      report(!m.overlap, `${WIDTH}px strip#${m.i} price/timer no overlap`, JSON.stringify(m));
      report(m.stripH >= 48, `${WIDTH}px strip#${m.i} readable height`, `h=${m.stripH}`);
    });

    const first = await page.$('.ac-deal-strip');
    if (first) {
      await first.scrollIntoViewIfNeeded();
      await page.screenshot({
        path: `${OUT}/card-deal-mobile-${WIDTH}.png`,
        clip: await first.evaluate((el) => {
          const r = el.getBoundingClientRect();
          const pad = 8;
          return {
            x: Math.max(0, r.x - pad),
            y: Math.max(0, r.y - pad),
            width: Math.min(window.innerWidth, r.width + pad * 2),
            height: r.height + pad * 2,
          };
        }),
      });
      report(true, `${WIDTH}px screenshot saved`);
    }
  } catch (e) {
    report(false, `${WIDTH}px page load`, e.message);
  }

  await context.close();
}

await browser.close();
console.log(`\nDone: ${pass} passed, ${fail} failed`);
process.exit(fail > 0 ? 1 : 0);