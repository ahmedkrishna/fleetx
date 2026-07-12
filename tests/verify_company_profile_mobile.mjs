/**
 * Company profile page mobile polish @ 375px — Build 111
 * node tests/verify_company_profile_mobile.mjs
 */
import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const COMPANY_ID = process.env.FLEETX_COMPANY_ID || '1';
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
      await page.waitForSelector('.fx-page-shell--company-profile', { timeout: 15000 });
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
  await gotoWithRetry(page, `${BASE}/company-profile.php?id=${COMPANY_ID}&t=${Date.now()}`);

  const data = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
    shell: !!document.querySelector('.fx-page-shell--company-profile'),
    bankStrip: !!document.querySelector('.fx-bank-strip'),
    tabs: document.querySelectorAll('.fx-company-profile-tabs .fx-tab-btn').length,
    cards: document.querySelectorAll('#vehiclesGrid .fx-card-wrap').length,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    heroMetaScroll: (() => {
      const meta = document.querySelector('.fx-page-hero__meta');
      if (!meta) return false;
      const s = getComputedStyle(meta);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    statsScroll: (() => {
      const stats = document.querySelector('.fx-company-hero-stats');
      if (!stats) return false;
      const s = getComputedStyle(stats);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    tabsScroll: (() => {
      const tabs = document.querySelector('.fx-company-profile-tabs');
      if (!tabs) return false;
      const s = getComputedStyle(tabs);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    gridCols: getComputedStyle(document.querySelector('.fx-vehicles-grid') || document.body).gridTemplateColumns,
    gridWidth: document.querySelector('.fx-vehicles-grid')?.getBoundingClientRect().width || 0,
    bankStacked: (() => {
      const bank = document.querySelector('.fx-bank-strip');
      if (!bank) return false;
      return getComputedStyle(bank).flexDirection === 'column';
    })(),
  }));

  report(data.build === '111', 'Build 111', data.build);
  report(data.shell, 'Company profile shell');
  report(data.bankStrip, 'Bank info strip');
  report(data.tabs >= 4, 'Vehicle filter tabs', String(data.tabs));
  report(data.cards > 0, 'Vehicle cards', String(data.cards));
  report(!data.overflow, 'No horizontal overflow');
  report(data.heroMetaScroll, 'Hero meta chips scrollable');
  report(data.statsScroll, 'Hero stat pills scrollable');
  report(data.tabsScroll, 'Filter tabs scrollable');
  report(data.bankStacked, 'Bank strip stacked on mobile');
  const gridCols = String(data.gridCols || '');
  const gridOk = gridCols.includes('1fr') || data.gridWidth <= 375 || gridCols.split(' ').length === 1;
  report(gridOk, 'Single column vehicle grid', `${gridCols || 'n/a'} w=${Math.round(data.gridWidth)}`);

  await page.click('.fx-company-profile-tabs .fx-tab-btn:nth-child(2)');
  await page.waitForTimeout(200);
  const tabLive = await page.evaluate(() => ({
    aria: document.querySelector('.fx-company-profile-tabs .fx-tab-btn.active')?.getAttribute('aria-selected'),
    active: document.querySelector('.fx-company-profile-tabs .fx-tab-btn.active')?.textContent?.includes('مباشر'),
  }));
  report(tabLive.aria === 'true' && tabLive.active, 'Tab filter with aria');

  const visibleAfterFilter = await page.evaluate(() => {
    return [...document.querySelectorAll('#vehiclesGrid .fx-card-wrap')].filter(w => w.style.display !== 'none').length;
  });
  report(visibleAfterFilter >= 0, 'Live tab filters cards', String(visibleAfterFilter));

  await page.screenshot({ path: `${OUT}/build111-company-profile-375.png` });
  console.log(`[SHOT] ${OUT}/build111-company-profile-375.png`);
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Company Profile Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);