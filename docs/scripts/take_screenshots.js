import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';

fs.mkdirSync(OUT_DIR, { recursive: true });

async function run() {
    console.log('Launching Chrome for screenshot capture...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();

    // 1. Desktop captures (1400x950)
    await page.setViewport({ width: 1400, height: 950 });

    // Home desktop
    console.log('Navigating to http://127.0.0.1:8000/ (Desktop)...');
    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle0', timeout: 30000 });
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_home.png') });
    console.log('Saved screenshot_home.png');

    // Category desktop
    console.log('Navigating to http://127.0.0.1:8000/category (Desktop)...');
    await page.goto('http://127.0.0.1:8000/category', { waitUntil: 'networkidle0', timeout: 30000 });
    await page.evaluate(() => window.scrollBy(0, 250));
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_category.png') });
    console.log('Saved screenshot_category.png');

    // Product desktop
    console.log('Navigating to http://127.0.0.1:8000/product/boxy-cyber-jersey-tee (Desktop)...');
    await page.goto('http://127.0.0.1:8000/product/boxy-cyber-jersey-tee', { waitUntil: 'networkidle0', timeout: 30000 });
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_product.png') });
    console.log('Saved screenshot_product.png');

    // Credits desktop
    console.log('Navigating to http://127.0.0.1:8000/credits (Desktop)...');
    await page.goto('http://127.0.0.1:8000/credits', { waitUntil: 'networkidle0', timeout: 30000 });
    await page.evaluate(() => window.scrollBy(0, 300));
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_credits.png') });
    console.log('Saved screenshot_credits.png');

    // 2. Mobile captures (390x844)
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });

    // Home mobile
    console.log('Navigating to http://127.0.0.1:8000/ (Mobile 390px)...');
    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle0', timeout: 30000 });
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_home_mobile.png') });
    console.log('Saved screenshot_home_mobile.png');

    // Category mobile (390px)
    console.log('Navigating to http://127.0.0.1:8000/category (Mobile 390px)...');
    await page.goto('http://127.0.0.1:8000/category', { waitUntil: 'networkidle0', timeout: 30000 });
    await page.evaluate(() => window.scrollBy(0, 220));
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_category_mobile.png') });
    console.log('Saved screenshot_category_mobile.png');

    // Product mobile
    console.log('Navigating to http://127.0.0.1:8000/product/boxy-cyber-jersey-tee (Mobile 390px)...');
    await page.goto('http://127.0.0.1:8000/product/boxy-cyber-jersey-tee', { waitUntil: 'networkidle0', timeout: 30000 });
    await new Promise(r => setTimeout(r, 600));
    await page.screenshot({ path: path.join(OUT_DIR, 'screenshot_product_mobile.png') });
    console.log('Saved screenshot_product_mobile.png');

    await browser.close();
    console.log('All screenshots captured successfully!');
}

run().catch(err => {
    console.error('Error taking screenshots:', err);
    process.exit(1);
});
