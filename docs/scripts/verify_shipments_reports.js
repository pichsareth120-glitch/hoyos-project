import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\b1b86f45-6a94-47df-82f4-ee04e03630c2';

fs.mkdirSync(OUT_DIR, { recursive: true });

async function run() {
    console.log('Launching Chrome to verify reports and shipments viewports...');
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

    // 1. Verify /admin/reports/products
    console.log('Checking /admin/reports/products...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/reports/products', { waitUntil: 'networkidle0' });
    const prodTitle = await page.title();
    const prodBreadcrumb = await page.$eval('nav[aria-label="Breadcrumb"] h2', el => el.innerText.trim());
    console.log(`Products Page Title: "${prodTitle}"`);
    console.log(`Products Breadcrumb: "${prodBreadcrumb}"`);

    const pProd = path.join(OUT_DIR, 'reports_products_1920.png');
    await page.screenshot({ path: pProd });
    fs.copyFileSync(pProd, path.join(ARTIFACT_DIR, 'reports_products_1920.png'));

    // 2. Verify /admin/reports/customers
    console.log('Checking /admin/reports/customers...');
    await page.goto('http://127.0.0.1:8000/admin/reports/customers', { waitUntil: 'networkidle0' });
    const custTitle = await page.title();
    const custBreadcrumb = await page.$eval('nav[aria-label="Breadcrumb"] h2', el => el.innerText.trim());
    console.log(`Customers Page Title: "${custTitle}"`);
    console.log(`Customers Breadcrumb: "${custBreadcrumb}"`);

    // Check filter buttons computed font
    const filterBtnFont = await page.$$eval('div.flex.items-center.gap-2 a', els => 
        els.map(el => ({ text: el.innerText.trim(), fontFamily: window.getComputedStyle(el).fontFamily }))
    );
    console.log('Filter buttons font check:', JSON.stringify(filterBtnFont, null, 2));

    const pCust = path.join(OUT_DIR, 'reports_customers_1920.png');
    await page.screenshot({ path: pCust });
    fs.copyFileSync(pCust, path.join(ARTIFACT_DIR, 'reports_customers_1920.png'));

    // 3. Verify /admin/shipments at 1920px
    console.log('Checking /admin/shipments at 1920x1080...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/shipments', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));

    const shipTitle = await page.title();
    const shipBreadcrumb = await page.$eval('nav[aria-label="Breadcrumb"] h2', el => el.innerText.trim());
    console.log(`Shipments Page Title: "${shipTitle}"`);
    console.log(`Shipments Breadcrumb: "${shipBreadcrumb}"`);

    const pShip1920 = path.join(OUT_DIR, 'shipments_1920.png');
    await page.screenshot({ path: pShip1920 });
    fs.copyFileSync(pShip1920, path.join(ARTIFACT_DIR, 'shipments_1920.png'));

    // Check stat cards height at 1280px
    console.log('Checking /admin/shipments at 1280x800...');
    await page.setViewport({ width: 1280, height: 800 });
    await new Promise(r => setTimeout(r, 600));

    const cardHeights = await page.$$eval('.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 > div', els => 
        els.map(el => el.getBoundingClientRect().height)
    );
    console.log('Stat card heights at 1280px:', cardHeights);

    const pShip1280 = path.join(OUT_DIR, 'shipments_1280.png');
    await page.screenshot({ path: pShip1280 });
    fs.copyFileSync(pShip1280, path.join(ARTIFACT_DIR, 'shipments_1280.png'));

    // Check at 390px (mobile)
    console.log('Checking /admin/shipments at 390x844...');
    await page.setViewport({ width: 390, height: 844 });
    await new Promise(r => setTimeout(r, 600));

    const pShip390 = path.join(OUT_DIR, 'shipments_390.png');
    await page.screenshot({ path: pShip390 });
    fs.copyFileSync(pShip390, path.join(ARTIFACT_DIR, 'shipments_390.png'));

    console.log('All captures and validations complete!');
    await browser.close();
}

run().catch(err => {
    console.error('Error during verification:', err);
    process.exit(1);
});
