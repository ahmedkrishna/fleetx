/**
 * About page mobile polish @ 375px — Build 112
 * node tests/verify_about_mobile.mjs
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
      await page.waitForSelector('.fx-page-shell--about', { timeout: 15000 });
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
  await gotoWithRetry(page, `${BASE}/about.php?t=${Date.now()}`);

  const data = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
    shell: !!document.querySelector('.fx-page-shell--about'),
    intro: !!document.querySelector('.fx-about-v2__intro'),
    pillars: document.querySelectorAll('.fx-about-v2__pillar').length,
    tracks: document.querySelectorAll('.fx-about-v2__track').length,
    cta: document.querySelectorAll('.fx-about-v2__cta .btn').length,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    heroMetaScroll: (() => {
      const meta = document.querySelector('.fx-page-hero__meta');
      if (!meta) return false;
      const s = getComputedStyle(meta);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    pillarsSingleCol: (() => {
      const grid = document.querySelector('.fx-about-v2__pillars');
      if (!grid) return false;
      const cols = getComputedStyle(grid).gridTemplateColumns;
      return cols.includes('1fr') || cols.split(' ').length === 1;
    })(),
    stepsScroll: (() => {
      const steps = document.querySelector('.fx-about-v2__steps');
      if (!steps) return false;
      const s = getComputedStyle(steps);
      return s.overflowX === 'auto' || s.overflowX === 'scroll';
    })(),
    ctaStacked: (() => {
      const cta = document.querySelector('.fx-about-v2__cta');
      if (!cta) return false;
      return getComputedStyle(cta).flexDirection === 'column';
    })(),
    arrowsHidden: [...document.querySelectorAll('.fx-about-v2__arrow')].every(el => {
      const s = getComputedStyle(el);
      return s.display === 'none';
    }),
  }));

  report(data.build === '112', 'Build 112', data.build);
  report(data.shell, 'About page shell');
  report(data.intro && data.pillars === 3, 'Intro + 3 pillars', String(data.pillars));
  report(data.tracks === 2, 'Buyer + seller tracks', String(data.tracks));
  report(data.cta >= 2, 'CTA buttons', String(data.cta));
  report(!data.overflow, 'No horizontal overflow');
  report(data.heroMetaScroll, 'Hero meta chips scrollable');
  report(data.pillarsSingleCol, 'Pillars single column');
  report(data.stepsScroll, 'Journey steps scrollable');
  report(data.arrowsHidden, 'Step arrows hidden on mobile');
  report(data.ctaStacked, 'CTA buttons stacked');

  const ctaSizes = await page.evaluate(() => {
    const btns = [...document.querySelectorAll('.fx-about-v2__cta .btn')];
    return btns.map(b => b.getBoundingClientRect().height);
  });
  report(ctaSizes.every(h => h >= 44), 'CTA touch targets', ctaSizes.map(Math.round).join(','));

  await page.screenshot({ path: `${OUT}/build112-about-375.png` });
  console.log(`[SHOT] ${OUT}/build112-about-375.png`);
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== About Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);