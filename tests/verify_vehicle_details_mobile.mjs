/**
 * Vehicle detail page mobile polish @ 375px — Build 108
 * node tests/verify_vehicle_details_mobile.mjs
 */
import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const VEHICLE_ID = process.env.FLEETX_VEHICLE_ID || '8';
const OUT = 'tests/screenshots';
let pass = 0;
let fail = 0;

function report(ok, label, detail = '') {
  console.log(`${ok ? '[PASS]' : '[FAIL]'} ${label}${detail ? ` — ${detail}` : ''}`);
  ok ? pass++ : fail++;
}

async function gotoWithRetry(page, url, attempts = 3) {
  let lastErr;
  for (let i = 1; i <= attempts; i++) {
    try {
      await page.goto(url, { waitUntil: 'load', timeout: 45000 });
      await page.waitForSelector('.fx-page-shell--vehicle', { timeout: 15000 });
      return;
    } catch (err) {
      lastErr = err;
      if (i < attempts) await page.waitForTimeout(800 * i);
    }
  }
  throw lastErr;
}

const fs = await import('fs');
if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({
  viewport: { width: 375, height: 812 },
  locale: 'ar-SA',
  ...devices['iPhone 13'],
  extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
});
const page = await ctx.newPage();

try {
  await gotoWithRetry(page, `${BASE}/vehicle-details.php?id=${VEHICLE_ID}&t=${Date.now()}`);

  const data = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
    shell: !!document.querySelector('.fx-page-shell--vehicle'),
    gallery: !!document.querySelector('.fx-vehicle-gallery-card'),
    pricing: !!document.getElementById('fxVehiclePricingPanel'),
    buyBar: !!document.getElementById('fxVehicleBuyBar'),
    thumbs: document.querySelectorAll('.mpg-thumb').length,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    galleryTop: Math.round(document.querySelector('.fx-vehicle-gallery-card')?.getBoundingClientRect().top ?? 0),
    pricingTop: Math.round(document.getElementById('fxVehiclePricingPanel')?.getBoundingClientRect().top ?? 0),
    heroMetaScroll: (() => {
      const meta = document.querySelector('.fx-page-hero__meta');
      if (!meta) return false;
      const s = getComputedStyle(meta);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    thumbStripScroll: (() => {
      const strip = document.querySelector('.mpg-thumbs-strip');
      if (!strip) return false;
      const s = getComputedStyle(strip);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    buyBarVisible: (() => {
      const bar = document.getElementById('fxVehicleBuyBar');
      if (!bar) return false;
      const s = getComputedStyle(bar);
      return s.display !== 'none' && bar.getBoundingClientRect().height > 0;
    })(),
    specsCols: getComputedStyle(document.querySelector('.fx-detail-specs') || document.body).gridTemplateColumns,
  }));

  report(data.build === '108', 'Build 108', data.build);
  report(data.shell, 'Vehicle page shell');
  report(data.gallery && data.pricing, 'Gallery + pricing panels');
  report(data.buyBar, 'Mobile buy bar present');
  report(data.buyBarVisible, 'Mobile buy bar visible');
  report(data.thumbs >= 4, 'Gallery thumbnails', String(data.thumbs));
  report(!data.overflow, 'No horizontal overflow');
  report(data.galleryTop < data.pricingTop, 'Gallery before pricing', `g=${data.galleryTop} p=${data.pricingTop}`);
  report(data.heroMetaScroll, 'Hero meta chips scrollable');
  report(data.thumbStripScroll, 'Thumb strip scrollable');
  const specsSingleCol = (data.specsCols || '').split(' ').length <= 1 || (data.specsCols || '').includes('1fr');
  report(specsSingleCol, 'Specs single column', data.specsCols);

  const buyLink = await page.locator('#fxVehicleBuyBar .fx-mobile-bid-btn').getAttribute('href');
  report(!!buyLink && buyLink.includes('checkout.php'), 'Buy bar links to checkout', buyLink || '');

  await page.screenshot({ path: `${OUT}/build108-vehicle-details-375.png` });
  console.log(`[SHOT] ${OUT}/build108-vehicle-details-375.png`);
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Vehicle Detail Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);