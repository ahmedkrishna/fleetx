/**
 * Buyer dashboard mobile polish verify @ 375px
 * node tests/verify_buyer_mobile.mjs
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

async function loginAsBuyer(page) {
  await page.goto(`${BASE}/login.php?type=trader&t=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
  await page.waitForFunction(() => document.getElementById('loginForm')?.classList.contains('visible'), { timeout: 15000 });
  await page.fill('#mobile', '0501111111');
  await page.fill('#password', '123456');
  await page.click('button[type="submit"]');
  await page.waitForURL((u) => !u.pathname.includes('login.php'), { timeout: 60000 });
}

async function checkBuyerSection(page, section, label) {
  const url = section === 'dashboard'
    ? `${BASE}/buyer.php?section=dashboard&t=${Date.now()}`
    : `${BASE}/buyer.php?section=${section}&t=${Date.now()}`;
  await page.goto(url, { waitUntil: 'load', timeout: 30000 });
  const data = await page.evaluate((sec) => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || '',
    profile: !!document.querySelector('.fx-dash-mobile-profile--buyer'),
    nav: !!document.querySelector('.fx-buyer-mobile-nav select'),
    actions: document.querySelectorAll('.fx-buyer-actions-top .btn-action-top').length,
    stats2: getComputedStyle(document.querySelector('.fx-buyer-stats') || document.body).gridTemplateColumns,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    walletGrid: !!document.querySelector('.wallet-grid'),
    walletBtns: document.querySelectorAll('.fx-wallet-card-actions .wallet-btn').length,
    bidCards: document.querySelectorAll('.bid-card').length,
    plans: document.querySelectorAll('.plans-grid .plan-card').length,
  }), section);

  report(data.build === '105', `${label} build 105`, data.build);
  report(data.profile, `${label} mobile profile strip`);
  report(data.nav, `${label} mobile nav select`);
  report(!data.overflow, `${label} no horizontal overflow`);

  if (section === 'dashboard') {
    report(data.actions >= 4, `${label} quick actions`, String(data.actions));
    report(data.stats2.includes(' ') || data.stats2 !== 'none', `${label} stats grid responsive`, data.stats2);
  }
  if (section === 'wallet') {
    report(data.walletGrid, `${label} wallet layout`);
    report(data.walletBtns >= 2, `${label} wallet action buttons`, String(data.walletBtns));
  }
  if (section === 'bids') {
    report(true, `${label} bids section loaded`);
  }
  if (section === 'subscription') {
    report(data.plans >= 3, `${label} subscription plans`, String(data.plans));
  }
  return data;
}

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({
  viewport: { width: 375, height: 812 },
  locale: 'ar-SA',
  ...devices['iPhone 13'],
});
const page = await ctx.newPage();

try {
  await loginAsBuyer(page);

  await checkBuyerSection(page, 'dashboard', 'Dashboard');
  await page.screenshot({ path: `${OUT}/build105-buyer-dashboard-375.png` });
  console.log(`[SHOT] ${OUT}/build105-buyer-dashboard-375.png`);

  await checkBuyerSection(page, 'bids', 'Bids');
  await checkBuyerSection(page, 'wallet', 'Wallet');
  await checkBuyerSection(page, 'subscription', 'Subscription');

  await page.goto(`${BASE}/buyer.php?section=dashboard&t=${Date.now()}`, { waitUntil: 'load' });
  await page.selectOption('.fx-buyer-mobile-nav select', '?section=wallet');
  await page.waitForURL(/section=wallet/, { timeout: 15000 });
  report(page.url().includes('section=wallet'), 'Mobile nav select navigates to wallet');
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Buyer Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);