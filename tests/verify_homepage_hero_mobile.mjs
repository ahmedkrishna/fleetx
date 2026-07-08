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

const fs = await import('fs');
if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: WIDTH, height: HEIGHT },
  locale: 'ar-SA',
  ...devices['iPhone SE'],
  extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
});
const page = await context.newPage();

try {
  const res = await page.goto(`${BASE}/?v=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
  report(res?.ok(), 'homepage/375 HTTP', String(res?.status()));

  await page.waitForSelector('body.fx-home-index', { timeout: 15000 });
  await page.waitForSelector('.fx-hero-wrap--fleet1', { timeout: 10000 });
  report(true, 'homepage/375 fx-home-index body class');

  await page.waitForSelector('.fx-hero-wrap--fleet1', { timeout: 10000 });
  report(true, 'homepage/375 white fleet1 hero wrapper');

  const heroChecks = await page.evaluate(() => {
    const wrap = document.querySelector('.fx-hero-wrap--fleet1');
    const wrapper = document.querySelector('.fx-hero-wrapper--fleet1');
    const title = document.querySelector('.fx-hero-title--fleet1');
    const subtitle = document.querySelector('.fx-hero-subtitle--fleet1');
    const tagline = document.querySelector('.fx-hero-tagline--fleet1');
    const cta = document.querySelector('.fx-cta-row--fleet1');
    const darkV1 = document.querySelector('.fx-hero-v1-wrap');

    const titleColor = title ? getComputedStyle(title).color : '';
    const subtitleColor = subtitle ? getComputedStyle(subtitle).color : '';
    const taglineBg = tagline ? getComputedStyle(tagline).backgroundColor : '';
    const wrapBg = wrap ? getComputedStyle(wrap).backgroundColor : '';

    const titleRect = title?.getBoundingClientRect();
    const heroRect = wrapper?.getBoundingClientRect();

    return {
      hasFleet1: !!(wrap && wrapper),
      noDarkV1: !darkV1,
      hasTitle: !!title,
      hasSubtitle: !!subtitle,
      hasTagline: !!tagline,
      hasCta: !!cta,
      titleColor,
      subtitleColor,
      subtitleAboveTitle: title && subtitle ? subtitle.compareDocumentPosition(title) & Node.DOCUMENT_POSITION_FOLLOWING : false,
      taglineBg,
      wrapBg,
      titleVisible: titleRect ? titleRect.width > 0 && titleRect.height > 0 && titleRect.top < window.innerHeight : false,
      heroHeight: heroRect ? Math.round(heroRect.height) : 0,
      heroWidth: heroRect ? Math.round(heroRect.width) : 0,
    };
  });

  report(heroChecks.hasFleet1, 'homepage/375 fleet1 structure', JSON.stringify(heroChecks));
  report(heroChecks.noDarkV1, 'homepage/375 no dark v1 hero');
  report(heroChecks.hasTitle && heroChecks.hasSubtitle && !heroChecks.hasCta, 'homepage/375 hero content blocks');
  report(heroChecks.titleVisible, 'homepage/375 hero title visible in viewport');

  const titleIsDark = heroChecks.titleColor === 'rgb(15, 23, 42)';
  report(titleIsDark, 'homepage/375 dark title text', heroChecks.titleColor);
  const subtitleIsGreen = heroChecks.subtitleColor === 'rgb(27, 201, 118)';
  report(subtitleIsGreen, 'homepage/375 green subtitle text', heroChecks.subtitleColor);
  report(heroChecks.subtitleAboveTitle, 'homepage/375 subtitle above title');

  const overflow = await page.evaluate(() => {
    const w = document.documentElement.scrollWidth;
    const vw = document.documentElement.clientWidth;
    return { scrollWidth: w, clientWidth: vw, overflows: w > vw + 2 };
  });
  report(!overflow.overflows, 'homepage/375 no horizontal overflow', JSON.stringify(overflow));

  const navbarLight = await page.evaluate(() => {
    const nav = document.querySelector('.navbar');
    if (!nav) return false;
    const bg = getComputedStyle(nav).backgroundColor;
    // light navbar uses high rgb values (white-ish)
    const m = bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (!m) return false;
    return parseInt(m[1], 10) > 200 && parseInt(m[2], 10) > 200 && parseInt(m[3], 10) > 200;
  });
  report(navbarLight, 'homepage/375 light navbar on mobile');

  const shot = `${OUT}/homepage-white-hero-mobile-375-viewport.png`;
  await page.screenshot({ path: shot, fullPage: false });
  console.log(`[SHOT] ${shot}`);
} catch (e) {
  report(false, 'homepage/375 run', e.message);
} finally {
  await context.close();
  await browser.close();
}

console.log(`\n=== HOMEPAGE HERO MOBILE 375: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);