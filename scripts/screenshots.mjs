#!/usr/bin/env node

/**
 * screenshots.mjs — generates print-ready PNG screenshots from the marketing_sites table.
 *
 * Usage:
 *   npm run screenshots -- [options]
 *
 * Options:
 *   --base-url=URL      Base URL of the running app  (default: https://instantsite.test)
 *   --out=DIR           Output directory              (default: ./screenshots)
 *   --concurrency=N     Parallel browser contexts     (default: 3)
 *   --force             Re-capture even if PNG exists
 *   --limit=N           Only process first N rows (for testing)
 *   --status=STATUS     marketing_sites status filter: pending|claimed|dismissed|all (default: all)
 *
 * Requires:
 *   npm install playwright
 *   npx playwright install chromium
 */

import { chromium } from 'playwright';
import { execSync } from 'child_process';
import { existsSync, mkdirSync, writeFileSync, readFileSync } from 'fs';
import { resolve, join } from 'path';

// ─── CLI argument parsing ─────────────────────────────────────────────────────

function arg(name, fallback) {
    const flag = `--${name}=`;
    const found = process.argv.find(a => a.startsWith(flag));
    return found ? found.slice(flag.length) : fallback;
}

function hasFlag(name) {
    return process.argv.includes(`--${name}`);
}

const BASE_URL    = arg('base-url', 'https://instantsite.test').replace(/\/$/, '');
const OUT_DIR     = resolve(arg('out', './screenshots'));
const CONCURRENCY = parseInt(arg('concurrency', '3'), 10);
const FORCE       = hasFlag('force');
const LIMIT       = arg('limit', null);
const STATUS      = arg('status', 'all');

// ─── Capture constants ────────────────────────────────────────────────────────

const VIEWPORT_WIDTH  = 1440;
const VIEWPORT_HEIGHT = 2200;   // full render height so lazy images load correctly
const CAPTURE_HEIGHT  = 920;    // crop — matches the browser-well in the postcard design
                                 // (hero photo + business name + location + action buttons)
                                 // output: 2880 × 1840 px at dpr:2
const DEVICE_SCALE    = 2;      // → 2880 px-wide output
const PAGE_TIMEOUT_MS = 30_000;
const SETTLE_DELAY_MS = 300;
const MAX_RETRIES     = 2;
const RETRY_DELAY_MS  = 2_000;

// CSS selectors for chrome that must be absent before capture
const CHROME_SELECTORS = [
    // top banner
    '.sticky.top-0.z-\\[9998\\]',
    // bottom claim footer
    '.fixed.bottom-0.left-0.right-0.z-\\[9999\\]',
    // floating palette picker
    '.fixed.right-4.z-\\[9990\\]',
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

function log(msg) {
    process.stdout.write(`${new Date().toISOString().slice(11, 19)} ${msg}\n`);
}

function err(msg) {
    process.stderr.write(`${new Date().toISOString().slice(11, 19)} ERROR ${msg}\n`);
}

/**
 * Run `php artisan marketing:places-json` and return the parsed array.
 */
function fetchPlacesFromDb() {
    const limitFlag  = LIMIT   ? ` --limit=${LIMIT}`   : '';
    const statusFlag = STATUS  ? ` --status=${STATUS}`  : '';
    const cmd = `php artisan marketing:places-json${statusFlag}${limitFlag}`;

    log(`Fetching places list: ${cmd}`);
    try {
        const stdout = execSync(cmd, { encoding: 'utf8', cwd: resolve(process.cwd()) });
        return JSON.parse(stdout);
    } catch (e) {
        throw new Error(`Failed to query marketing_sites via artisan: ${e.message}`);
    }
}

/**
 * Check that all chrome selectors are absent from the page.
 * Returns the first selector still found, or null if all gone.
 */
async function findChrome(page) {
    for (const sel of CHROME_SELECTORS) {
        try {
            const el = page.locator(sel).first();
            if (await el.isVisible({ timeout: 500 }).catch(() => false)) {
                return sel;
            }
        } catch { /* not found = good */ }
    }
    return null;
}

/**
 * Wait until the page is visually ready:
 * 1. networkidle
 * 2. document.fonts.ready
 * 3. All img elements complete with naturalWidth > 0
 * 4. No chrome selectors visible (banners/footers)
 * 5. Short settle delay
 */
async function waitForReady(page) {
    await page.waitForLoadState('networkidle', { timeout: PAGE_TIMEOUT_MS });

    await page.evaluate(() => document.fonts.ready);

    // Wait for all images to have loaded
    await page.waitForFunction(() => {
        const imgs = Array.from(document.querySelectorAll('img'));
        if (imgs.length === 0) return true;
        return imgs.every(img => img.complete && img.naturalWidth > 0);
    }, { timeout: PAGE_TIMEOUT_MS });

    // Verify no chrome is visible
    const remainingChrome = await findChrome(page);
    if (remainingChrome) {
        throw new Error(`Preview chrome still visible after load: ${remainingChrome}`);
    }

    // Final layout-shift settle
    await sleep(SETTLE_DELAY_MS);
}

/**
 * Detect error / not-found states.
 * Returns a string describing the problem, or null if page looks healthy.
 */
async function detectErrorState(page) {
    const title = await page.title();
    if (/404|not found|error/i.test(title)) return `Bad page title: "${title}"`;

    // Check for Laravel / Inertia error indicators
    const hasErrorText = await page.evaluate(() => {
        const body = document.body?.innerText ?? '';
        return /Whoops|404|Something went wrong/i.test(body.slice(0, 500));
    });
    if (hasErrorText) return 'Page appears to be an error page';

    return null;
}

/**
 * Capture a single page and save the PNG.
 * Returns { success, width, height, filePath, error }.
 */
async function capturePage(browser, placesId) {
    const url = `${BASE_URL}/claim/${encodeURIComponent(placesId)}?capture=1`;
    const outPath = join(OUT_DIR, `${placesId}.png`);

    for (let attempt = 1; attempt <= MAX_RETRIES + 1; attempt++) {
        const context = await browser.newContext({
            viewport: { width: VIEWPORT_WIDTH, height: VIEWPORT_HEIGHT },
            deviceScaleFactor: DEVICE_SCALE,
            colorScheme: 'light',
        });
        const page = await context.newPage();

        try {
            await page.goto(url, { timeout: PAGE_TIMEOUT_MS, waitUntil: 'domcontentloaded' });

            const errorState = await detectErrorState(page);
            if (errorState) {
                throw new Error(errorState);
            }

            await waitForReady(page);

            // Crop to CAPTURE_HEIGHT — just the hero section that fits the postcard well.
            // VIEWPORT_HEIGHT is kept tall so all lazy images load before we clip.
            const screenshot = await page.screenshot({
                type: 'png',
                clip: { x: 0, y: 0, width: VIEWPORT_WIDTH, height: CAPTURE_HEIGHT },
                animations: 'disabled',
            });

            writeFileSync(outPath, screenshot);

            return {
                success: true,
                width: VIEWPORT_WIDTH * DEVICE_SCALE,
                height: CAPTURE_HEIGHT * DEVICE_SCALE,
                filePath: outPath,
                url,
            };
        } catch (e) {
            await context.close();
            if (attempt <= MAX_RETRIES) {
                err(`[${placesId}] attempt ${attempt} failed: ${e.message} — retrying in ${RETRY_DELAY_MS}ms`);
                await sleep(RETRY_DELAY_MS * attempt);
            } else {
                return { success: false, error: e.message, url };
            }
            continue;
        } finally {
            await context.close().catch(() => {});
        }
    }
}

// ─── Concurrency pool ─────────────────────────────────────────────────────────

/**
 * Process an array of tasks with a fixed concurrency limit.
 * taskFn receives each item and must return a promise.
 */
async function pool(items, concurrency, taskFn) {
    const results = new Array(items.length);
    let index = 0;

    async function worker() {
        while (index < items.length) {
            const i = index++;
            results[i] = await taskFn(items[i], i);
        }
    }

    await Promise.all(Array.from({ length: concurrency }, worker));
    return results;
}

// ─── Main ─────────────────────────────────────────────────────────────────────

async function main() {
    // Ensure output directory exists
    mkdirSync(OUT_DIR, { recursive: true });

    // Load places from DB
    const places = fetchPlacesFromDb();
    if (places.length === 0) {
        log('No places found in marketing_sites. Nothing to do.');
        process.exit(0);
    }
    log(`Found ${places.length} place(s) to process`);

    // Determine which rows need capturing
    const toCapture = FORCE
        ? places
        : places.filter(p => !existsSync(join(OUT_DIR, `${p.places_id}.png`)));

    const skipped = places.length - toCapture.length;
    if (skipped > 0) log(`Skipping ${skipped} already-captured (use --force to re-capture)`);
    if (toCapture.length === 0) {
        log('All rows already captured. Done.');
        writeManifest(places, [], []);
        process.exit(0);
    }

    log(`Capturing ${toCapture.length} screenshot(s) with concurrency=${CONCURRENCY}`);

    const browser = await chromium.launch({ headless: true });

    const results = await pool(toCapture, CONCURRENCY, async (place, idx) => {
        const num = idx + 1;
        log(`[${num}/${toCapture.length}] ${place.places_id} — ${place.business_name ?? ''}`);
        const result = await capturePage(browser, place.places_id);
        if (result.success) {
            log(`  ✓ saved ${place.places_id}.png (${result.width}×${result.height})`);
        } else {
            err(`  ✗ FAILED ${place.places_id}: ${result.error}`);
        }
        return { places_id: place.places_id, ...result };
    });

    await browser.close();

    // ─── Manifest ───────────────────────────────────────────────────────────

    const captured = results.filter(r => r.success);
    const failed   = results.filter(r => !r.success);

    const manifestRows = places.map(p => {
        const wasSkipped = !toCapture.find(t => t.places_id === p.places_id);
        if (wasSkipped) {
            const filePath = join(OUT_DIR, `${p.places_id}.png`);
            return {
                places_id:  p.places_id,
                status:     'skipped',
                file:       filePath,
                captured_at: null,
            };
        }
        const r = results.find(r => r.places_id === p.places_id);
        return {
            places_id:   p.places_id,
            status:      r?.success ? 'captured' : 'failed',
            file:        r?.success ? r.filePath : null,
            width:       r?.success ? r.width  : null,
            height:      r?.success ? r.height : null,
            url:         r?.url ?? null,
            error:       r?.success ? null : r?.error,
            captured_at: r?.success ? new Date().toISOString() : null,
        };
    });

    const manifestPath = join(OUT_DIR, 'manifest.json');
    writeFileSync(manifestPath, JSON.stringify(manifestRows, null, 2));
    log(`Manifest written → ${manifestPath}`);

    // ─── Summary ────────────────────────────────────────────────────────────

    log('');
    log(`────────────────────────────────`);
    log(`Captured : ${captured.length}`);
    log(`Skipped  : ${skipped}`);
    log(`Failed   : ${failed.length}`);
    if (failed.length > 0) {
        log('Failed places_ids:');
        failed.forEach(f => log(`  • ${f.places_id}: ${f.error}`));
    }
    log(`────────────────────────────────`);

    process.exit(failed.length > 0 ? 1 : 0);
}

main().catch(e => {
    err(`Unhandled error: ${e.message}`);
    process.exit(1);
});
