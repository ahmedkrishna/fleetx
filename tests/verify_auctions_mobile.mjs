/**
 * Auctions listing page mobile polish @ 375px — Build 107
 * node tests/verify_auctions_mobile.mjs
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

async function checkAuctionsPage(page, type) {
  const label = type === 'live' ? 'Live auctions' : 'Instant purchase';
  await page.goto(`${BASE}/auctions.php?type=${type}&t=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });

  const data = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
    shell: !!document.querySelector('.fx-page-shell--search'),
    mobileType: !!document.querySelector('.fx-auctions-mobile-type'),
    filterToggle: !!document.getElementById('fxFilterToggle'),
    filterPanel: !!document.getElementById('fxFilterPanel'),
    toolbar: !!document.querySelector('.fx-search-toolbar'),
    cards: document.querySelectorAll('.fx-listing-grid .auction-card').length,
    gridCols: getComputedStyle(document.querySelector('.fx-listing-grid') || document.body).gridTemplateColumns,
    gridWidth: document.querySelector('.fx-listing-grid')?.getBoundingClientRect().width || 0,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    heroChips: !!document.querySelector('.fx-page-hero__meta'),
  }));

  report(data.build === '107', `${label} build 107`, data.build);
  report(data.shell, `${label} search shell`);
  report(data.mobileType, `${label} mobile type switcher`);
  report(data.filterToggle && data.filterPanel, `${label} filter accordion`);
  report(data.toolbar, `${label} results toolbar`);
  report(data.cards > 0, `${label} listing cards`, String(data.cards));
  report(!data.overflow, `${label} no horizontal overflow`);
  const gridCols = String(data.gridCols || '');
  const gridOk = gridCols.includes('1fr') || data.gridWidth <= 375 || gridCols.split(' ').length === 1;
  report(gridOk, `${label} responsive grid`, `${gridCols || 'n/a'} w=${Math.round(data.gridWidth)}`);

  await page.click('#fxFilterToggle');
  await page.waitForTimeout(300);
  const filterOpen = await page.evaluate(() => ({
    expanded: document.querySelector('.filter-sidebar')?.classList.contains('expanded'),
    aria: document.getElementById('fxFilterToggle')?.getAttribute('aria-expanded'),
    visible: getComputedStyle(document.getElementById('fxFilterPanel') || document.body).display !== 'none',
  }));
  report(filterOpen.expanded && filterOpen.aria === 'true', `${label} filter panel opens`);
  await page.keyboard.press('Escape');
  const filterClosed = await page.evaluate(() => !document.querySelector('.filter-sidebar')?.classList.contains('expanded'));
  report(filterClosed, `${label} filter closes on Escape`);

  return data;
}

const fs = await import('fs');
if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({
  viewport: { width: 375, height: 812 },
  locale: 'ar-SA',
  ...devices['iPhone 13'],
});
const page = await ctx.newPage();

try {
  await checkAuctionsPage(page, 'live');
  await page.screenshot({ path: `${OUT}/build107-auctions-live-375.png` });
  console.log(`[SHOT] ${OUT}/build107-auctions-live-375.png`);

  await checkAuctionsPage(page, 'instant');
  await page.screenshot({ path: `${OUT}/build107-auctions-instant-375.png` });
  console.log(`[SHOT] ${OUT}/build107-auctions-instant-375.png`);

  await page.goto(`${BASE}/auctions.php?type=live&t=${Date.now()}`, { waitUntil: 'load' });
  const pillsScroll = await page.evaluate(() => {
    const pills = document.querySelector('.fx-pills');
    if (!pills) return false;
    const s = getComputedStyle(pills);
    return s.overflowX === 'auto' || s.overflowX === 'scroll';
  });
  report(pillsScroll, 'Status pills horizontally scrollable');
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Auctions Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);