/**
 * Checkout page mobile polish @ 375px — Build 113
 * node tests/verify_checkout_mobile.mjs
 */
import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const AUCTION_ID = process.env.FLEETX_CHECKOUT_ID || '8';
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

async function gotoCheckout(page, attempts = 3) {
  let lastErr;
  const url = `${BASE}/checkout.php?id=${AUCTION_ID}&t=${Date.now()}`;
  for (let i = 1; i <= attempts; i++) {
    try {
      await page.goto(url, { waitUntil: 'load', timeout: 45000 });
      await page.waitForSelector('.fx-page-shell--checkout .fx-checkout-layout', { timeout: 15000 });
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
  await gotoCheckout(page);

  const data = await page.evaluate(() => {
    const checkoutLayout = document.querySelector('.fx-checkout-layout');
    const summary = document.querySelector('.fx-checkout-summary');
    const box = document.querySelector('.fx-checkout-box');
    const payBar = document.getElementById('fxCheckoutPayBar');
    const form = document.getElementById('fxCheckoutForm');
    const desktopSubmit = document.querySelector('.fx-checkout-submit');
    const extras = document.querySelectorAll('#extra-services .payment-option');
    const payments = document.querySelectorAll('.fx-checkout-stack--payments .payment-option');
    const vehicleRow = document.querySelector('.fx-checkout-vehicle');

    const layoutCols = checkoutLayout ? getComputedStyle(checkoutLayout).gridTemplateColumns : '';
    const singleCol =
      layoutCols.includes('1fr') ||
      layoutCols.split(' ').length === 1 ||
      (checkoutLayout?.getBoundingClientRect().width || 0) <= 375;

    const summaryFirst = (() => {
      if (!summary || !box) return false;
      return summary.getBoundingClientRect().top < box.getBoundingClientRect().top;
    })();

    const summaryStatic = summary
      ? getComputedStyle(summary).position === 'static'
      : false;

    const payBarVisible = payBar
      ? getComputedStyle(payBar).display !== 'none' && payBar.getBoundingClientRect().height > 0
      : false;

    const desktopSubmitHidden = desktopSubmit
      ? getComputedStyle(desktopSubmit).display === 'none'
      : false;

    const payBtn = payBar?.querySelector('button[type="submit"]');
    const payBtnForm = payBtn?.getAttribute('form') || '';

    const layoutPadRaw = checkoutLayout ? getComputedStyle(checkoutLayout).paddingBottom : '0px';
    const layoutPad = parseFloat(layoutPadRaw) || 0;

    return {
      build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
      cssVer: document.querySelector('link[href*="fleetx.css"]')?.getAttribute('href') || '',
      shell: !!document.querySelector('.fx-page-shell--checkout'),
      form: !!form,
      layout: !!checkoutLayout,
      summaryCard: !!document.querySelector('.fx-checkout-summary-card'),
      vehicle: !!vehicleRow,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
      singleCol,
      summaryFirst,
      summaryStatic,
      payBarVisible,
      desktopSubmitHidden,
      payBtnForm,
      layoutPad,
      layoutPadRaw,
      extrasCount: extras.length,
      paymentsCount: payments.length,
      cardForm: !!document.getElementById('card-payment-form'),
      mobileTotal: !!document.getElementById('fxCheckoutMobileTotal'),
    };
  });

  report(data.build === '113', 'Build 113', data.build);
  report(data.cssVer.includes('v=113'), 'fleetx.css v=113', data.cssVer);
  report(data.shell, 'Checkout page shell');
  report(data.form, 'Checkout form (#fxCheckoutForm)');
  report(data.layout && data.summaryCard, 'Layout + order summary');
  report(data.vehicle, 'Vehicle summary row');
  report(!data.overflow, 'No horizontal overflow');
  report(data.singleCol, 'Single-column layout');
  report(data.summaryFirst, 'Summary appears before form on mobile');
  report(data.summaryStatic, 'Summary not sticky on mobile');
  report(data.payBarVisible, 'Mobile pay bar visible');
  report(data.desktopSubmitHidden, 'Desktop submit hidden on mobile');
  report(data.payBtnForm === 'fxCheckoutForm', 'Pay bar submits checkout form', data.payBtnForm);
  report(data.layoutPad >= 100, 'Layout bottom padding for sticky bar', data.layoutPadRaw || String(data.layoutPad));
  report(data.extrasCount >= 3, 'Extra service options', String(data.extrasCount));
  report(data.paymentsCount >= 5, 'Payment method options', String(data.paymentsCount));
  report(data.mobileTotal, 'Mobile total synced element');

  const touchTargets = await page.evaluate(() => {
    const opts = [
      ...document.querySelectorAll('#extra-services .payment-option'),
      ...document.querySelectorAll('.fx-checkout-stack--payments .payment-option'),
    ];
    return opts.map((el) => el.getBoundingClientRect().height);
  });
  report(touchTargets.every((h) => h >= 44), 'Payment option touch targets', touchTargets.map(Math.round).join(','));

  const payBtnH = await page.evaluate(() => {
    const btn = document.querySelector('#fxCheckoutPayBar .fx-mobile-bid-btn');
    return btn ? btn.getBoundingClientRect().height : 0;
  });
  report(payBtnH >= 44, 'Pay bar button touch target', String(Math.round(payBtnH)));

  // Toggle extra service — mobile total should update
  const firstExtra = page.locator('#extra-services .extra-service-cb').first();
  await firstExtra.check();
  await page.waitForTimeout(300);
  const totalsMatch = await page.evaluate(() => {
    const main = document.getElementById('total-amount-display')?.textContent?.trim() || '';
    const mobile = document.getElementById('fxCheckoutMobileTotal')?.textContent?.trim() || '';
    return main === mobile && main.length > 0;
  });
  report(totalsMatch, 'Mobile total syncs with summary total');

  // Card form visibility
  await page.locator('input[name="payment_method"][value="card"]').check();
  await page.waitForTimeout(200);
  const cardVisible = await page.evaluate(() =>
    document.getElementById('card-payment-form')?.classList.contains('is-visible')
  );
  report(cardVisible, 'Card form shows when card selected');

  const inputHeights = await page.evaluate(() =>
    [...document.querySelectorAll('.fx-checkout-field-input')].map((el) => el.getBoundingClientRect().height)
  );
  report(inputHeights.every((h) => h >= 44), 'Card field touch targets', inputHeights.map(Math.round).join(','));

  await page.screenshot({ path: `${OUT}/build113-checkout-375.png` });
  console.log(`[SHOT] ${OUT}/build113-checkout-375.png`);
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Checkout Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);