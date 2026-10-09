import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\b1b86f45-6a94-47df-82f4-ee04e03630c2';

fs.mkdirSync(OUT_DIR, { recursive: true });

async function run() {
    console.log('Launching Chrome to verify viewports...');
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

    // 1. Verify /admin/orders at 1920px
    console.log('Capturing /admin/orders at 1920x1080...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/orders', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    const p1920 = path.join(OUT_DIR, 'orders_1920.png');
    await page.screenshot({ path: p1920 });
    fs.copyFileSync(p1920, path.join(ARTIFACT_DIR, 'orders_1920.png'));

    // 2. Verify /admin/orders at 1280px
    console.log('Capturing /admin/orders at 1280x800...');
    await page.setViewport({ width: 1280, height: 800 });
    await new Promise(r => setTimeout(r, 600));
    const p1280 = path.join(OUT_DIR, 'orders_1280.png');
    await page.screenshot({ path: p1280 });
    fs.copyFileSync(p1280, path.join(ARTIFACT_DIR, 'orders_1280.png'));

    // 3. Verify /admin/orders at 390px
    console.log('Capturing /admin/orders at 390x844...');
    await page.setViewport({ width: 390, height: 844 });
    await new Promise(r => setTimeout(r, 600));
    const p390 = path.join(OUT_DIR, 'orders_390.png');
    await page.screenshot({ path: p390 });
    fs.copyFileSync(p390, path.join(ARTIFACT_DIR, 'orders_390.png'));

    // 4. Verify /admin/orders/2 at 1920x800 (Confirmed order with Move to Processing & Cancel Order buttons)
    console.log('Capturing /admin/orders/2 at 1920x800...');
    await page.setViewport({ width: 1920, height: 800 });
    await page.goto('http://127.0.0.1:8000/admin/orders/2', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    const pDetail2Top = path.join(OUT_DIR, 'order_detail_2_top.png');
    await page.screenshot({ path: pDetail2Top });
    fs.copyFileSync(pDetail2Top, path.join(ARTIFACT_DIR, 'order_detail_2_top.png'));

    // Scroll down to observe sticky action bar with buttons
    console.log('Scrolling down on /admin/orders/2 to verify sticky action bar...');
    await page.evaluate(() => window.scrollBy(0, 400));
    await new Promise(r => setTimeout(r, 600));
    const pDetail2Sticky = path.join(OUT_DIR, 'order_detail_2_sticky.png');
    await page.screenshot({ path: pDetail2Sticky });
    fs.copyFileSync(pDetail2Sticky, path.join(ARTIFACT_DIR, 'order_detail_2_sticky.png'));

    // 5. Verify /admin/orders/1 at 1280px
    console.log('Capturing /admin/orders/1 at 1280x800...');
    await page.setViewport({ width: 1280, height: 800 });
    await new Promise(r => setTimeout(r, 600));
    const pDetail1280 = path.join(OUT_DIR, 'order_detail_1280.png');
    await page.screenshot({ path: pDetail1280 });
    fs.copyFileSync(pDetail1280, path.join(ARTIFACT_DIR, 'order_detail_1280.png'));

    // 6. Verify /admin/orders/1 at 390px
    console.log('Capturing /admin/orders/1 at 390x844...');
    await page.setViewport({ width: 390, height: 844 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise(r => setTimeout(r, 600));
    const pDetail390 = path.join(OUT_DIR, 'order_detail_390.png');
    await page.screenshot({ path: pDetail390 });
    fs.copyFileSync(pDetail390, path.join(ARTIFACT_DIR, 'order_detail_390.png'));

    await browser.close();
    console.log('All viewport screenshots captured successfully!');
}

run().catch(err => {
    console.error('Error during viewport capture:', err);
    process.exit(1);
});
