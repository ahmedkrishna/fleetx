/**
 * Seller dashboard + auction live mobile polish verify
 * node tests/verify_seller_live_mobile.mjs
 */
import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const OUT = 'tests/screenshots';
let pass = 0;
let fail = 0;

function report(ok, label, detail = '') {
  console.log(`${ok ? '[PASS]' : '[FAIL]'} ${label}${detail ? ` — ${detail}` : ''}`);
  ok ? pass++ : fail++;
}

async function loginAs(page, type, mobile, password) {
  await page.goto(`${BASE}/login.php?type=${type}&t=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
  await page.waitForFunction(() => document.getElementById('loginForm')?.classList.contains('visible'), { timeout: 15000 });
  await page.fill('#mobile', mobile);
  await page.fill('#password', password);
  await page.click('button[type="submit"]');
  await page.waitForURL((u) => !u.pathname.includes('login.php'), { timeout: 60000 });
}

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({
  viewport: { width: 375, height: 812 },
  locale: 'ar-SA',
  ...devices['iPhone 13'],
});
const page = await ctx.newPage();

try {
  await loginAs(page, 'company', '0500000002', '123456');
  await page.goto(`${BASE}/seller.php?section=dashboard&t=${Date.now()}`, { waitUntil: 'load' });

  const seller = await page.evaluate(() => ({
    profile: !!document.querySelector('.fx-dash-mobile-profile--seller'),
    nav: !!document.querySelector('.fx-dash-mobile-nav select'),
    stats2: getComputedStyle(document.querySelector('.fx-seller-stats') || document.body).gridTemplateColumns,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    actions: document.querySelectorAll('.fx-seller-actions-top .btn-action-top').length,
  }));
  report(seller.profile, 'Seller mobile profile strip');
  report(seller.nav, 'Seller mobile nav select');
  report(seller.actions >= 3, 'Seller quick actions present', String(seller.actions));
  report(!seller.overflow, 'Seller dashboard no horizontal overflow');
  await page.screenshot({ path: `${OUT}/build103-seller-mobile-375.png` });
  console.log(`[SHOT] ${OUT}/build103-seller-mobile-375.png`);

  await page.goto(`${BASE}/auction-live.php?id=1&t=${Date.now()}`, { waitUntil: 'load' });
  const live = await page.evaluate(() => {
    const room = document.querySelector('.fx-live-room');
    const gallery = document.querySelector('.live-gallery-panel');
    const bid = document.getElementById('fx-bid-panel');
    const bar = document.querySelector('.fx-mobile-bid-bar');
    const barStyle = bar ? getComputedStyle(bar) : null;
    const galleryRect = gallery?.getBoundingClientRect();
    const bidRect = bid?.getBoundingClientRect();
    return {
      barVisible: barStyle?.display !== 'none',
      barHeight: bar ? Math.round(bar.getBoundingClientRect().height) : 0,
      galleryBeforeBid: galleryRect && bidRect ? galleryRect.top < bidRect.top : false,
      bidAnchor: !!bid,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
      hasCta: !!document.querySelector('.fx-mobile-bid-btn, a.fx-mobile-bid-btn'),
    };
  });
  report(live.bidAnchor, 'Auction live bid panel anchor');
  report(live.barVisible && live.barHeight > 40, 'Mobile bid bar visible', `h=${live.barHeight}`);
  report(live.galleryBeforeBid, 'Gallery appears above bid panel on mobile');
  report(!live.overflow, 'Auction live no horizontal overflow');
  report(live.hasCta, 'Mobile bid bar has action CTA');
  await page.screenshot({ path: `${OUT}/build103-auction-live-mobile-375.png`, fullPage: false });
  console.log(`[SHOT] ${OUT}/build103-auction-live-mobile-375.png`);
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Seller + Auction Live Mobile: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);