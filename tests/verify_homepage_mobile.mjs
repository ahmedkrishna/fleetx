/**
 * Homepage mobile polish verify @ 375px — Build 106
 * node tests/verify_homepage_mobile.mjs
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
  await page.goto(`${BASE}/?t=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
  await page.waitForSelector('body.fx-home-index', { timeout: 15000 });

  const hero = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
    homeCss: document.querySelector('link[href*="home-live.css"]')?.getAttribute('href') || '',
    title: !!document.querySelector('.fx-hero-title--fleet1'),
    subtitle: !!document.querySelector('.fx-hero-subtitle--fleet1'),
    titleVisible: (() => {
      const el = document.querySelector('.fx-hero-title--fleet1');
      if (!el) return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && r.top < window.innerHeight && r.top > 40;
    })(),
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
  }));

  report(hero.build === '106', 'Build 106', hero.build);
  report(hero.homeCss.includes('v=106'), 'home-live.css v=106', hero.homeCss);
  report(hero.title && hero.subtitle, 'Hero title + subtitle present');
  report(hero.titleVisible, 'Hero title visible below navbar');
  report(!hero.overflow, 'No horizontal overflow');

  const navbarLight = await page.evaluate(() => {
    const nav = document.querySelector('.navbar');
    if (!nav) return { ok: false, bg: 'missing' };
    const bg = getComputedStyle(nav).backgroundColor;
    const m = bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (!m) return { ok: false, bg };
    const ok = parseInt(m[1], 10) > 200 && parseInt(m[2], 10) > 200 && parseInt(m[3], 10) > 200;
    return { ok, bg, fixed: getComputedStyle(nav).position === 'fixed' };
  });
  report(navbarLight.ok, 'Light navbar on mobile', navbarLight.bg);
  report(navbarLight.fixed, 'Navbar fixed on mobile');

  await page.click('#navToggle');
  await page.waitForTimeout(350);
  const menu = await page.evaluate(() => ({
    open: document.getElementById('mobileMenu')?.classList.contains('open'),
    bodyLock: document.body.classList.contains('fx-mobile-menu-open'),
    aria: document.getElementById('navToggle')?.getAttribute('aria-expanded'),
    links: document.querySelectorAll('#mobileMenu a').length,
    loginCta: !!document.querySelector('#mobileMenu .fx-mobile-login'),
  }));
  report(menu.open && menu.bodyLock, 'Mobile menu opens');
  report(menu.aria === 'true', 'Menu aria-expanded', menu.aria);
  report(menu.links >= 5, 'Mobile menu links', String(menu.links));
  report(menu.loginCta, 'Mobile login CTA for guests');
  await page.keyboard.press('Escape');
  const menuClosed = await page.evaluate(() => !document.getElementById('mobileMenu')?.classList.contains('open'));
  report(menuClosed, 'Mobile menu closes on Escape');

  const sections = await page.evaluate(() => ({
    auctions: !!document.querySelector('.fx-home-auctions'),
    toggle: !!document.querySelector('.fx-auction-type-toggle'),
    hiw: !!document.querySelector('.fx-hiw-section--dark'),
    why: !!document.querySelector('.fx-why-fleetx'),
    stats: !!document.querySelector('.fx-home-stats-section'),
    contact: !!document.querySelector('.fx-home-contact'),
    statsGap: getComputedStyle(document.querySelector('.main-stats-container') || document.body).gap,
  }));
  report(sections.auctions && sections.toggle, 'Auctions + type toggle');
  report(sections.hiw && sections.why, 'HIW + Why sections');
  report(sections.stats && sections.contact, 'Stats + Contact sections');
  report(sections.statsGap !== '100px', 'Stats gap tightened on mobile', sections.statsGap);

  await page.screenshot({ path: `${OUT}/build106-homepage-mobile-375.png` });
  console.log(`[SHOT] ${OUT}/build106-homepage-mobile-375.png`);

  await page.selectOption?.('select', '').catch(() => {});
  const toggleWorks = await page.evaluate(() => {
    const sw = document.getElementById('auctionTypeSwitch');
    if (!sw) return false;
    sw.click();
    return document.getElementById('tab-content-instant')?.classList.contains('active');
  });
  report(toggleWorks, 'Auction type toggle switches to instant');
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Homepage Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);