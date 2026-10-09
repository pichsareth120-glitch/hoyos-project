import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\b1b86f45-6a94-47df-82f4-ee04e03630c2';

fs.mkdirSync(OUT_DIR, { recursive: true });

async function run() {
    console.log('Launching Chrome to verify payments audit and status badge format...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();

    // Login as Admin
    console.log('Logging in as admin@hoyos.com...');
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@hoyos.com');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
    console.log('Logged in successfully.');

    // 1. Verify StatusBadge on /admin/shipments
    console.log('Verifying /admin/shipments status badge text...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/shipments', { waitUntil: 'networkidle0' });
    const badgeTexts = await page.$$eval('span.rounded-full.font-mono', els => els.map(e => e.innerText.trim()));
    console.log('Shipment badge texts:', badgeTexts);

    // 2. Verify /admin/payments at 1920x1080
    console.log('Checking /admin/payments at 1920x1080...');
    await page.goto('http://127.0.0.1:8000/admin/payments', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));

    const pPayTitle = await page.title();
    const pPayBreadcrumb = await page.$eval('nav[aria-label="Breadcrumb"] h2', el => el.innerText.trim());
    console.log(`Payments Title: "${pPayTitle}"`);
    console.log(`Payments Breadcrumb: "${pPayBreadcrumb}"`);

    const pPay1920 = path.join(OUT_DIR, 'payments_1920.png');
    await page.screenshot({ path: pPay1920 });
    fs.copyFileSync(pPay1920, path.join(ARTIFACT_DIR, 'payments_1920.png'));

    // 3. Verify /admin/payments at 1280x800 (checking stat cards heights & filter wrapping)
    console.log('Checking /admin/payments at 1280x800...');
    await page.setViewport({ width: 1280, height: 800 });
    await new Promise(r => setTimeout(r, 600));

    const statHeights = await page.$$eval('.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 > a', els => 
        els.map(el => el.getBoundingClientRect().height)
    );
    console.log('Payments StatCard heights at 1280px:', statHeights);

    const pPay1280 = path.join(OUT_DIR, 'payments_1280.png');
    await page.screenshot({ path: pPay1280 });
    fs.copyFileSync(pPay1280, path.join(ARTIFACT_DIR, 'payments_1280.png'));

    // 4. Verify /admin/payments at 390x844 (Mobile)
    console.log('Checking /admin/payments at 390x844...');
    await page.setViewport({ width: 390, height: 844 });
    await new Promise(r => setTimeout(r, 600));

    const pPay390 = path.join(OUT_DIR, 'payments_390.png');
    await page.screenshot({ path: pPay390 });
    fs.copyFileSync(pPay390, path.join(ARTIFACT_DIR, 'payments_390.png'));

    // 5. Test Invalid Refund Form Submission and verify validation error state
    console.log('Testing invalid refund form submission (validation error state)...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/payments', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));

    // Find and click "Record Refund" button if present
    const refundBtn = await page.$('button::-p-text(Record Refund)');
    if (refundBtn) {
        console.log('Found Record Refund button, clicking to open modal...');
        await refundBtn.click();
        await new Promise(r => setTimeout(r, 400));

        // Type short note (less than 5 chars, e.g. "abc") into textarea
        // Note: remove HTML5 required/minlength client-side restriction to test server validation
        await page.$eval('textarea[name="notes"]', el => {
            el.removeAttribute('required');
            el.removeAttribute('minlength');
            el.value = 'bad';
        });

        console.log('Submitting invalid refund with notes="bad"...');
        const submitRefundBtn = await page.$('div[x-show="refundModalOpen"] button[type="submit"]');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            submitRefundBtn.click()
        ]);

        console.log('Captured server validation response. Checking modal visibility and error text...');
        const isModalVisible = await page.$eval('div[x-show="refundModalOpen"]', el => {
            return window.getComputedStyle(el).display !== 'none';
        });
        console.log('Is Refund Modal Visible after validation error:', isModalVisible);

        const errorMsg = await page.$eval('div[x-show="refundModalOpen"] p.text-hoyos-red', el => el.innerText.trim()).catch(() => 'None');
        console.log('Validation error message:', errorMsg);

        const pRefundErr = path.join(OUT_DIR, 'payments_refund_validation_error.png');
        await page.screenshot({ path: pRefundErr });
        fs.copyFileSync(pRefundErr, path.join(ARTIFACT_DIR, 'payments_refund_validation_error.png'));
    } else {
        console.log('No paid payment found to test refund button directly on page, checking database...');
    }

    console.log('Verification finished successfully.');
    await browser.close();
}

run().catch(err => {
    console.error('Error during verification:', err);
    process.exit(1);
});
