/**
 * Verify §2 auctions v1 tabs + cards @ 375px — Build 120
 */
import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
let pass = 0;
let fail = 0;

function report(ok, label, detail = '') {
  console.log(`${ok ? '[PASS]' : '[FAIL]'} ${label}${detail ? ` — ${detail}` : ''}`);
  ok ? pass++ : fail++;
}

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({
  viewport: { width: 375, height: 812 },
  ...devices['iPhone 13'],
  extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
});
const page = await ctx.newPage();

try {
  await page.goto(`${BASE}/?t=${Date.now()}`, { waitUntil: 'load', timeout: 60000 });
  await page.waitForSelector('.fx-auctions-panel', { timeout: 20000 });

  const s2 = await page.evaluate(() => {
    const panel = document.querySelector('.fx-auctions-panel');
    const toggle = document.querySelector('.fx-auction-type-toggle');
    const tabs = document.querySelectorAll('.fx-auctions-panel__tabs .auctions-tab-btn');
    const card = document.querySelector('.fx-home-auctions .auction-card');
    const titleOnImg = card?.querySelector('.ac-title--on-image');
    const titleInBody = card?.querySelector('.ac-body .ac-title');
    const timerSide = card?.querySelector('.ac-timer-side .ac-timer-box--v2');
    const timerBoard = card?.querySelector('.ac-timer-board');
    const swiper = document.querySelector('.live-auctions-swiper');
    return {
      build: document.querySelector('meta[name="fx-build"]')?.content || '',
      panelVisible: !!(panel && getComputedStyle(panel).display !== 'none'),
      toggleHidden: !toggle || getComputedStyle(toggle).display === 'none',
      tabCount: tabs.length,
      tabText: [...tabs].map((t) => t.textContent.replace(/\s+/g, ' ').trim()),
      marqueeSwiper: swiper?.classList.contains('fx-auctions-swiper--marquee'),
      titleInBody: !!titleInBody && !titleOnImg,
      timerSide: !!timerSide && !timerBoard,
      cardTitle: titleInBody?.textContent?.trim() || '',
    };
  });

  report(s2.build === '120', 'Build 120', s2.build);
  report(s2.panelVisible, 'Auctions panel visible');
  report(s2.toggleHidden, 'Toggle switch hidden');
  report(s2.tabCount === 2, 'Two word tabs only', String(s2.tabCount));
  report(s2.tabText.some((t) => t.includes('المزادات الحية')), 'Live tab label', s2.tabText.join(' | '));
  report(s2.tabText.some((t) => t.includes('الشراء الفوري')), 'Instant tab label');
  report(s2.marqueeSwiper, 'Marquee swiper class');
  report(s2.titleInBody, 'Title in card body not on image', s2.cardTitle);
  report(s2.timerSide, 'Timer side v2 box not board');

  await page.click('.fx-auctions-panel__tabs .auctions-tab-btn:nth-child(2)');
  await page.waitForTimeout(600);

  const instant = await page.evaluate(() => ({
    instantActive: document.getElementById('tab-content-instant')?.classList.contains('active'),
    instantSwiper: document.querySelector('.instant-buy-swiper')?.classList.contains('fx-auctions-swiper--marquee'),
  }));

  report(instant.instantActive, 'Instant tab activates content');
  report(instant.instantSwiper, 'Instant marquee swiper');

  console.log(`\n${pass} passed, ${fail} failed`);
  process.exit(fail > 0 ? 1 : 0);
} finally {
  await browser.close();
}