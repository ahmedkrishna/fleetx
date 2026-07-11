import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const OUT = 'tests/screenshots';
const FULL_PAGE_SHOTS = process.env.FULL_PAGE_SHOTS === '1';

const MOBILE_VIEWPORTS = [
  { label: '375', width: 375, height: 812 },
  { label: '390', width: 390, height: 844 },
];

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

async function gotoWithRetry(page, url, attempts = 3) {
  let lastErr;
  for (let i = 1; i <= attempts; i++) {
    try {
      const res = await page.goto(url, { waitUntil: 'load', timeout: 30000 });
      await page.waitForTimeout(400);
      return res;
    } catch (err) {
      lastErr = err;
      if (i < attempts) await page.waitForTimeout(800 * i);
    }
  }
  throw lastErr;
}

async function overflowCheck(page) {
  return page.evaluate(() => {
    const w = document.documentElement.scrollWidth;
    const vw = document.documentElement.clientWidth;
    return { scrollWidth: w, clientWidth: vw, overflows: w > vw + 2 };
  });
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function loginAs(page, { type, mobile, password }, label = 'login') {
  let lastErr;
  for (let attempt = 1; attempt <= 5; attempt++) {
    try {
      await gotoWithRetry(page, `${BASE}/login.php?type=${type}&t=${Date.now()}`);
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
  report(false, label, lastErr?.message || 'login failed');
  return false;
}

async function testAuctionPage(page, vp, cfg) {
  const tag = `${cfg.name}/${vp.label}`;
  try {
    const res = await gotoWithRetry(page, `${cfg.url}&v=${Date.now()}`);
    report(res?.ok(), `${tag} HTTP`, String(res?.status()));

    await page.waitForSelector(cfg.shell, { timeout: 15000 });
    await page.waitForSelector('.fx-page-hero', { timeout: 10000 });
    report(true, `${tag} dark hero present`);

    const cards = await page.locator(cfg.cardSelector).count();
    report(cards > 0, `${tag} listing cards`, `count=${cards}`);

    const overflow = await overflowCheck(page);
    report(!overflow.overflows, `${tag} no horizontal overflow`, JSON.stringify(overflow));

    if (cfg.name.startsWith('auctions')) {
      const filterToggle = await page.locator('.mobile-sidebar-toggle').count();
      report(filterToggle > 0, `${tag} mobile filter toggle`, `found=${filterToggle}`);
      const heroTitleVisible = await page.evaluate(() => {
        const t = document.querySelector('.fx-page-hero__title');
        if (!t) return false;
        const r = t.getBoundingClientRect();
        return r.width > 0 && r.height > 0 && r.top < window.innerHeight;
      });
      report(heroTitleVisible, `${tag} hero title visible in viewport`);
    }

    if (cfg.name === 'event') {
      const toolbar = await page.locator('.fx-toolbar').count();
      report(toolbar > 0, `${tag} event toolbar`, `found=${toolbar}`);
    }

    if (cfg.name === 'auction-live') {
      const mobileBar = await page.evaluate(() => {
        const bar = document.querySelector('.fx-mobile-bid-bar');
        if (!bar) return false;
        const s = getComputedStyle(bar);
        return s.display !== 'none' && bar.getBoundingClientRect().height > 0;
      });
      report(mobileBar, `${tag} mobile bid bar visible`);
    }

    const shot = `${OUT}/${cfg.name}-mobile-${vp.label}${FULL_PAGE_SHOTS ? '' : '-viewport'}.png`;
    await page.screenshot({ path: shot, fullPage: FULL_PAGE_SHOTS });
    console.log(`[SHOT] ${shot}`);
  } catch (e) {
    report(false, `${tag} run`, e.message);
  }
}

async function testDashboardSection(page, vp, cfg) {
  const tag = `${cfg.name}/${vp.label}`;
  try {
    const res = await gotoWithRetry(page, `${cfg.url}&v=${Date.now()}`);
    report(res?.ok(), `${tag} HTTP`, String(res?.status()));

    await page.waitForSelector(cfg.shell, { timeout: 15000 });
    await page.waitForSelector('.fx-page-hero', { timeout: 10000 });
    report(true, `${tag} dark hero present`);

    const layout = await page.evaluate(({ sidebarSel, mainSel }) => {
      const sidebar = document.querySelector(sidebarSel);
      const mobileNav = document.querySelector('.fx-dash-mobile-nav');
      const main = document.querySelector(mainSel);
      const sidebarStyle = sidebar ? getComputedStyle(sidebar) : null;
      const mobileNavStyle = mobileNav ? getComputedStyle(mobileNav) : null;
      return {
        sidebarHidden: sidebarStyle ? sidebarStyle.display === 'none' : true,
        mobileNavVisible: mobileNavStyle ? mobileNavStyle.display !== 'none' : false,
        mobileNavWidth: mobileNav ? Math.round(mobileNav.getBoundingClientRect().width) : 0,
        mainVisible: main ? main.getBoundingClientRect().width > 0 : false,
      };
    }, { sidebarSel: cfg.sidebarSelector, mainSel: cfg.mainSelector });

    report(layout.sidebarHidden, `${tag} sidebar hidden on mobile`, JSON.stringify(layout));
    report(layout.mobileNavVisible, `${tag} mobile nav select visible`);
    report(layout.mobileNavWidth >= vp.width - 40, `${tag} mobile nav full width`, `width=${layout.mobileNavWidth}`);
    report(layout.mainVisible, `${tag} main content visible`);

    const overflow = await overflowCheck(page);
    report(!overflow.overflows, `${tag} no horizontal overflow`, JSON.stringify(overflow));

    const cards = await page.locator(cfg.contentSelector).count();
    report(cards > 0, `${tag} dashboard content blocks`, `count=${cards}`);

    const shot = `${OUT}/${cfg.name}-mobile-${vp.label}${FULL_PAGE_SHOTS ? '' : '-viewport'}.png`;
    await page.screenshot({ path: shot, fullPage: FULL_PAGE_SHOTS });
    console.log(`[SHOT] ${shot}`);
  } catch (e) {
    report(false, `${tag} run`, e.message);
  }
}

const AUCTION_PAGES = [
  {
    name: 'auctions-live',
    url: `${BASE}/auctions.php?type=live`,
    shell: '.fx-page-shell--search',
    cardSelector: '.fx-listing-grid .auction-card',
  },
  {
    name: 'auctions-instant',
    url: `${BASE}/auctions.php?type=instant`,
    shell: '.fx-page-shell--search',
    cardSelector: '.fx-listing-grid .auction-card',
  },
  {
    name: 'event',
    url: `${BASE}/event.php?id=1`,
    shell: '.fx-page-shell--listing',
    cardSelector: '.fx-event-lots-grid .auction-card',
  },
  {
    name: 'vehicle-details',
    url: `${BASE}/vehicle-details.php?id=8`,
    shell: '.fx-page-shell--vehicle',
    cardSelector: '.mazad-premium-gallery',
  },
  {
    name: 'auction-live',
    url: `${BASE}/auction-live.php?id=1`,
    shell: '.fx-page-shell--live',
    cardSelector: '.mazad-premium-gallery',
  },
];

const BUYER_SECTIONS = [
  {
    name: 'buyer-dashboard',
    url: `${BASE}/buyer.php?section=dashboard`,
    shell: '.fx-page-shell--buyer',
    sidebarSelector: '.fx-buyer-sidebar',
    mainSelector: '.fx-buyer-main',
    contentSelector: '.fx-buyer-card, .stat-grid .stat-card',
  },
  {
    name: 'buyer-bids',
    url: `${BASE}/buyer.php?section=bids`,
    shell: '.fx-page-shell--buyer',
    sidebarSelector: '.fx-buyer-sidebar',
    mainSelector: '.fx-buyer-main',
    contentSelector: '.fx-buyer-card, .fx-dash-card-grid .auction-card, .fx-empty-state',
  },
  {
    name: 'buyer-wallet',
    url: `${BASE}/buyer.php?section=wallet`,
    shell: '.fx-page-shell--buyer',
    sidebarSelector: '.fx-buyer-sidebar',
    mainSelector: '.fx-buyer-main',
    contentSelector: '.fx-buyer-card, .fx-wallet-card, .wallet-stat',
  },
];

const SELLER_SECTIONS = [
  {
    name: 'seller-dashboard',
    url: `${BASE}/seller.php?section=dashboard`,
    shell: '.fx-page-shell--seller',
    sidebarSelector: '.fx-seller-sidebar',
    mainSelector: '.fx-seller-main',
    contentSelector: '.fx-seller-card, .stats-grid .stat-card',
  },
  {
    name: 'seller-fleet',
    url: `${BASE}/seller.php?section=fleet`,
    shell: '.fx-page-shell--seller',
    sidebarSelector: '.fx-seller-sidebar',
    mainSelector: '.fx-seller-main',
    contentSelector: '.fx-seller-card, .fleet-table, .fx-empty-state',
  },
];

const fs = await import('fs');
if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ headless: true });

try {
  console.log('=== Mobile Auction Pages ===\n');
  for (const vp of MOBILE_VIEWPORTS) {
    const context = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      locale: 'ar-SA',
      ...devices['iPhone 13'],
    });
    const page = await context.newPage();
    for (const cfg of AUCTION_PAGES) {
      await testAuctionPage(page, vp, cfg);
    }
    await context.close();
  }

  console.log('\n=== Mobile Dashboard Pages ===\n');
  for (const vp of MOBILE_VIEWPORTS) {
    const buyerCtx = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      locale: 'ar-SA',
      ...devices['iPhone 13'],
    });
    const buyerPage = await buyerCtx.newPage();
    const buyerLoggedIn = await loginAs(
      buyerPage,
      { type: 'trader', mobile: '0501111111', password: '123456' },
      `buyer-login/${vp.label}`
    );
    if (buyerLoggedIn) {
      for (const cfg of BUYER_SECTIONS) {
        await testDashboardSection(buyerPage, vp, cfg);
      }
    } else {
      for (const cfg of BUYER_SECTIONS) {
        report(false, `${cfg.name}/${vp.label} run`, 'skipped — buyer login failed');
      }
    }
    await buyerCtx.close();

    // Live host recovery between buyer burst and seller login
    await sleep(2000);

    const sellerCtx = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      locale: 'ar-SA',
      ...devices['iPhone 13'],
    });
    const sellerPage = await sellerCtx.newPage();
    const sellerLoggedIn = await loginAs(
      sellerPage,
      { type: 'company', mobile: '0500000002', password: '123456' },
      `seller-login/${vp.label}`
    );
    if (sellerLoggedIn) {
      for (const cfg of SELLER_SECTIONS) {
        await testDashboardSection(sellerPage, vp, cfg);
      }
    } else {
      for (const cfg of SELLER_SECTIONS) {
        report(false, `${cfg.name}/${vp.label} run`, 'skipped — seller login failed');
      }
    }
    await sellerCtx.close();

    if (vp.label === '375') await sleep(1500);
  }
} finally {
  await browser.close();
}

console.log(`\n=== MOBILE DASHBOARD/AUCTION TEST: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);