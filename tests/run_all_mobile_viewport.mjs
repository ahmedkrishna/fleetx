import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.dirname(fileURLToPath(import.meta.url));
const baseUrl = process.env.FLEETX_BASE || 'https://mazadi.bearand.com';

const SUITES = [
  { name: 'Vehicle + Auction Live Viewports', cmd: 'node', args: ['verify_vehicle_live_viewports.mjs'] },
  { name: 'Mobile Dashboards + Auctions', cmd: 'node', args: ['verify_mobile_dashboards_auctions.mjs'], cooldownMs: 2000 },
  { name: 'Homepage White Hero @ 375px', cmd: 'node', args: ['verify_homepage_hero_mobile.mjs'] },
  { name: 'Sub-page Dark Heroes @ 375px', cmd: 'node', args: ['verify_subpage_hero_mobile.mjs'] },
  { name: 'Sub-page Navbars @ 375px', cmd: 'node', args: ['verify_subpage_navbar_mobile.mjs'] },
  { name: 'Dashboards Live (PHP)', cmd: 'php', args: ['verify_dashboards_live.php'], cooldownMs: 5000, dashCooldown: true },
];

function runSuite(suite) {
  return new Promise((resolve) => {
    const started = Date.now();
    const env = {
      ...process.env,
      FLEETX_BASE: baseUrl,
      ...(suite.dashCooldown ? { FX_DASH_COOLDOWN: '1' } : {}),
    };
    console.log(`\n${'='.repeat(72)}\n▶ ${suite.name}\n${'='.repeat(72)}\n`);
    // Node: direct spawn (avoids Windows shell arg injection). PHP: shell on win32 (PATH shim).
    const useShell = process.platform === 'win32' && suite.cmd === 'php';
    const child = spawn(suite.cmd, suite.args, {
      cwd: root,
      stdio: 'inherit',
      shell: useShell,
      env,
    });
    child.on('error', (err) => {
      console.error(`[ERROR] ${suite.name}: ${err.message}`);
      resolve({
        name: suite.name,
        ok: false,
        code: 1,
        ms: Date.now() - started,
      });
    });
    child.on('close', (code) => {
      resolve({
        name: suite.name,
        ok: code === 0,
        code: code ?? 1,
        ms: Date.now() - started,
      });
    });
  });
}

console.log('FleetX — All Viewport & Mobile Tests');
console.log(`Base: ${baseUrl}`);

const results = [];
for (const suite of SUITES) {
  if (suite.cooldownMs) {
    console.log(`\n(cooldown ${suite.cooldownMs / 1000}s before ${suite.name} — live host recovery)\n`);
    await new Promise((r) => setTimeout(r, suite.cooldownMs));
  }
  results.push(await runSuite(suite));
}

console.log(`\n${'='.repeat(72)}`);
console.log('SUMMARY');
console.log('='.repeat(72));
let failed = 0;
for (const r of results) {
  const status = r.ok ? 'PASS' : 'FAIL';
  if (!r.ok) failed++;
  console.log(`[${status}] ${r.name} (${(r.ms / 1000).toFixed(1)}s)`);
}
console.log(`\n=== ALL SUITES: ${results.length - failed}/${results.length} passed ===`);
process.exit(failed > 0 ? 1 : 0);