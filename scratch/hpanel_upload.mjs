import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';

const EMAIL = 'ahmedkrishna11@gmail.com';
const PASS = '*A7medfouad*';
const ROOT = path.resolve('E:/Design/bearand/Fleetx');
const FILES = ['hotfix.php', 'sanad.php', 'nafath.php', 'register.php'];

const out = [];
function log(msg) { console.log(msg); out.push(msg); }

(async () => {
  const browser = await chromium.launch({ headless: true, channel: 'chrome' });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    log('1. Opening Hostinger login...');
    await page.goto('https://auth.hostinger.com/login', { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForTimeout(8000);
    await page.screenshot({ path: path.join(ROOT, 'scratch/hpanel_step1.png') });
    log(`   Title: ${await page.title()}`);

    // Fill login form
    const emailSel = 'input[type="email"], input[name="email"], input[name="username"], #email';
    const passSel = 'input[type="password"], input[name="password"], #password';
    await page.waitForSelector(emailSel, { timeout: 90000 });
    await page.fill(emailSel, EMAIL);
    await page.fill(passSel, PASS);

    const submit = page.locator('button[type="submit"], button:has-text("Log in"), button:has-text("Login")').first();
    await submit.click();
    await page.waitForTimeout(8000);
    log(`2. After login URL: ${page.url()}`);

    // Navigate to file manager for mazadi.bearand.com
    const fmUrls = [
      'https://hpanel.hostinger.com/websites/mazadi.bearand.com/files/file-manager',
      'https://hpanel.hostinger.com/hosting/mazadi.bearand.com/files/file-manager',
    ];
    let fmOk = false;
    for (const url of fmUrls) {
      try {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.waitForTimeout(5000);
        log(`3. File manager try: ${url} => ${page.url()}`);
        if (!page.url().includes('login')) { fmOk = true; break; }
      } catch (e) { log(`   fail: ${e.message}`); }
    }

    // Try FTP accounts page
    log('4. Looking for FTP credentials...');
    const ftpUrls = [
      'https://hpanel.hostinger.com/websites/mazadi.bearand.com/files/ftp-accounts',
      'https://hpanel.hostinger.com/hosting/mazadi.bearand.com/files/ftp-accounts',
      'https://hpanel.hostinger.com/websites/mazadi.bearand.com/advanced/ftp-accounts',
    ];
    for (const url of ftpUrls) {
      try {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.waitForTimeout(4000);
        const text = await page.innerText('body');
        if (text.match(/ftp\.|FTP|u274391035/i)) {
          log(`FTP page: ${url}`);
          const lines = text.split('\n').filter(l => /ftp|u274391035|password|host|username/i.test(l)).slice(0, 20);
          lines.forEach(l => log(`  ${l.trim()}`));
        }
      } catch (e) { /* skip */ }
    }

    // Upload via file manager if iframe/upload found
    if (fmOk) {
      log('5. Attempting file upload...');
      for (const f of FILES) {
        const local = path.join(ROOT, f);
        if (!fs.existsSync(local)) { log(`   SKIP missing ${f}`); continue; }
        try {
          const input = page.locator('input[type="file"]').first();
          if (await input.count()) {
            await input.setInputFiles(local);
            await page.waitForTimeout(3000);
            log(`   Uploaded ${f} via file input`);
          } else {
            log(`   No file input found for ${f}`);
          }
        } catch (e) { log(`   Upload fail ${f}: ${e.message}`); }
      }
    }

    // Screenshot for debug
    await page.screenshot({ path: path.join(ROOT, 'scratch/hpanel_screenshot.png'), fullPage: true });
    log('6. Screenshot saved');

  } catch (err) {
    log(`ERROR: ${err.message}`);
    try {
      await page.screenshot({ path: path.join(ROOT, 'scratch/hpanel_error.png') });
    } catch (_) {}
  } finally {
    await browser.close();
    fs.writeFileSync(path.join(ROOT, 'scratch/hpanel_upload_log.txt'), out.join('\n'));
  }
})();