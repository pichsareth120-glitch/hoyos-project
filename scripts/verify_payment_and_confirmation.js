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

    // 1. Log in via demo auth
    console.log('Logging in as demo customer...');
    await page.goto('http://127.0.0.1:8000/auth/demo', { waitUntil: 'networkidle0' });

    // 2. Add product to bag if cart is empty
    console.log('Checking cart and adding item if needed...');
    await page.goto('http://127.0.0.1:8000/product/midnight-matrix-silhouette', { waitUntil: 'networkidle0' });
    try {
        const addToCartBtn = await page.$('button[type="submit"]');
        if (addToCartBtn) {
            await addToCartBtn.click();
            await new Promise(r => setTimeout(r, 1500));
        }
    } catch (e) {
        console.log('Error adding to cart:', e.message);
    }

    // 3. Checkout with ABA KHQR
    console.log('Proceeding to checkout...');
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto('http://127.0.0.1:8000/checkout', { waitUntil: 'networkidle0' });

    // Click step 2 button if available
    const nextBtn = await page.$('button[type="button"]');
    // Let's submit the checkout form directly
    const form = await page.$('form[action*="checkout"]');
    if (form) {
        console.log('Submitting checkout form for ABA KHQR...');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            form.evaluate(f => f.submit())
        ]);
    }

    const currentUrl = page.url();
    console.log('Current URL after checkout:', currentUrl);

    // If on /payment/HOYOS-XXXXXX
    if (currentUrl.includes('/payment/')) {
        console.log('On payment page. Taking pending screenshots...');
        await page.setViewport({ width: 1440, height: 900 });
        await new Promise(r => setTimeout(r, 1000));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'payment_pending_desktop_1440.png'), fullPage: true });

        // Mobile viewport
        console.log('Taking mobile pending screenshot...');
        await page.setViewport({ width: 360, height: 800 });
        await new Promise(r => setTimeout(r, 500));
        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'payment_pending_mobile_360.png'), fullPage: true });

        // Back to desktop for simulation
        await page.setViewport({ width: 1440, height: 900 });
        await new Promise(r => setTimeout(r, 500));

        console.log('Clicking simulate approval...');
        const simBtn = await page.$('button[type="button"][class*="amber"]');
        if (simBtn) {
            await simBtn.click();
            // Wait for confirmed state inside card
            await new Promise(r => setTimeout(r, 1000));
            await page.screenshot({ path: path.join(ARTIFACT_DIR, 'payment_confirmed_card_1440.png'), fullPage: false });
            console.log('Captured confirmed card state.');

            // Wait for redirect to order receipt
            console.log('Waiting for redirect...');
            await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 }).catch(() => {});
        }
    }

    const receiptUrl = page.url();
    console.log('URL for receipt/confirmation:', receiptUrl);

    // 4. Capture Order Confirmation / Receipt Page (Paid ABA KHQR)
    await page.setViewport({ width: 1440, height: 900 });
    await new Promise(r => setTimeout(r, 1000));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_desktop_1440.png'), fullPage: true });

    // Mobile Order Confirmation
    await page.setViewport({ width: 360, height: 800 });
    await new Promise(r => setTimeout(r, 500));
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_mobile_360.png'), fullPage: true });

    // Test print media
    console.log('Testing print styles...');
    await page.emulateMediaType('print');
    await page.setViewport({ width: 1200, height: 1600 });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_print_preview.png'), fullPage: true });
    await page.emulateMediaType('screen');

    // 5. Check COD Order Confirmation page (/orders/HOYOS-000005)
    console.log('Checking COD order confirmation at /orders/HOYOS-000005...');
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto('http://127.0.0.1:8000/orders/HOYOS-000005', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(ARTIFACT_DIR, 'order_confirmation_cod_desktop_1440.png'), fullPage: true });

    await browser.close();
    console.log('All screenshots captured successfully!');
}

run().catch(console.error);
