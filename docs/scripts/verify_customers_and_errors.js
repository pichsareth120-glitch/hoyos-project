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
    return page.screenshot({ path: src }).then(() => {
        fs.copyFileSync(src, dest);
        console.log(`Saved screenshot: ${filename}`);
    });
}

async function run() {
    console.log('Launching browser for customers and error verification...');
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

    // --- CUSTOMERS INDEX ---
    // 1920px
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/customers', { waitUntil: 'networkidle0' });
    await saveShot(page, 'customers_index_1920.png');

    // 1280px (filters)
    await page.setViewport({ width: 1280, height: 900 });
    await page.goto('http://127.0.0.1:8000/admin/customers?search=Cinnamon&status=active', { waitUntil: 'networkidle0' });
    await saveShot(page, 'customers_index_1280_filters.png');

    // 390px (mobile 2-col stat grid)
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    await page.goto('http://127.0.0.1:8000/admin/customers', { waitUntil: 'networkidle0' });
    await saveShot(page, 'customers_index_390_mobile.png');

    // --- CUSTOMER DETAIL (Customer ID 2) ---
    // 1920px
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/customers/2', { waitUntil: 'networkidle0' });
    await saveShot(page, 'customer_detail_1920.png');

    // Open Suspend ConfirmModal (Danger variant)
    await page.evaluate(() => {
        // Trigger Alpine suspendModalOpen
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && window.Alpine) {
            window.Alpine.$data(alpineEl).suspendModalOpen = true;
        }
    });
    await new Promise(r => setTimeout(r, 400));
    await saveShot(page, 'customer_suspend_modal_open_1920.png');

    // 1280px detail
    await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && window.Alpine) {
            window.Alpine.$data(alpineEl).suspendModalOpen = false;
        }
    });
    await page.setViewport({ width: 1280, height: 900 });
    await page.goto('http://127.0.0.1:8000/admin/customers/2', { waitUntil: 'networkidle0' });
    await saveShot(page, 'customer_detail_1280.png');

    // 390px detail
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    await page.goto('http://127.0.0.1:8000/admin/customers/2', { waitUntil: 'networkidle0' });
    await saveShot(page, 'customer_detail_390_mobile.png');

    // --- ERROR STATES REQUESTED BY USER ---

    // 1. Failed Customer Suspend submission (reason < 5 chars, e.g. "bad")
    console.log('Testing failed Customer Suspend (short reason)...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/customers/2', { waitUntil: 'networkidle0' });
    // Open modal, fill reason with "abc", bypass client minlength to test server validation
    await page.evaluate(() => {
        const alpineEl = document.querySelector('[x-data]');
        if (alpineEl && window.Alpine) {
            window.Alpine.$data(alpineEl).suspendModalOpen = true;
        }
    });
    await new Promise(r => setTimeout(r, 300));
    await page.evaluate(() => {
        const form = document.querySelector('form[action*="/suspend"]');
        if (form) {
            const txt = form.querySelector('textarea[name="reason"]');
            if (txt) {
                txt.removeAttribute('minlength');
                txt.removeAttribute('required');
                txt.value = 'abc';
            }
            form.submit();
        }
    });
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    await saveShot(page, 'customer_suspend_failed_error_1920.png');

    // 2. Failed submission on Edit Product page
    console.log('Testing failed Edit Product submission...');
    await page.goto('http://127.0.0.1:8000/admin/products/1/edit', { waitUntil: 'networkidle0' });
    await page.evaluate(() => {
        const form = document.getElementById('product-form');
        if (form) {
            const nameInput = form.querySelector('input[name="name"]');
            const priceInput = form.querySelector('input[name="price"]');
            if (nameInput) {
                nameInput.removeAttribute('required');
                nameInput.value = '';
            }
            if (priceInput) {
                priceInput.removeAttribute('required');
                priceInput.value = '-5.00';
            }
            form.submit();
        }
    });
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    await saveShot(page, 'edit_product_failed_submission_errors_1920.png');

    // 3. Failed Inventory adjustment: reason < 5 chars and negative stock result
    console.log('Testing failed Inventory adjustment (reason < 5 chars)...');
    await page.goto('http://127.0.0.1:8000/admin/inventory/21', { waitUntil: 'networkidle0' });
    await page.evaluate(() => {
        const form = document.querySelector('form[action*="/adjust"]');
        if (form) {
            const reasonArea = form.querySelector('textarea[name="reason"]');
            if (reasonArea) {
                reasonArea.removeAttribute('minlength');
                reasonArea.removeAttribute('required');
                reasonArea.value = 'abc';
            }
            form.submit();
        }
    });
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    await saveShot(page, 'inventory_adjust_failed_short_reason_1920.png');

    console.log('Testing failed Inventory adjustment (negative stock result)...');
    await page.goto('http://127.0.0.1:8000/admin/inventory/21', { waitUntil: 'networkidle0' });
    await page.evaluate(() => {
        const form = document.querySelector('form[action*="/adjust"]');
        if (form) {
            const typeSelect = form.querySelector('select[name="type"]');
            const qtyInput = form.querySelector('input[name="quantity"]');
            const reasonArea = form.querySelector('textarea[name="reason"]');
            if (typeSelect) typeSelect.value = 'manual_shrinkage';
            if (qtyInput) qtyInput.value = '9999';
            if (reasonArea) reasonArea.value = 'Attempting deduction exceeding variant stock';
            form.submit();
        }
    });
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    await saveShot(page, 'inventory_adjust_failed_negative_stock_1920.png');

    await browser.close();
    console.log('All verification captures completed successfully.');
}

run().catch(err => {
    console.error('Error running capture script:', err);
    process.exit(1);
});
