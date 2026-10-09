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
    console.log('Launching Chrome to capture requested screenshots...');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080, deviceScaleFactor: 1 });

    // 1. Login
    console.log('Logging in as admin@hoyos.com...');
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@hoyos.com');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);

    // 2. Find a rich product with variants and images
    console.log('Finding a product with variants and images...');
    await page.goto('http://127.0.0.1:8000/admin/products', { waitUntil: 'networkidle0' });
    const productLinks = await page.$$eval('a[href*="/edit"]', els => els.map(e => e.href));
    const targetEditUrl = productLinks[0] || 'http://127.0.0.1:8000/admin/products/9/edit';
    console.log(`Using product edit page: ${targetEditUrl}`);

    // Navigate to Edit page
    await page.goto(targetEditUrl, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));

    // Scroll to the Variants & Sizes table and Images Gallery
    console.log('Scrolling to Images gallery and Variants table on Edit page...');
    await page.evaluate(() => {
        const variantsCard = Array.from(document.querySelectorAll('h3, span, div')).find(el => el.innerText && el.innerText.includes('Variants & Sizing'));
        if (variantsCard) {
            variantsCard.scrollIntoView({ behavior: 'instant', block: 'start' });
            window.scrollBy(0, -100); // offset for topbar and sticky action bar
        } else {
            window.scrollTo(0, 950);
        }
    });
    await new Promise(r => setTimeout(r, 500));
    await saveShot(page, 'edit_product_variants_and_images_1920.png');

    // Also open the Edit SKU modal on the Edit page to show SKU modal in action
    console.log('Opening Edit SKU modal...');
    await page.evaluate(() => {
        const editBtn = Array.from(document.querySelectorAll('button')).find(b => b.innerText && b.innerText.includes('Edit SKU'));
        if (editBtn) editBtn.click();
    });
    await new Promise(r => setTimeout(r, 500));
    await saveShot(page, 'edit_product_sku_modal_open_1920.png');

    // Close modal
    await page.evaluate(() => {
        const closeBtn = document.querySelector('[x-show="editVariantModalOpen"] button[aria-label="Close dialog"]') ||
                         Array.from(document.querySelectorAll('button')).find(b => b.innerText === 'Cancel');
        if (closeBtn) closeBtn.click();
    });
    await new Promise(r => setTimeout(r, 300));

    // 3. Create page after failed submission, scrolled so sticky bar sits over a card
    console.log('Navigating to Create page for failed submission and scrolled sticky bar test...');
    await page.goto('http://127.0.0.1:8000/admin/products/create', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 400));

    // Fill invalid data and submit
    await page.evaluate(() => {
        const form = document.getElementById('product-form');
        if (form) form.noValidate = true;
        const nameInput = document.getElementById('field-name-input');
        if (nameInput) nameInput.value = '';
        const priceInput = document.getElementById('field-price-input');
        if (priceInput) priceInput.value = '65';
        const compareInput = document.getElementById('field-compare-at-price-input');
        if (compareInput) compareInput.value = '50'; // invalid: compare_at_price <= price
        if (nameInput) nameInput.dispatchEvent(new Event('input', { bubbles: true }));
        if (priceInput) priceInput.dispatchEvent(new Event('input', { bubbles: true }));
        if (compareInput) compareInput.dispatchEvent(new Event('input', { bubbles: true }));
    });

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[form="product-form"]')
    ]);
    await new Promise(r => setTimeout(r, 600));

    // Scroll down so the sticky bar sits visibly over a card (around 420px)
    console.log('Scrolling so sticky ActionBar sits over a glass card...');
    await page.evaluate(() => window.scrollTo(0, 480));
    await new Promise(r => setTimeout(r, 500));
    await saveShot(page, 'create_product_failed_sticky_over_card_1920.png');

    // 4. Inventory at 1920px
    console.log('Navigating to /admin/inventory at 1920px...');
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.goto('http://127.0.0.1:8000/admin/inventory', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    await saveShot(page, 'inventory_index_1920.png');

    // 5. Inventory Adjust / Ledger view
    console.log('Navigating to /admin/inventory/21 (variant with ledger history)...');
    await page.goto('http://127.0.0.1:8000/admin/inventory/21', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    await saveShot(page, 'inventory_adjust_ledger_21_1920.png');

    await browser.close();
    console.log('All requested screenshots captured successfully!');
}

run().catch(err => {
    console.error('Capture error:', err);
    process.exit(1);
});
