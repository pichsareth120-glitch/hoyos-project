import puppeteer from 'puppeteer-core';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\ebdff404-9c0f-4512-804b-9578d85f2f8f';

async function run() {
    console.log('Launching browser...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();

    // 1. Log in
    await page.goto('http://127.0.0.1:8000/auth/demo', { waitUntil: 'networkidle0' });

    // 2. Go to /payment/HOYOS-000014
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto('http://127.0.0.1:8000/payment/HOYOS-000014', { waitUntil: 'networkidle0' });

    console.log('Clicking simulateApproval button...');
    const simBtn = await page.$('button[type="button"][\\@click*="simulateApproval"], button[\\@click*="simulateApproval"]');
    if (simBtn) {
        await simBtn.click();
        console.log('Clicked simulation button, waiting 1.2s for payment verified state...');
        await new Promise(r => setTimeout(r, 1200));

        // Capture confirmed card state
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'payment_confirmed_card_1440.png'), fullPage: false });
        console.log('Captured payment_confirmed_card_1440.png');

        // Wait for redirect to order receipt
        console.log('Waiting for redirect to order receipt...');
        await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 5000 }).catch(() => {});
    }

    const currentUrl = page.url();
    console.log('Current URL:', currentUrl);

    // If redirected to /orders/HOYOS-000014/success or /orders/HOYOS-000014
    if (!currentUrl.includes('/orders/')) {
        await page.goto('http://127.0.0.1:8000/orders/HOYOS-000014', { waitUntil: 'networkidle0' });
    }

    // 3. Capture Order Confirmation Desktop
    console.log('Capturing order confirmation desktop...');
    await page.setViewport({ width: 1440, height: 900 });
    await new Promise(r => setTimeout(r, 1000));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_desktop_1440.png'), fullPage: true });

    // 4. Capture Order Confirmation Mobile
    console.log('Capturing order confirmation mobile...');
    await page.setViewport({ width: 360, height: 800 });
    await new Promise(r => setTimeout(r, 500));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_mobile_360.png'), fullPage: true });

    // 5. Capture Print Stylesheet Preview
    console.log('Capturing print preview...');
    await page.emulateMediaType('print');
    await page.setViewport({ width: 1200, height: 1600 });
    await new Promise(r => setTimeout(r, 500));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_print_preview.png'), fullPage: true });
    await page.emulateMediaType('screen');

    // 6. Capture COD Order Confirmation Desktop (/orders/HOYOS-000005)
    console.log('Capturing COD order confirmation...');
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto('http://127.0.0.1:8000/orders/HOYOS-000005', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1000));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_cod_desktop_1440.png'), fullPage: true });

    await browser.close();
    console.log('Completed successfully!');
}

run().catch(console.error);
