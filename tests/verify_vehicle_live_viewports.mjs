import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const OUT = 'tests/screenshots';
const FULL_PAGE_SHOTS = process.env.FULL_PAGE_SHOTS === '1';
const PAGES = [
  {
    name: 'vehicle-details',
    url: `${BASE}/vehicle-details.php?id=8`,
    checks: async (page) => {
      await page.waitForSelector('.fx-page-shell--vehicle', { timeout: 15000 });
      await page.waitForSelector('.mazad-premium-gallery', { timeout: 10000 });
      await page.waitForSelector('.fx-pricing-panel', { timeout: 10000 });
      const gallery = await page.locator('.fx-vehicle-gallery-card').count();
      const pricing = await page.locator('.fx-vehicle-pricing-card').count();
      const thumbs = await page.locator('.mpg-thumb').count();
      return { gallery, pricing, thumbs };
    },
    layout: async (page, viewport) => {
      const order = await page.evaluate(() => {
        const layout = document.querySelector('.fx-detail-layout');
        if (!layout) return null;
        const kids = [...layout.children].map((el) => ({
          cls: el.className,
          order: getComputedStyle(el).order,
          top: el.getBoundingClientRect().top,
        }));
        kids.sort((a, b) => a.top - b.top);
        return kids.map((k) => `${k.cls.split(' ')[0]}:${k.order}@${Math.round(k.top)}`);
      });
      const overflow = await page.evaluate(() => {
        const w = document.documentElement.scrollWidth;
        const vw = document.documentElement.clientWidth;
        return { scrollWidth: w, clientWidth: vw, overflows: w > vw + 2 };
      });
      return { order, overflow };
    },
  },
  {
    name: 'auction-live',
    url: `${BASE}/auction-live.php?id=1`,
    checks: async (page) => {
      await page.waitForSelector('.fx-page-shell--live', { timeout: 15000 });
      await page.waitForSelector('.mazad-premium-gallery', { timeout: 10000 });
      await page.waitForSelector('.fx-live-bidding-panel', { timeout: 10000 });
      const gallery = await page.locator('.live-gallery-panel').count();
      const bidding = await page.locator('.fx-live-bidding-panel').count();
      const mobileBar = await page.locator('.fx-mobile-bid-bar').count();
      return { gallery, bidding, mobileBar };
    },
    layout: async (page, viewport) => {
      const order = await page.evaluate(() => {
        const room = document.querySelector('.fx-live-room');
        if (!room) return null;
        const kids = [...room.children].map((el) => ({
          cls: el.className,
          order: getComputedStyle(el).order,
          top: el.getBoundingClientRect().top,
        }));
        kids.sort((a, b) => a.top - b.top);
        return kids.map((k) => `${k.cls.split(' ')[0]}:${k.order}@${Math.round(k.top)}`);
      });
      const mobileBarVisible = await page.evaluate(() => {
        const bar = document.querySelector('.fx-mobile-bid-bar');
        if (!bar) return false;
        const s = getComputedStyle(bar);
        return s.display !== 'none' && bar.getBoundingClientRect().height > 0;
      });
      const overflow = await page.evaluate(() => {
        const w = document.documentElement.scrollWidth;
        const vw = document.documentElement.clientWidth;
        return { scrollWidth: w, clientWidth: vw, overflows: w > vw + 2 };
      });
      return { order, mobileBarVisible, overflow };
    },
  },
];

const viewports = [
  { label: 'mobile', width: 390, height: 844, isMobile: true },
  { label: 'desktop', width: 1280, height: 800, isMobile: false },
];

let pass = 0;
let fail = 0;

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

try {
for (const vp of viewports) {
  const context = await browser.newContext({
    viewport: { width: vp.width, height: vp.height },
    locale: 'ar-SA',
    ...(vp.isMobile ? devices['iPhone 13'] : {}),
  });
  const page = await context.newPage();

  for (const p of PAGES) {
    const tag = `${p.name}/${vp.label}`;
    try {
      const res = await gotoWithRetry(page, `${p.url}${p.url.includes('?') ? '&' : '?'}v=${Date.now()}`);
      report(res?.ok(), `${tag} HTTP`, String(res?.status()));

      const counts = await p.checks(page);
      report(counts.gallery > 0 || counts.pricing > 0 || counts.bidding > 0, `${tag} core sections`, JSON.stringify(counts));

      const layout = await p.layout(page, vp);
      report(!layout.overflow?.overflows, `${tag} no horizontal overflow`, JSON.stringify(layout.overflow));

      if (p.name === 'vehicle-details' && vp.isMobile) {
        const visual = await page.evaluate(() => {
          const gallery = document.querySelector('.fx-vehicle-gallery-card');
          const pricing = document.querySelector('.fx-pricing-panel');
          return {
            galleryTop: Math.round(gallery?.getBoundingClientRect().top ?? 0),
            pricingTop: Math.round(pricing?.getBoundingClientRect().top ?? 0),
          };
        });
        const galleryBeforePricing = visual.galleryTop < visual.pricingTop;
        report(galleryBeforePricing, `${tag} gallery before pricing`, JSON.stringify(visual));
      }

      if (p.name === 'auction-live' && vp.isMobile) {
        const visual = await page.evaluate(() => {
          const gallery = document.querySelector('.live-gallery-panel');
          const panel = document.querySelector('.pbb-board');
          return {
            galleryTop: Math.round(gallery?.getBoundingClientRect().top ?? 0),
            panelTop: Math.round(panel?.getBoundingClientRect().top ?? 0),
          };
        });
        const galleryBeforeBidding = visual.galleryTop > 0 && visual.galleryTop < visual.panelTop;
        report(galleryBeforeBidding, `${tag} gallery before bidding`, JSON.stringify(visual));
        report(layout.mobileBarVisible === true, `${tag} mobile bid bar visible`);
      }

      if (p.name === 'auction-live' && !vp.isMobile) {
        const barHidden = await page.evaluate(() => getComputedStyle(document.querySelector('.fx-mobile-bid-bar')).display === 'none');
        report(barHidden, `${tag} mobile bid bar hidden on desktop`);
      }

      const shot = `${OUT}/${p.name}-${vp.label}${FULL_PAGE_SHOTS ? '' : '-viewport'}.png`;
      await page.screenshot({ path: shot, fullPage: FULL_PAGE_SHOTS });
      console.log(`[SHOT] ${shot}`);
    } catch (e) {
      report(false, `${tag} run`, e.message);
    }
  }

  await context.close();
}
} finally {
  await browser.close();
}

console.log(`\n=== VIEWPORT TEST: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);