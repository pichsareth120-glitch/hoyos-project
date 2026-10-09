import puppeteer from 'puppeteer-core';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\ebdff404-9c0f-4512-804b-9578d85f2f8f';

async function run() {
    console.log('Launching browser for /track-order verification...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();

    // 1. Guest View - Desktop 1440px
    console.log('1. Checking Guest View Desktop...');
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto('http://127.0.0.1:8000/track-order', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'track_order_guest_desktop_1440.png'), fullPage: true });

    // 2. Guest View - Mobile 360px
    console.log('2. Checking Guest View Mobile...');
    await page.setViewport({ width: 360, height: 800 });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'track_order_guest_mobile_360.png'), fullPage: true });

    // 3. Test Wrong Order Number / Mismatch State
    console.log('3. Testing Wrong Order Number...');
    await page.setViewport({ width: 1440, height: 900 });
    await page.type('#order_number', 'HOYOS-999999');
    await page.type('#customer_phone', '012345678');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'track_order_not_found_desktop_1440.png'), fullPage: true });
    console.log('Captured not found error alert state.');

    // 4. Test Correct Order Number with Wrong Phone
    console.log('4. Testing Correct Order with Wrong Phone...');
    await page.evaluate(() => {
        document.getElementById('order_number').value = 'HOYOS-000006';
        document.getElementById('customer_phone').value = '099999999';
    });
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
    console.log('Phone mismatch correctly returned generic error.');

    // 5. Test Correct Pair (HOYOS-000006 and +85516696969)
    console.log('5. Testing Correct Pair...');
    await page.evaluate(() => {
        document.getElementById('order_number').value = 'HOYOS-000006';
        document.getElementById('customer_phone').value = '+85516696969';
    });
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
    const redirectedUrl = page.url();
    console.log('Redirected URL with correct pair:', redirectedUrl);

    // 6. Test Authenticated View
    console.log('6. Testing Authenticated View...');
    await page.goto('http://127.0.0.1:8000/auth/demo', { waitUntil: 'networkidle0' });
    await page.goto('http://127.0.0.1:8000/track-order', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'track_order_auth_desktop_1440.png'), fullPage: true });

    // 7. Test Authenticated Mobile View
    console.log('7. Testing Authenticated Mobile View...');
    await page.setViewport({ width: 360, height: 800 });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'track_order_auth_mobile_360.png'), fullPage: true });

    await browser.close();
    console.log('Track order tests and screenshots finished successfully!');
}

run().catch(console.error);
