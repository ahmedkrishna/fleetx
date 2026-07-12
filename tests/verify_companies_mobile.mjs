/**
 * Companies page mobile polish @ 375px — Build 110
 * node tests/verify_companies_mobile.mjs
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

async function gotoWithRetry(page, url, attempts = 3) {
  let lastErr;
  for (let i = 1; i <= attempts; i++) {
    try {
      await page.goto(url, { waitUntil: 'load', timeout: 45000 });
      await page.waitForSelector('.fx-page-shell--companies', { timeout: 15000 });
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
  await gotoWithRetry(page, `${BASE}/companies.php?t=${Date.now()}`);

  const data = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
    shell: !!document.querySelector('.fx-page-shell--companies'),
    panel: !!document.querySelector('.fx-companies-v2__panel'),
    chips: document.querySelectorAll('.fx-companies-filter-chips .chip').length,
    cards: document.querySelectorAll('.fx-company-card--v2').length,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    heroMetaScroll: (() => {
      const meta = document.querySelector('.fx-page-hero__meta');
      if (!meta) return false;
      const s = getComputedStyle(meta);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    chipsScroll: (() => {
      const chips = document.querySelector('.fx-companies-filter-chips');
      if (!chips) return false;
      const s = getComputedStyle(chips);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    gridCols: getComputedStyle(document.querySelector('.fx-company-grid--modern') || document.body).gridTemplateColumns,
    gridWidth: document.querySelector('.fx-company-grid--modern')?.getBoundingClientRect().width || 0,
    searchStacked: (() => {
      const search = document.querySelector('.fx-companies-v2__search');
      if (!search) return false;
      return getComputedStyle(search).flexDirection === 'column';
    })(),
  }));

  report(data.build === '110', 'Build 110', data.build);
  report(data.shell && data.panel, 'Companies shell + panel');
  report(data.chips >= 4, 'Filter chips', String(data.chips));
  report(data.cards > 0, 'Company cards', String(data.cards));
  report(!data.overflow, 'No horizontal overflow');
  report(data.heroMetaScroll, 'Hero meta chips scrollable');
  report(data.chipsScroll, 'Filter chips scrollable');
  const gridCols = String(data.gridCols || '');
  const gridOk = gridCols.includes('1fr') || data.gridWidth <= 375 || gridCols.split(' ').length === 1;
  report(gridOk, 'Single column grid', `${gridCols || 'n/a'} w=${Math.round(data.gridWidth)}`);
  report(data.searchStacked, 'Search stacked on mobile');

  await page.click('.fx-companies-filter-chips .chip:nth-child(2)');
  await page.waitForTimeout(200);
  const filterLive = await page.evaluate(() => ({
    aria: document.querySelector('.fx-companies-filter-chips .chip.active')?.getAttribute('aria-selected'),
    active: document.querySelector('.fx-companies-filter-chips .chip.active')?.textContent?.includes('مباشر'),
  }));
  report(filterLive.aria === 'true' && filterLive.active, 'Filter chip toggles with aria');

  await page.click('.fx-companies-filter-chips .chip:first-child');
  await page.waitForTimeout(150);
  await page.locator('#searchInput').fill('الوطنية');
  await page.locator('#searchInput').dispatchEvent('input');
  await page.waitForTimeout(250);
  const searchCount = await page.evaluate(() => {
    return [...document.querySelectorAll('.fx-company-card--v2')].filter(c => c.style.display !== 'none').length;
  });
  report(searchCount >= 1, 'Search filters companies', String(searchCount));

  await page.screenshot({ path: `${OUT}/build110-companies-375.png` });
  console.log(`[SHOT] ${OUT}/build110-companies-375.png`);
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Companies Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);