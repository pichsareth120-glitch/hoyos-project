import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\b1b86f45-6a94-47df-82f4-ee04e03630c2';

fs.mkdirSync(OUT_DIR, { recursive: true });

async function run() {
    console.log('Launching Chrome to verify invoices and receipt viewports...');
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

    // 1. Verify /admin/invoices at 1920x1080
    console.log('Capturing /admin/invoices at 1920x1080...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/invoices', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    const p1920 = path.join(OUT_DIR, 'invoices_1920.png');
    await page.screenshot({ path: p1920 });
    fs.copyFileSync(p1920, path.join(ARTIFACT_DIR, 'invoices_1920.png'));

    // 2. Verify /admin/invoices at 1280x800
    console.log('Capturing /admin/invoices at 1280x800...');
    await page.setViewport({ width: 1280, height: 800 });
    await new Promise(r => setTimeout(r, 600));
    const p1280 = path.join(OUT_DIR, 'invoices_1280.png');
    await page.screenshot({ path: p1280 });
    fs.copyFileSync(p1280, path.join(ARTIFACT_DIR, 'invoices_1280.png'));

    // 3. Verify /admin/invoices at 390x844
    console.log('Capturing /admin/invoices at 390x844...');
    await page.setViewport({ width: 390, height: 844 });
    await new Promise(r => setTimeout(r, 600));
    const p390 = path.join(OUT_DIR, 'invoices_390.png');
    await page.screenshot({ path: p390 });
    fs.copyFileSync(p390, path.join(ARTIFACT_DIR, 'invoices_390.png'));

    // 4. Verify /admin/invoices/1 at 1920x800 (Detail view with sticky action bar)
    console.log('Capturing /admin/invoices/1 at 1920x800...');
    await page.setViewport({ width: 1920, height: 800 });
    await page.goto('http://127.0.0.1:8000/admin/invoices/1', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    const pDetailTop = path.join(OUT_DIR, 'invoice_detail_1920_top.png');
    await page.screenshot({ path: pDetailTop });
    fs.copyFileSync(pDetailTop, path.join(ARTIFACT_DIR, 'invoice_detail_1920_top.png'));

    // Scroll down to observe sticky action bar behavior
    console.log('Scrolling down on /admin/invoices/1 to verify sticky action bar...');
    await page.evaluate(() => window.scrollBy(0, 350));
    await new Promise(r => setTimeout(r, 600));
    const pDetailSticky = path.join(OUT_DIR, 'invoice_detail_1920_sticky.png');
    await page.screenshot({ path: pDetailSticky });
    fs.copyFileSync(pDetailSticky, path.join(ARTIFACT_DIR, 'invoice_detail_1920_sticky.png'));

    // 5. Verify /admin/invoices/1 at 1280x800
    console.log('Capturing /admin/invoices/1 at 1280x800...');
    await page.setViewport({ width: 1280, height: 800 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise(r => setTimeout(r, 600));
    const pDetail1280 = path.join(OUT_DIR, 'invoice_detail_1280.png');
    await page.screenshot({ path: pDetail1280 });
    fs.copyFileSync(pDetail1280, path.join(ARTIFACT_DIR, 'invoice_detail_1280.png'));

    // 6. Verify /admin/invoices/1 at 390x844
    console.log('Capturing /admin/invoices/1 at 390x844...');
    await page.setViewport({ width: 390, height: 844 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise(r => setTimeout(r, 600));
    const pDetail390 = path.join(OUT_DIR, 'invoice_detail_390.png');
    await page.screenshot({ path: pDetail390 });
    fs.copyFileSync(pDetail390, path.join(ARTIFACT_DIR, 'invoice_detail_390.png'));

    // 7. Check Print Preview of Receipt (@media print emulation)
    console.log('Emulating print media on /admin/invoices/1 for print preview check...');
    await page.setViewport({ width: 1200, height: 1600 });
    await page.emulateMediaType('print');
    await new Promise(r => setTimeout(r, 600));
    const pPrintPreview = path.join(OUT_DIR, 'invoice_receipt_print_preview.png');
    await page.screenshot({ path: pPrintPreview, fullPage: true });
    fs.copyFileSync(pPrintPreview, path.join(ARTIFACT_DIR, 'invoice_receipt_print_preview.png'));

    await browser.close();
    console.log('All invoice viewport and print screenshots captured successfully!');
}

run().catch(err => {
    console.error('Error during invoice viewports capture:', err);
    process.exit(1);
});



