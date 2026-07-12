/**
 * Admin panel mobile polish verify @ 375px
 * node tests/verify_admin_mobile.mjs
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

async function loginAsAdmin(page) {
  await page.goto(`${BASE}/login.php?type=trader&t=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
  await page.waitForFunction(() => document.getElementById('loginForm')?.classList.contains('visible'), { timeout: 15000 });
  await page.fill('#mobile', '0500000001');
  await page.fill('#password', '123456');
  await page.click('button[type="submit"]');
  await page.waitForURL((u) => !u.pathname.includes('login.php'), { timeout: 60000 });
}

async function checkAdminPage(page, path, label) {
  await page.goto(`${BASE}/admin/${path}?t=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
  const data = await page.evaluate(() => ({
    build: document.querySelector('meta[name="fx-build"]')?.content || '',
    profile: !!document.querySelector('.fx-dash-mobile-profile--admin'),
    nav: !!document.querySelector('.fx-admin-mobile-nav select'),
    menuBtn: !!document.getElementById('sidebar-toggle-mobile'),
    overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 2,
    tableWrap: !!document.querySelector('.admin-table-wrapper'),
    sidebar: !!document.getElementById('admin-sidebar'),
    overlay: !!document.getElementById('admin-sidebar-overlay'),
  }));
  report(data.build === '104', `${label} build 104`, data.build || 'missing');
  report(data.profile, `${label} mobile profile strip`);
  report(data.nav, `${label} mobile nav select`);
  report(data.menuBtn, `${label} mobile menu button`);
  report(!data.overflow, `${label} no horizontal overflow`);
  report(data.sidebar && data.overlay, `${label} sidebar + overlay present`);
  if (path === 'index.php' || path === 'users.php' || path === 'activity.php') {
    report(data.tableWrap, `${label} table scroll wrapper`);
  }
  return data;
}

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({
  viewport: { width: 375, height: 812 },
  locale: 'ar-SA',
  ...devices['iPhone 13'],
});
const page = await ctx.newPage();

try {
  await loginAsAdmin(page);

  await checkAdminPage(page, 'index.php', 'Dashboard');
  await page.screenshot({ path: `${OUT}/build104-admin-dashboard-375.png` });
  console.log(`[SHOT] ${OUT}/build104-admin-dashboard-375.png`);

  await checkAdminPage(page, 'users.php', 'Users');
  await checkAdminPage(page, 'settings.php', 'Settings');

  await page.goto(`${BASE}/admin/index.php?t=${Date.now()}`, { waitUntil: 'load' });
  await page.click('#sidebar-toggle-mobile');
  const navOpen = await page.evaluate(() => ({
    open: document.getElementById('admin-sidebar')?.classList.contains('open'),
    expanded: document.getElementById('sidebar-toggle-mobile')?.getAttribute('aria-expanded') === 'true',
    bodyLock: document.body.classList.contains('admin-nav-open'),
  }));
  report(navOpen.open && navOpen.expanded && navOpen.bodyLock, 'Sidebar opens from mobile menu btn');
  await page.keyboard.press('Escape');
  const navClosed = await page.evaluate(() => !document.getElementById('admin-sidebar')?.classList.contains('open'));
  report(navClosed, 'Sidebar closes on Escape');

  await page.selectOption('.fx-admin-mobile-nav select', 'auctions.php');
  await page.waitForURL(/admin\/auctions\.php/, { timeout: 15000 });
  report(page.url().includes('auctions.php'), 'Mobile nav select navigates');
} catch (e) {
  report(false, 'Run', e.message);
} finally {
  await browser.close();
}

console.log(`\n=== Admin Mobile Polish: ${pass} passed, ${fail} failed ===`);
process.exit(fail > 0 ? 1 : 0);