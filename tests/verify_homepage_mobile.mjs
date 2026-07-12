/**
 * Homepage mobile + UI coordination @ 375px — Build 109
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

async function gotoWithRetry(page, url, attempts = 3) {
  let lastErr;
  for (let i = 1; i <= attempts; i++) {
    try {
      await page.goto(url, { waitUntil: 'load', timeout: 45000 });
      await page.waitForSelector('body.fx-home-index', { timeout: 15000 });
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
  await gotoWithRetry(page, `${BASE}/?t=${Date.now()}`);

  const hero = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || document.body.dataset.fxBuild || '',
    homeCss: document.querySelector('link[href*="home-live.css"]')?.getAttribute('href') || '',
    title: !!document.querySelector('.fx-hero-title--fleet1'),
    subtitle: !!document.querySelector('.fx-hero-subtitle--fleet1'),
    cta: !!document.querySelector('.fx-hero-cta-row'),
    ctaVisible: (() => {
      const el = document.querySelector('.fx-hero-cta-row');
      if (!el) return false;
      const r = el.getBoundingClientRect();
      return r.height > 0 && getComputedStyle(el).opacity !== '0';
    })(),
    titleVisible: (() => {
      const el = document.querySelector('.fx-hero-title--fleet1');
      if (!el) return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && r.top < window.innerHeight && r.top > 40;
    })(),
    heroTransform: getComputedStyle(document.querySelector('.fx-hero-content--fleet1') || document.body).transform,
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
  }));

  report(hero.build === '109', 'Build 109', hero.build);
  report(hero.homeCss.includes('v=109'), 'home-live.css v=109', hero.homeCss);
  report(hero.title && hero.subtitle && hero.cta, 'Hero title + subtitle + CTAs');
  report(hero.titleVisible, 'Hero title visible below navbar');
  report(hero.ctaVisible, 'Hero CTAs visible');
  report(hero.heroTransform === 'none' || hero.heroTransform.includes('matrix(1, 0, 0, 1, 0, 0)'), 'Hero uses flex not harsh translate', hero.heroTransform);
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
    journeyScroll: (() => {
      const steps = document.querySelector('.fx-why-journey__steps');
      if (!steps) return false;
      const s = getComputedStyle(steps);
      return s.overflowX === 'auto' || s.overflowX === 'scroll' || s.display === 'flex';
    })(),
    formBtnInForm: !!document.querySelector('.fx-contact-form-card form .fx-form-submit'),
  }));
  report(sections.auctions && sections.toggle, 'Auctions + type toggle');
  report(sections.hiw && sections.why, 'HIW + Why sections');
  report(sections.stats && sections.contact, 'Stats + Contact sections');
  report(sections.statsGap !== '100px', 'Stats gap tightened on mobile', sections.statsGap);
  report(sections.journeyScroll, 'Why journey scrollable on mobile');
  report(sections.formBtnInForm, 'Contact submit inside form');

  const toggleWorks = await page.evaluate(() => {
    const sw = document.getElementById('auctionTypeSwitch');
    if (!sw) return false;
    sw.click();
    return document.getElementById('tab-content-instant')?.classList.contains('active');
  });
  report(toggleWorks, 'Auction type toggle switches to instant');

  await page.screenshot({ path: `${OUT}/build109-homepage-mobile-375.png` });
  console.log(`[SHOT] ${OUT}/build109-homepage-mobile-375.png`);
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Homepage Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);