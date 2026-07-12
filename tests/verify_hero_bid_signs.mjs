import { chromium } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
const browser = await chromium.launch({ headless: true });
const page = await (await browser.newContext({
  viewport: { width: 1280, height: 800 },
  extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
})).newPage();

await page.goto(`${BASE}/?t=${Date.now()}`, { waitUntil: 'load', timeout: 60000 });
await page.waitForTimeout(2800);

const result = await page.evaluate(() => {
  const build = document.querySelector('meta[name="fx-build"]')?.content || '';
  const signs = [...document.querySelectorAll('.fx-bid-sign')].filter((s) => s.querySelector('.fx-bid-sign__board'));
  return {
    build,
    signs: signs.map((sign) => {
      const board = sign.querySelector('.fx-bid-sign__board');
      const stem = sign.querySelector('.fx-bid-sign__stem');
      const gavel = sign.querySelector('.fx-bid-sign__gavel');
      const br = board.getBoundingClientRect();
      const sr = stem.getBoundingClientRect();
      const isLeft = sign.classList.contains('fx-bid-sign--left');
      return {
        isLeft,
        hasGavel: !!gavel,
        children: [...sign.children].map((c) => c.className.split(' ')[0]),
        stemOutside: isLeft ? sr.left < br.left - 1 : sr.right > br.right + 1,
        signDir: getComputedStyle(sign).direction,
      };
    }),
  };
});

console.log(JSON.stringify(result, null, 2));

const ok =
  result.build === '125' &&
  result.signs.length > 0 &&
  result.signs.every(
    (s) => !s.hasGavel && s.stemOutside && !s.children.includes('fx-bid-sign__gavel'),
  );

console.log(ok ? 'PASS' : 'FAIL');
await browser.close();
process.exit(ok ? 0 : 1);