import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const OUT = 'tests/screenshots';
const WIDTH = 375;
const HEIGHT = 812;

let pass = 0;
let fail = 0;

function report(ok, label, detail = '') {
  if (ok) {
    pass++;
    console.log(`[PASS] ${label}`);
  } else {
    fail++;
    console.log(`[FAIL] ${label}${detail ? ` — ${detail}` : ''}`);
  }
}

async function gotoPage(page, url) {
  return page.goto(`${url}${url.includes('?') ? '&' : '?'}v=${Date.now()}`, {
    waitUntil: 'load',
    timeout: 30000,
  });
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function loginAs(page, { type, mobile, password }) {
  let lastErr;
  for (let attempt = 1; attempt <= 5; attempt++) {
    try {
      await gotoPage(page, `${BASE}/login.php?type=${type}`);
      if (!page.url().includes('login.php')) return true;
      await page.waitForFunction(
        () => document.getElementById('loginForm')?.classList.contains('visible'),
        { timeout: 20000 }
      );
      await page.fill('#mobile', mobile);
      await page.fill('#password', password);
      await page.click('button[type="submit"]');
      await page.waitForURL((url) => !url.pathname.includes('login.php'), {
        timeout: 60000,
        waitUntil: 'domcontentloaded',
      });
      return true;
    } catch (err) {
      lastErr = err;
      if (attempt < 5) await sleep(1200 * attempt);
    }
  }
  throw lastErr;
}

async function testSubpageHero(page, name, url, opts = {}) {
  const tag = `${name}/375`;
  try {
    const res = await gotoPage(page, url);
    report(res?.ok(), `${tag} HTTP`, String(res?.status()));

    await page.waitForSelector('.fx-page-hero', { timeout: 15000 });
    if (opts.shell) {
      await page.waitForSelector(opts.shell, { timeout: 10000 });
      report(true, `${tag} page shell`);
    }

    const hero = await page.evaluate(() => {
      const el = document.querySelector('.fx-page-hero');
      const title = document.querySelector('.fx-page-hero__title');
      const overlayDark = document.querySelector('.fx-page-hero__overlay-dark');
      const overlayFade = document.querySelector('.fx-page-hero__overlay-fade');
      const gradient = document.querySelector('.fx-page-hero__gradient');
      const bg = document.querySelector('.fx-page-hero__bg');
      const nav = document.querySelector('.navbar');

      const heroCs = el ? getComputedStyle(el) : null;
      const titleCs = title ? getComputedStyle(title) : null;
      const navCs = nav ? getComputedStyle(nav) : null;
      const navLink = document.querySelector('.navbar-links a');
      const navLinkCs = navLink ? getComputedStyle(navLink) : null;
      const titleRect = title?.getBoundingClientRect();
      const heroRect = el?.getBoundingClientRect();

      const parseRgb = (s) => {
        const m = (s || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        return m ? [+m[1], +m[2], +m[3]] : null;
      };
      const heroBg = parseRgb(heroCs?.backgroundColor);
      const titleRgb = parseRgb(titleCs?.color);
      const navBg = parseRgb(navCs?.backgroundColor);
      const navLinkRgb = parseRgb(navLinkCs?.color);

      return {
        hasHero: !!el,
        hasOverlayDark: !!overlayDark,
        hasOverlayFade: !!overlayFade,
        hasGradient: !!gradient,
        hasBg: !!bg,
        isCover: el?.classList.contains('fx-page-hero--cover') ?? false,
        isLightClass: el?.classList.contains('fx-page-hero--light') ?? false,
        heroBg,
        titleRgb,
        titleColor: titleCs?.color,
        navBg,
        navLinkRgb,
        navLinkColor: navLinkCs?.color,
        titleVisible: titleRect ? titleRect.width > 0 && titleRect.height > 0 && titleRect.top < window.innerHeight : false,
        heroHeight: heroRect ? Math.round(heroRect.height) : 0,
        bodyShell: document.body.classList.contains('fx-page-shell'),
      };
    });

    report(hero.hasHero, `${tag} fx-page-hero present`);
    report(hero.hasOverlayDark, `${tag} dark overlay scrim`);
    report(hero.hasOverlayFade && hero.hasGradient, `${tag} overlay layers`, JSON.stringify({
      fade: hero.hasOverlayFade,
      gradient: hero.hasGradient,
      bg: hero.hasBg,
    }));
    report(hero.bodyShell, `${tag} fx-page-shell body`);

    const heroBgDark = hero.heroBg && hero.heroBg[0] < 40 && hero.heroBg[1] < 40 && hero.heroBg[2] < 50;
    report(heroBgDark, `${tag} dark hero background`, JSON.stringify(hero.heroBg));

    const titleWhite = hero.titleRgb && hero.titleRgb[0] > 200 && hero.titleRgb[1] > 200 && hero.titleRgb[2] > 200;
    report(titleWhite, `${tag} white hero title`, hero.titleColor);
    report(hero.titleVisible, `${tag} title visible in viewport`);

    if (opts.expectCover) {
      report(hero.isCover, `${tag} cover hero modifier`);
    }

    const overflow = await page.evaluate(() => {
      const w = document.documentElement.scrollWidth;
      const vw = document.documentElement.clientWidth;
      return { scrollWidth: w, clientWidth: vw, overflows: w > vw + 2 };
    });
    report(!overflow.overflows, `${tag} no horizontal overflow`, JSON.stringify(overflow));

    const shot = `${OUT}/subpage-${name}-hero-mobile-375-viewport.png`;
    await page.screenshot({ path: shot, fullPage: false });
    console.log(`[SHOT] ${shot}`);
  } catch (e) {
    report(false, `${tag} run`, e.message);
  }
}

const PUBLIC_PAGES = [
  { name: 'auctions-live', url: `${BASE}/auctions.php?type=live`, shell: '.fx-page-shell--search' },
  { name: 'auctions-instant', url: `${BASE}/auctions.php?type=instant`, shell: '.fx-page-shell--search' },
  { name: 'map', url: `${BASE}/map.php`, shell: '.fx-page-shell--map' },
  { name: 'about', url: `${BASE}/about.php`, shell: '.fx-page-shell--legal' },
  { name: 'terms', url: `${BASE}/terms.php`, shell: '.fx-page-shell--legal' },
  { name: 'companies', url: `${BASE}/companies.php` },
  { name: 'event', url: `${BASE}/event.php?id=1`, shell: '.fx-page-shell--listing' },
  { name: 'vehicle-details', url: `${BASE}/vehicle-details.php?id=8`, shell: '.fx-page-shell--vehicle', expectCover: true },
  { name: 'auction-live', url: `${BASE}/auction-live.php?id=1`, shell: '.fx-page-shell--live', expectCover: true },
];

const AUTH_PAGES = [
  { name: 'buyer', url: `${BASE}/buyer.php?section=dashboard`, shell: '.fx-page-shell--buyer', login: { type: 'trader', mobile: '0501111111', password: '123456' } },
  { name: 'seller', url: `${BASE}/seller.php?section=dashboard`, shell: '.fx-page-shell--seller', login: { type: 'company', mobile: '0500000002', password: '123456' } },
];

const fs = await import('fs');
if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });

console.log('=== Sub-page Dark Heroes @ 375px ===\n');

try {
  const pubCtx = await browser.newContext({
    viewport: { width: WIDTH, height: HEIGHT },
    locale: 'ar-SA',
    ...devices['iPhone SE'],
    extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
  });
  const pubPage = await pubCtx.newPage();
  for (const p of PUBLIC_PAGES) {
    await testSubpageHero(pubPage, p.name, p.url, p);
  }
  await pubCtx.close();

  for (const p of AUTH_PAGES) {
    const ctx = await browser.newContext({
      viewport: { width: WIDTH, height: HEIGHT },
      locale: 'ar-SA',
      ...devices['iPhone SE'],
      extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
    });
    const page = await ctx.newPage();
    try {
      await loginAs(page, p.login);
      await testSubpageHero(page, p.name, p.url, p);
    } catch (e) {
      report(false, `${p.name}/375 run`, e.message);
    }
    await ctx.close();
    await sleep(1500);
  }
} finally {
  await browser.close();
}

console.log(`\n=== SUB-PAGE HERO MOBILE 375: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);