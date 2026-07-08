import { chromium } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';

const PAGES = [
  { url: '/', expect: 'logo.png', selector: '.navbar-logo img' },
  { url: '/index.php', expect: 'logo.png', selector: '.navbar-logo img' },
  { url: '/auctions.php?type=live', expect: 'logo-dark.png', selector: '.navbar-logo img' },
  { url: '/companies.php', expect: 'logo-dark.png', selector: '.navbar-logo img' },
  { url: '/map.php', expect: 'logo-dark.png', selector: '.navbar-logo img' },
  { url: '/about.php', expect: 'logo-dark.png', selector: '.navbar-logo img' },
  { url: '/login.php', expect: 'logo-dark.png', selector: '.fx-auth-topbar__logo img' },
  { url: '/register.php', expect: 'logo-dark.png', selector: '.fx-auth-topbar__logo img' },
  { url: '/vehicle-details.php?id=8', expect: 'logo-dark.png', selector: '.navbar-logo img' },
  { url: '/auction-live.php?id=2', expect: 'logo-dark.png', selector: '.navbar-logo img' },
];

let pass = 0;
let fail = 0;

const browser = await chromium.launch({ headless: true });

for (const { url, expect, selector } of PAGES) {
  const page = await browser.newPage();
  try {
    await page.goto(`${BASE}${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`, {
      waitUntil: 'load',
      timeout: 30000,
    });
    const src = await page.evaluate((sel) => {
      const img = document.querySelector(sel);
      return img?.getAttribute('src') || '';
    }, selector);

    const ok = src.includes(expect) && (expect === 'logo.png' ? !src.includes('logo-dark') : true);
    if (ok) {
      pass++;
      console.log(`[PASS] ${url} → ${src}`);
    } else {
      fail++;
      console.log(`[FAIL] ${url} expected ${expect}, got ${src || 'missing'}`);
    }
  } catch (err) {
    fail++;
    console.log(`[FAIL] ${url} — ${err.message}`);
  } finally {
    await page.close();
  }
}

await browser.close();
console.log(`\n=== LOGO AUDIT: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);