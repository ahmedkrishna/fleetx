import { chromium, devices } from 'playwright';

const BASE = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';
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

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1280, height: 900 },
  locale: 'ar-SA',
  extraHTTPHeaders: { 'Cache-Control': 'no-cache' },
});
const page = await context.newPage();

try {
  const res = await page.goto(`${BASE}/?v=${Date.now()}`, { waitUntil: 'load', timeout: 30000 });
  report(res?.ok(), 'homepage HTTP', String(res?.status()));

  await page.waitForSelector('.fx-hiw-section--dark', { timeout: 15000 });
  report(true, 'dark HIW section present');

  const structure = await page.evaluate(() => {
    const section = document.querySelector('.fx-hiw-section--dark');
    const buyers = document.getElementById('buyers-section');
    const sellers = document.getElementById('sellers-section');
    const pinBlocks = document.querySelectorAll('.hiw-pin-block').length;
    const classic = document.querySelector('.fx-hiw-section--classic');

    const buyerTabs = [...document.querySelectorAll('#buyer-tabs .b-tab')].map((t) => t.textContent.trim());
    const sellerTabs = [...document.querySelectorAll('#seller-tabs .b-tab')].map((t) => t.textContent.trim());

    const visibleBuyerSteps = [...document.querySelectorAll('[id^="buyer-step-"]')].filter(
      (el) => getComputedStyle(el).display !== 'none'
    ).length;
    const visibleSellerSteps = [...document.querySelectorAll('[id^="seller-step-"]')].filter(
      (el) => getComputedStyle(el).display !== 'none'
    ).length;

    const buyerBg = buyers ? getComputedStyle(buyers).backgroundColor : '';
    const panelBg = buyers?.querySelector('.hiw-panel')
      ? getComputedStyle(buyers.querySelector('.hiw-panel')).backgroundColor
      : '';
    const titleColor = buyers?.querySelector('.hiw-dark-title')
      ? getComputedStyle(buyers.querySelector('.hiw-dark-title')).color
      : '';

    const buyerStep1HasContent = !!buyers?.querySelector('#buyer-step-1 .hiw-tab-title')?.textContent?.trim();
    const buyerStep1HasImg = !!buyers?.querySelector('#buyer-step-1 .hiw-tab-img');

    return {
      hasSection: !!section,
      hasBuyers: !!buyers,
      hasSellers: !!sellers,
      pinBlocks,
      hasClassic: !!classic,
      buyerTabs,
      sellerTabs,
      visibleBuyerSteps,
      visibleSellerSteps,
      buyerBg,
      panelBg,
      titleColor,
      buyerStep1HasContent,
      buyerStep1HasImg,
      buyersSticky: buyers ? getComputedStyle(buyers).position === 'sticky' : false,
    };
  });

  report(structure.hasSection && structure.hasBuyers && structure.hasSellers, 'buyer + seller sticky blocks');
  report(structure.pinBlocks === 0 && !structure.hasClassic, 'no scroll-pin classic layout');
  report(structure.buyerTabs.join(',') === 'التسجيل,المحفظة,المزايدة', 'buyer tab labels', structure.buyerTabs.join(','));
  report(structure.sellerTabs.join(',') === 'الاعتماد,الاشتراك,الإدراج', 'seller tab labels', structure.sellerTabs.join(','));
  report(structure.visibleBuyerSteps === 1 && structure.visibleSellerSteps === 1, 'one visible step per section', JSON.stringify(structure));
  report(structure.buyerStep1HasContent && structure.buyerStep1HasImg, 'step 1 has title + image');
  report(structure.buyersSticky, 'buyers section is sticky');
  report(structure.titleColor === 'rgb(255, 255, 255)', 'white section title', structure.titleColor);

  await page.click('#buyer-tabs .b-tab:nth-child(2)');
  await page.waitForTimeout(300);

  const buyerStep2 = await page.evaluate(() => {
    const step2 = document.getElementById('buyer-step-2');
    const step1 = document.getElementById('buyer-step-1');
    const tab2 = document.querySelector('#buyer-tabs .b-tab:nth-child(2)');
    return {
      step2Visible: step2 ? getComputedStyle(step2).display !== 'none' : false,
      step1Hidden: step1 ? getComputedStyle(step1).display === 'none' : false,
      tab2Active: tab2?.classList.contains('active') ?? false,
      step2Title: step2?.querySelector('.hiw-tab-title')?.textContent?.trim() || '',
      step2HasImg: !!step2?.querySelector('.hiw-tab-img'),
    };
  });

  report(buyerStep2.step2Visible && buyerStep2.step1Hidden && buyerStep2.tab2Active, 'buyer tab 2 switches content');
  report(buyerStep2.step2Title.includes('المحفظة') && buyerStep2.step2HasImg, 'buyer step 2 content', buyerStep2.step2Title);

  await page.click('#seller-tabs .b-tab:nth-child(3)');
  await page.waitForTimeout(300);

  const sellerStep3 = await page.evaluate(() => {
    const step3 = document.getElementById('seller-step-3');
    return {
      visible: step3 ? getComputedStyle(step3).display !== 'none' : false,
      title: step3?.querySelector('.hiw-tab-title')?.textContent?.trim() || '',
      hasImg: !!step3?.querySelector('.hiw-tab-img'),
    };
  });

  report(sellerStep3.visible, 'seller tab 3 shows step 3');
  report(sellerStep3.title.includes('إدراج') && sellerStep3.hasImg, 'seller step 3 content', sellerStep3.title);
} catch (err) {
  report(false, 'unexpected error', err.message);
} finally {
  await browser.close();
}

console.log(`\nResult: ${pass} passed, ${fail} failed`);
process.exit(fail > 0 ? 1 : 0);