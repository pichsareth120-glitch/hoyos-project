import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\b1b86f45-6a94-47df-82f4-ee04e03630c2';

fs.mkdirSync(OUT_DIR, { recursive: true });

async function run() {
    console.log('Launching Chrome to verify payments polish and products index...');
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

    // 1. Verify Polished /admin/payments
    console.log('Verifying /admin/payments (pagination in DataTable, neutral Settled At dates, Order # StatusBadge)...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/payments', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));

    // Check pagination is inside DataTable container
    const isPaginationInTable = await page.$eval('.glass.rounded-2xl .border-t .font-mono', el => !!el).catch(() => false);
    console.log('Is pagination inside DataTable container:', isPaginationInTable);

    // Check Order # status badge
    const orderStatusBadgeExists = await page.$eval('td:nth-child(2) span.rounded-full.font-mono', el => el.innerText.trim()).catch(() => 'None');
    console.log('First Order # StatusBadge text:', orderStatusBadgeExists);

    // Check Settled At date color
    const settledAtClasses = await page.$eval('td:nth-child(8) div:first-child', el => el.className).catch(() => 'None');
    console.log('Settled At date classes:', settledAtClasses);

    const paymentsPolishedShot = path.join(OUT_DIR, 'payments_polished_1920.png');
    await page.screenshot({ path: paymentsPolishedShot });
    fs.copyFileSync(paymentsPolishedShot, path.join(ARTIFACT_DIR, 'payments_polished_1920.png'));

    // 2. Verify /admin/products at 1920x1080
    console.log('Verifying /admin/products at 1920x1080...');
    await page.goto('http://127.0.0.1:8000/admin/products', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));

    const pProdTitle = await page.title();
    console.log(`Products Page Title: "${pProdTitle}"`);

    const prod1920 = path.join(OUT_DIR, 'products_1920.png');
    await page.screenshot({ path: prod1920 });
    fs.copyFileSync(prod1920, path.join(ARTIFACT_DIR, 'products_1920.png'));

    // 3. Verify /admin/products at 1280x800 (checking stat cards heights & filter wrapping)
    console.log('Verifying /admin/products at 1280x800...');
    await page.setViewport({ width: 1280, height: 800 });
    await new Promise(r => setTimeout(r, 600));

    const statHeights = await page.$$eval('.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 > a', els => 
        els.map(el => Math.round(el.getBoundingClientRect().height))
    );
    console.log('Products StatCard heights at 1280px:', statHeights);

    const prod1280 = path.join(OUT_DIR, 'products_1280.png');
    await page.screenshot({ path: prod1280 });
    fs.copyFileSync(prod1280, path.join(ARTIFACT_DIR, 'products_1280.png'));

    // 4. Verify /admin/products at 390x844 (Mobile)
    console.log('Verifying /admin/products at 390x844...');
    await page.setViewport({ width: 390, height: 844 });
    await new Promise(r => setTimeout(r, 600));

    const prod390 = path.join(OUT_DIR, 'products_390.png');
    await page.screenshot({ path: prod390 });
    fs.copyFileSync(prod390, path.join(ARTIFACT_DIR, 'products_390.png'));

    // 5. Test ConfirmModal on Archive button
    console.log('Testing Archive ConfirmModal...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/products', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));

    const archiveBtn = await page.$('button::-p-text(Archive)');
    if (archiveBtn) {
        console.log('Found Archive button, clicking to trigger ConfirmModal...');
        await archiveBtn.click();
        await new Promise(r => setTimeout(r, 400));

        const isArchiveModalOpen = await page.$eval('div[x-show="archiveModalOpen"]', el => {
            return window.getComputedStyle(el).display !== 'none';
        });
        console.log('Is Archive ConfirmModal Visible:', isArchiveModalOpen);

        const modalProdName = await page.$eval('div[x-show="archiveModalOpen"] strong[x-text="archiveProductName"]', el => el.innerText.trim());
        console.log('Product to archive in modal:', modalProdName);

        const prodModalShot = path.join(OUT_DIR, 'products_archive_modal.png');
        await page.screenshot({ path: prodModalShot });
        fs.copyFileSync(prodModalShot, path.join(ARTIFACT_DIR, 'products_archive_modal.png'));

        // Close modal by clicking cancel
        await page.click('div[x-show="archiveModalOpen"] button::-p-text(Cancel)');
        await new Promise(r => setTimeout(r, 300));
    }

    // 6. Test a failed form submission to verify server-side validation error states
    console.log('Testing a failed form submission (POST /admin/products with invalid data) to inspect validation states...');
    // We navigate to /admin/products/create and submit with empty required fields
    await page.goto('http://127.0.0.1:8000/admin/products/create', { waitUntil: 'networkidle0' });
    // Remove client side validation if any, and submit
    await page.evaluate(() => {
        const form = document.querySelector('form');
        if (form) {
            form.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
            form.submit();
        }
    });
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    const currentUrl = page.url();
    console.log('Current URL after submitting invalid form:', currentUrl);

    const validationErrShot = path.join(OUT_DIR, 'products_create_validation_error.png');
    await page.screenshot({ path: validationErrShot });
    fs.copyFileSync(validationErrShot, path.join(ARTIFACT_DIR, 'products_create_validation_error.png'));

    console.log('All verification steps completed successfully.');
    await browser.close();
}

run().catch(err => {
    console.error('Error during verification:', err);
    process.exit(1);
});
