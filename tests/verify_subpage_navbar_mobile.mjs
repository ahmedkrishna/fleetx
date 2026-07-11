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

function parseRgb(color) {
  const m = (color || '').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
  return m ? [+m[1], +m[2], +m[3]] : null;
}

function isLightText(rgb) {
  return rgb && rgb[0] >= 180 && rgb[1] >= 180 && rgb[2] >= 180;
}

function isDarkText(rgb) {
  return rgb && rgb[0] <= 80 && rgb[1] <= 90 && rgb[2] <= 100;
}

async function gotoPage(page, url) {
  const res = await page.goto(`${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`, {
    waitUntil: 'load',
    timeout: 30000,
  });
  await page.waitForSelector('.navbar', { timeout: 10000 });
  return res;
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function loginAs(page, { type, mobile, password }) {
  let lastErr;
  for (let attempt = 1; attempt <= 5; attempt++) {
    try {
      await page.goto(`${BASE}/login.php?type=${type}&t=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
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

async function readNavbar(page) {
  return page.evaluate(() => {
    const body = document.body;
    const nav = document.querySelector('.navbar');
    const link = document.querySelector('.navbar-links a');
    const toggle = document.querySelector('#navToggle, .navbar-toggle');
    const logo = document.querySelector('.navbar-logo img, .navbar-logo');
    const cssLink = document.querySelector('link[href*="fleetx.css"]');

    const navCs = nav ? getComputedStyle(nav) : null;
    const linkCs = link ? getComputedStyle(link) : null;
    const toggleCs = toggle ? getComputedStyle(toggle) : null;

    const navRect = nav?.getBoundingClientRect();
    const toggleRect = toggle?.getBoundingClientRect();

    return {
      bodyClass: body.className,
      isHomeIndex: body.classList.contains('fx-home-index'),
      isPageShell: body.classList.contains('fx-page-shell'),
      isFxHome: body.classList.contains('fx-home'),
      cssHref: cssLink?.getAttribute('href') || '',
      navVisible: navRect ? navRect.width > 0 && navRect.height > 0 : false,
      navWidth: navRect ? Math.round(navRect.width) : 0,
      toggleVisible: toggleRect ? toggleRect.width > 0 && toggleRect.height > 0 : false,
      hasLogo: !!logo,
      navBg: navCs?.backgroundColor,
      linkColor: linkCs?.color,
      toggleColor: toggleCs?.color,
      backdrop: navCs?.backdropFilter || navCs?.webkitBackdropFilter || '',
    };
  });
}

async function testNavbar(page, name, url, mode, opts = {}) {
  const tag = `${name}/375`;
  try {
    const res = await gotoPage(page, url);
    report(res?.ok(), `${tag} HTTP`, String(res?.status()));

    const nav = await readNavbar(page);

    report(nav.cssHref.includes('fleetx.css?v=8'), `${tag} fleetx.css v=8`, nav.cssHref);
    report(nav.navVisible, `${tag} navbar visible`, `width=${nav.navWidth}`);
    report(nav.hasLogo, `${tag} logo present`);
    report(nav.toggleVisible, `${tag} mobile menu toggle visible`);

    const linkRgb = parseRgb(nav.linkColor);
    const toggleRgb = parseRgb(nav.toggleColor);

    if (mode === 'dark') {
      report(nav.isFxHome && nav.isPageShell && !nav.isHomeIndex, `${tag} sub-page body classes`, nav.bodyClass);
      report(isLightText(linkRgb), `${tag} light nav link text (dark navbar)`, nav.linkColor);
      report(isLightText(toggleRgb), `${tag} light menu toggle (dark navbar)`, nav.toggleColor);
      const hasBlur = nav.backdrop.includes('blur');
      report(hasBlur, `${tag} navbar glass blur`, nav.backdrop || 'none');
    } else if (mode === 'light') {
      report(nav.isHomeIndex, `${tag} homepage body class`, nav.bodyClass);
      report(isDarkText(linkRgb), `${tag} dark nav link text (light navbar)`, nav.linkColor);
      report(isDarkText(toggleRgb), `${tag} dark menu toggle (light navbar)`, nav.toggleColor);
    }

    if (opts.shell) {
      await page.waitForSelector(opts.shell, { timeout: 10000 });
      report(true, `${tag} page shell marker`);
    }

    const overflow = await page.evaluate(() => {
      const n = document.querySelector('.navbar');
      const w = document.documentElement.scrollWidth;
      const vw = document.documentElement.clientWidth;
      const navOverflow = n ? n.scrollWidth > n.clientWidth + 2 : false;
      return {
        pageOverflow: w > vw + 2,
        navOverflow,
        scrollWidth: w,
        clientWidth: vw,
      };
    });
    report(!overflow.pageOverflow, `${tag} no page horizontal overflow`, JSON.stringify(overflow));
    report(!overflow.navOverflow, `${tag} navbar fits viewport`);

    const shot = `${OUT}/navbar-${name}-mobile-375-viewport.png`;
    await page.screenshot({ path: shot, fullPage: false });
    console.log(`[SHOT] ${shot}`);
  } catch (e) {
    report(false, `${tag} run`, e.message);
  }
}

const SUB_PAGES = [
  { name: 'auctions-live', url: `${BASE}/auctions.php?type=live`, shell: '.fx-page-shell--search' },
  { name: 'auctions-instant', url: `${BASE}/auctions.php?type=instant`, shell: '.fx-page-shell--search' },
  { name: 'map', url: `${BASE}/map.php`, shell: '.fx-page-shell--map' },
  { name: 'about', url: `${BASE}/about.php`, shell: '.fx-page-shell--legal' },
  { name: 'terms', url: `${BASE}/terms.php`, shell: '.fx-page-shell--legal' },
  { name: 'companies', url: `${BASE}/companies.php` },
  { name: 'event', url: `${BASE}/event.php?id=1`, shell: '.fx-page-shell--listing' },
  { name: 'vehicle-details', url: `${BASE}/vehicle-details.php?id=8`, shell: '.fx-page-shell--vehicle' },
  { name: 'auction-live', url: `${BASE}/auction-live.php?id=1`, shell: '.fx-page-shell--live' },
];

const AUTH_PAGES = [
  { name: 'buyer', url: `${BASE}/buyer.php?section=dashboard`, shell: '.fx-page-shell--buyer', login: { type: 'trader', mobile: '0501111111', password: '123456' } },
  { name: 'seller', url: `${BASE}/seller.php?section=dashboard`, shell: '.fx-page-shell--seller', login: { type: 'company', mobile: '0500000002', password: '123456' } },
];

const fs = await import('fs');
if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });

console.log('=== Sub-page Navbars @ 375px ===\n');

try {
  const homeCtx = await browser.newContext({
    viewport: { width: WIDTH, height: HEIGHT },
    locale: 'ar-SA',
    ...devices['iPhone SE'],
    extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
  });
  const homePage = await homeCtx.newPage();
  await testNavbar(homePage, 'homepage-control', `${BASE}/`, 'light');
  await homeCtx.close();

  for (const p of SUB_PAGES) {
    const ctx = await browser.newContext({
      viewport: { width: WIDTH, height: HEIGHT },
      locale: 'ar-SA',
      ...devices['iPhone SE'],
      extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
    });
    const page = await ctx.newPage();
    await testNavbar(page, p.name, p.url, 'dark', p);
    await ctx.close();
  }

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
      await testNavbar(page, p.name, p.url, 'dark', p);
    } catch (e) {
      report(false, `${p.name}/375 run`, e.message);
    }
    await ctx.close();
    await sleep(1500);
  }
} finally {
  await browser.close();
}

console.log(`\n=== SUB-PAGE NAVBAR MOBILE 375: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);