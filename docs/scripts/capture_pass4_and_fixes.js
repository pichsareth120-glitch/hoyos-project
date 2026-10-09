import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\08e809ed-7781-46b6-8eb6-3c56dd50e780';

fs.mkdirSync(OUT_DIR, { recursive: true });
fs.mkdirSync(ARTIFACT_DIR, { recursive: true });

function saveShot(page, filename) {
    const src = path.join(OUT_DIR, filename);
    const dest = path.join(ARTIFACT_DIR, filename);
    return page.screenshot({ path: src, fullPage: false }).then(() => {
        fs.copyFileSync(src, dest);
        console.log(`Saved screenshot: ${filename}`);
    });
}

async function run() {
    console.log('Launching browser for Pass 4 and Fixes verification...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();

    // Login as admin
    console.log('Logging in as admin...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@hoyos.com');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);

    // -------------------------------------------------------------
    // FIX 3e: Customer detail page with suspend modal ACTUALLY open
    // -------------------------------------------------------------
    console.log('Capturing customer detail with suspend modal ACTUALLY open...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/customers/2', { waitUntil: 'networkidle0' });
    
    // Click the Suspend button to open the modal
    await page.waitForSelector('#open-suspend-modal-btn', { visible: true });
    await page.click('#open-suspend-modal-btn');
    
    // Wait for the modal container to be visible
    await page.waitForSelector('div[x-show="suspendModalOpen"]', { visible: true });
    await new Promise(r => setTimeout(r, 400));
    await saveShot(page, 'customer_suspend_modal_open_1920.png');

    // -------------------------------------------------------------
    // FIX 3e: State after a failed suspend submission (< 5 chars reason)
    // -------------------------------------------------------------
    console.log('Submitting failed suspend (< 5 chars reason) to capture validation state...');
    await page.evaluate(() => {
        const textarea = document.querySelector('textarea#reason');
        if (textarea) {
            textarea.removeAttribute('minlength');
            textarea.removeAttribute('required');
            textarea.value = 'bad';
        }
    });
    // Click confirm submit inside the modal
    const submitBtn = await page.$('div[x-show="suspendModalOpen"] form button[type="submit"]');
    if (submitBtn) {
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            submitBtn.click()
        ]);
    }
    await new Promise(r => setTimeout(r, 400));
    await saveShot(page, 'customer_suspend_failed_error_1920.png');

    // -------------------------------------------------------------
    // PASS 4: AUDIT LOGS (/admin/audit-logs)
    // -------------------------------------------------------------
    // 1. Audit Logs Index 1920px
    console.log('Capturing Audit Logs index at 1920px...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/audit-logs', { waitUntil: 'networkidle0' });
    await saveShot(page, 'audit_logs_index_1920.png');

    // 2. Audit Logs Index with Filters at 1280px
    console.log('Capturing Audit Logs index with filters at 1280px...');
    await page.setViewport({ width: 1280, height: 900 });
    await page.goto('http://127.0.0.1:8000/admin/audit-logs?module=shipments&action=update_status', { waitUntil: 'networkidle0' });
    await saveShot(page, 'audit_logs_index_1280_filters.png');

    // 3. Audit Logs Index Mobile 2-column stat grid at 390px
    console.log('Capturing Audit Logs index at 390px mobile...');
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    await page.goto('http://127.0.0.1:8000/admin/audit-logs', { waitUntil: 'networkidle0' });
    await saveShot(page, 'audit_logs_index_390_mobile.png');

    // 4. Audit Logs Empty State (e.g. target_id=999999)
    console.log('Capturing Audit Logs empty state...');
    await page.setViewport({ width: 1280, height: 900 });
    await page.goto('http://127.0.0.1:8000/admin/audit-logs?target_id=999999', { waitUntil: 'networkidle0' });
    await saveShot(page, 'audit_logs_empty_state_1280.png');

    // 5. Audit Logs Detail / Diff at 1920px
    console.log('Capturing Audit Logs detail diff at 1920px...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/audit-logs/1', { waitUntil: 'networkidle0' });
    await saveShot(page, 'audit_logs_detail_1920.png');

    // 6. Audit Logs Detail / Diff at 390px mobile (scrollable diff)
    console.log('Capturing Audit Logs detail diff at 390px mobile...');
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    await page.goto('http://127.0.0.1:8000/admin/audit-logs/1', { waitUntil: 'networkidle0' });
    await saveShot(page, 'audit_logs_detail_390_mobile.png');

    // -------------------------------------------------------------
    // Customers index DataTable header & cleaned-up pill re-check
    // -------------------------------------------------------------
    console.log('Re-capturing Customers index at 1920px to confirm aligned headers & no stray plus...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/customers', { waitUntil: 'networkidle0' });
    await saveShot(page, 'customers_index_1920_fixed.png');

    await browser.close();
    console.log('All verification captures completed successfully!');
}

run().catch(err => {
    console.error('Error running capture script:', err);
    process.exit(1);
});
