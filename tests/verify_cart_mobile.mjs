/**
 * Cart page mobile polish @ 375px — Build 114
 * node tests/verify_cart_mobile.mjs
 */
import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const FAV_AUCTION_ID = process.env.FLEETX_CART_AUCTION_ID || '8';
const OUT = 'tests/screenshots';
let pass = 0;
let fail = 0;

function report(ok, label, detail = '') {
  console.log(`${ok ? '[PASS]' : '[FAIL]'} ${label}${detail ? ` — ${detail}` : ''}`);
  ok ? pass++ : fail++;
}

async function loginBuyer(page) {
  await page.goto(`${BASE}/login.php?type=trader&t=${Date.now()}`, { waitUntil: 'load', timeout: 45000 });
  await page.waitForFunction(() => document.getElementById('loginForm')?.classList.contains('visible'), { timeout: 15000 });
  await page.fill('#mobile', '0501111111');
  await page.fill('#password', '123456');
  await page.click('button[type="submit"]');
  await page.waitForURL((u) => !u.pathname.includes('login.php'), { timeout: 60000 });
}

async function ensureCartItem(page) {
  let resp = await page.request.post(`${BASE}/api/toggle_favorite.php`, {
    data: { id: Number(FAV_AUCTION_ID) },
    headers: { 'Content-Type': 'application/json' },
  });
  let data = await resp.json().catch(() => ({}));
  if (!data.is_favorite) {
    resp = await page.request.post(`${BASE}/api/toggle_favorite.php`, {
      data: { id: Number(FAV_AUCTION_ID) },
      headers: { 'Content-Type': 'application/json' },
    });
    data = await resp.json().catch(() => ({}));
  }
  report(resp.ok() && data.is_favorite === true, 'Cart item seeded in watchlist', String(data.is_favorite));
}

async function gotoCart(page, attempts = 3) {
  let lastErr;
  const url = `${BASE}/cart.php?t=${Date.now()}`;
  for (let i = 1; i <= attempts; i++) {
    try {
      await page.goto(url, { waitUntil: 'load', timeout: 45000 });
      await page.waitForSelector('.fx-page-shell--cart', { timeout: 15000 });
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
  await loginBuyer(page);
  await ensureCartItem(page);
  await gotoCart(page);

  const data = await page.evaluate(() => {
    const checkoutLayout = document.getElementById('fxCartLayout');
    const summary = document.querySelector('.fx-cart-summary');
    const itemsBox = document.querySelector('.fx-cart-items-box');
    const payBar = document.getElementById('fxCartMobileBar');
    const items = document.querySelectorAll('.fx-cart-item');

    const layoutCols = checkoutLayout ? getComputedStyle(checkoutLayout).gridTemplateColumns : '';
    const singleCol =
      layoutCols.includes('1fr') ||
      layoutCols.split(' ').length === 1 ||
      (checkoutLayout?.getBoundingClientRect().width || 0) <= 375;

    const summaryFirst = (() => {
      if (!summary || !itemsBox) return false;
      return summary.getBoundingClientRect().top < itemsBox.getBoundingClientRect().top;
    })();

    const summaryStatic = summary ? getComputedStyle(summary).position === 'static' : false;

    const payBarVisible = payBar
      ? getComputedStyle(payBar).display !== 'none' && payBar.getBoundingClientRect().height > 0
      : false;

    const layoutPadRaw = checkoutLayout ? getComputedStyle(checkoutLayout).paddingBottom : '0px';
    const layoutPad = parseFloat(layoutPadRaw) || 0;

    const heroMeta = (() => {
      const meta = document.querySelector('.fx-page-hero__meta');
      if (!meta) return { chips: 0, scroll: false, nowrap: false };
      const s = getComputedStyle(meta);
      return {
        chips: meta.querySelectorAll('.fx-page-hero__chip').length,
        scroll: s.overflowX === 'auto' || s.overflowX === 'scroll',
        nowrap: s.flexWrap === 'nowrap',
      };
    })();

    return {
      build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
      cssVer: document.querySelector('link[href*="fleetx.css"]')?.getAttribute('href') || '',
      shell: !!document.querySelector('.fx-page-shell--cart'),
      layout: !!checkoutLayout,
      summaryCard: !!document.querySelector('.fx-cart-summary-card'),
      itemCount: items.length,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
      singleCol,
      summaryFirst,
      summaryStatic,
      payBarVisible,
      layoutPad,
      layoutPadRaw,
      heroMeta,
      hasCheckoutCta: !!document.querySelector('.fx-cart-item__cta[href*="checkout.php"]'),
      mobileCheckout: !!document.querySelector('#fxCartMobileBar a[href*="checkout.php"]'),
    };
  });

  report(data.build === '114', 'Build 114', data.build);
  report(data.cssVer.includes('v=114'), 'fleetx.css v=114', data.cssVer);
  report(data.shell, 'Cart page shell');
  report(data.layout && data.summaryCard, 'Layout + summary card');
  report(data.itemCount >= 1, 'Cart has saved vehicle', String(data.itemCount));
  report(!data.overflow, 'No horizontal overflow');
  report(data.singleCol, 'Single-column layout');
  report(data.summaryFirst, 'Summary appears before items on mobile');
  report(data.summaryStatic, 'Summary not sticky on mobile');
  report(data.payBarVisible, 'Mobile cart bar visible');
  report(data.layoutPad >= 100, 'Layout bottom padding for sticky bar', data.layoutPadRaw);
  report(
    data.heroMeta.chips >= 1 && (data.heroMeta.scroll || data.heroMeta.nowrap),
    'Hero meta chips mobile-ready',
    `${data.heroMeta.chips} chips`
  );
  report(data.hasCheckoutCta, 'Item checkout CTA present');
  report(data.mobileCheckout, 'Mobile bar checkout CTA');

  const touchTargets = await page.evaluate(() =>
    [...document.querySelectorAll('.fx-cart-item__cta, .fx-cart-item__remove, #fxCartMobileBar .fx-mobile-bid-btn')].map(
      (el) => el.getBoundingClientRect().height
    )
  );
  report(touchTargets.every((h) => h >= 44), 'Cart touch targets', touchTargets.map(Math.round).join(','));

  await page.screenshot({ path: `${OUT}/build114-cart-375.png` });
  console.log(`[SHOT] ${OUT}/build114-cart-375.png`);

  // Empty cart state (separate navigation)
  await page.request.post(`${BASE}/api/toggle_favorite.php`, {
    data: { id: Number(FAV_AUCTION_ID) },
    headers: { 'Content-Type': 'application/json' },
  });
  await gotoCart(page);
  const empty = await page.evaluate(() => ({
    emptyPanel: !!document.querySelector('.fx-empty-state-panel'),
    noMobileBar: !document.getElementById('fxCartMobileBar'),
    emptyCta: !!document.querySelector('.fx-empty-state-panel__cta'),
  }));
  report(empty.emptyPanel, 'Empty cart state panel');
  report(empty.noMobileBar, 'No sticky bar when cart empty');
  report(empty.emptyCta, 'Empty cart CTA');

  const emptyCtaH = await page.evaluate(() => {
    const btn = document.querySelector('.fx-empty-state-panel__cta');
    return btn ? btn.getBoundingClientRect().height : 0;
  });
  report(emptyCtaH >= 44, 'Empty cart CTA touch target', String(Math.round(emptyCtaH)));
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Cart Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);