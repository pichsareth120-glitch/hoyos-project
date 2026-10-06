import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';

fs.mkdirSync(OUT_DIR, { recursive: true });

async function run() {
    console.log('Launching Chrome for comprehensive regression check...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();

    // Helper for adding an item to the bag first
    console.log('Adding item to bag for checkout and cart drawer tests...');
    await page.goto('http://127.0.0.1:8000/product/boxy-cyber-jersey-tee', { waitUntil: 'networkidle0' });
    // Wait for add-to-bag button and click it
    await page.waitForSelector('#add-to-bag-button', { timeout: 10000 });
    await page.click('#add-to-bag-button');
    await new Promise(r => setTimeout(r, 1200));

    // 1. Desktop Screenshots (1400x950)
    await page.setViewport({ width: 1400, height: 950 });

    // Login
    console.log('Capturing Desktop: /login');
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_login_desktop.png') });

    // Register
    console.log('Capturing Desktop: /register');
    await page.goto('http://127.0.0.1:8000/register', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_register_desktop.png') });

    // About
    console.log('Capturing Desktop: /about');
    await page.goto('http://127.0.0.1:8000/about', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_about_desktop.png') });

    // Track Order
    console.log('Capturing Desktop: /track-order');
    await page.goto('http://127.0.0.1:8000/track-order', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_track_order_desktop.png') });

    // Cart Drawer Open (Desktop)
    console.log('Capturing Desktop: Cart Drawer Open');
    await page.goto('http://127.0.0.1:8000/category', { waitUntil: 'networkidle0' });
    await page.evaluate(() => {
        const btn = document.getElementById('open-cart-btn') || document.querySelector('[data-cart-button]');
        if (btn) btn.click();
        else window.dispatchEvent(new CustomEvent('open-cart'));
    });
    await new Promise(r => setTimeout(r, 800));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_cart_drawer_desktop.png') });

    // Checkout with item in bag (Desktop)
    console.log('Capturing Desktop: /checkout');
    await page.goto('http://127.0.0.1:8000/checkout', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_checkout_desktop.png') });

    // Order Confirmation (Desktop)
    console.log('Capturing Desktop: Order Confirmation');
    await page.goto('http://127.0.0.1:8000/orders/HOYOS-000123?token=sample-guest-token-hoyos-000123', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_order_confirm_desktop.png') });

    // 2. Mobile Screenshots (390x844)
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });

    // Mobile Hero with mask check
    console.log('Capturing Mobile: Hero at 390px');
    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_hero_mobile_390.png') });

    // Mobile Login
    console.log('Capturing Mobile: /login');
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_login_mobile.png') });

    // Mobile Register
    console.log('Capturing Mobile: /register');
    await page.goto('http://127.0.0.1:8000/register', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_register_mobile.png') });

    // Mobile About
    console.log('Capturing Mobile: /about');
    await page.goto('http://127.0.0.1:8000/about', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_about_mobile.png') });

    // Mobile Track Order
    console.log('Capturing Mobile: /track-order');
    await page.goto('http://127.0.0.1:8000/track-order', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_track_order_mobile.png') });

    // Mobile Cart Drawer Open
    console.log('Capturing Mobile: Cart Drawer Open');
    await page.goto('http://127.0.0.1:8000/category', { waitUntil: 'networkidle0' });
    await page.evaluate(() => {
        const btn = document.getElementById('open-cart-btn') || document.querySelector('[data-cart-button]');
        if (btn) btn.click();
        else window.dispatchEvent(new CustomEvent('open-cart'));
    });
    await new Promise(r => setTimeout(r, 800));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_cart_drawer_mobile.png') });

    // Mobile Checkout
    console.log('Capturing Mobile: /checkout');
    await page.goto('http://127.0.0.1:8000/checkout', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_checkout_mobile.png') });

    // Mobile Order Confirmation
    console.log('Capturing Mobile: Order Confirmation');
    await page.goto('http://127.0.0.1:8000/orders/HOYOS-000123?token=sample-guest-token-hoyos-000123', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_order_confirm_mobile.png') });

    await browser.close();
    console.log('All regression screenshots successfully taken!');
}

run().catch(err => {
    console.error('Error during screenshot capture:', err);
    process.exit(1);
});
