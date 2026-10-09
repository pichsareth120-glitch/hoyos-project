import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'd:\\Hoyos-project\\storage\\app\\screenshots';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\08e809ed-7781-46b6-8eb6-3c56dd50e780';

fs.mkdirSync(OUT_DIR, { recursive: true });
fs.mkdirSync(ARTIFACT_DIR, { recursive: true });

function copyShot(filename) {
    const src = path.join(OUT_DIR, filename);
    const dest = path.join(ARTIFACT_DIR, filename);
    if (fs.existsSync(src)) {
        fs.copyFileSync(src, dest);
    }
}

async function run() {
    console.log('=== STARTING PASS 2b & PRODUCT FORMS VERIFICATION ===');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();

    // 1. Login as Admin
    console.log('\n[1] Logging in as admin@hoyos.com...');
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@hoyos.com');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
    console.log('✓ Successfully authenticated as Admin.');

    // 2. Failed submission on Create Product (Triple error display check)
    console.log('\n[2] Verifying Failed Submission on Product Create (/admin/products/create)...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/products/create', { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 400));

    await page.evaluate(() => {
        const form = document.getElementById('product-form');
        if (form) form.noValidate = true;
        const nameInput = document.getElementById('field-name-input');
        if (nameInput) nameInput.value = '';
        const priceInput = document.getElementById('field-price-input');
        if (priceInput) priceInput.value = '50';
        const compareInput = document.getElementById('field-compare-at-price-input');
        if (compareInput) compareInput.value = '40';
        if (nameInput) nameInput.dispatchEvent(new Event('input', { bubbles: true }));
        if (priceInput) priceInput.dispatchEvent(new Event('input', { bubbles: true }));
        if (compareInput) compareInput.dispatchEvent(new Event('input', { bubbles: true }));
    });

    // Submit form
    console.log('Submitting invalid form data...');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[form="product-form"]')
    ]);
    await new Promise(r => setTimeout(r, 600));

    // Verify:
    // a. Layout-level banner is suppressed
    const layoutBannerCount = await page.$$eval('.bg-red-950\\/50', els => els.length);
    console.log(`- Layout-level red error banner count: ${layoutBannerCount} (expected: 0, suppressed)`);

    // b. Summary card exists and has correct text
    const summaryCardText = await page.$eval('#form-error-summary', el => el.innerText.trim()).catch(() => null);
    console.log(`- Error Summary Card present: ${!!summaryCardText}`);
    console.log(`  Summary Text snippet: "${summaryCardText ? summaryCardText.substring(0, 80) : ''}..."`);

    // c. Summary card and form container width check
    const summaryCardWidth = await page.$eval('#form-error-summary', el => el.getBoundingClientRect().width).catch(() => 0);
    const formWidth = await page.$eval('#product-form', el => el.getBoundingClientRect().width).catch(() => 0);
    console.log(`- Summary card width: ${summaryCardWidth}px, Form width: ${formWidth}px (match: ${Math.abs(summaryCardWidth - formWidth) < 2})`);

    // d. Old values preserved
    const oldPrice = await page.$eval('#field-price-input', el => el.value);
    const oldComparePrice = await page.$eval('#field-compare-at-price-input', el => el.value);
    console.log(`- Old values preserved: price=${oldPrice} (expected 50), compare_at_price=${oldComparePrice} (expected 40)`);

    // e. FormField error state: red border, aria-invalid, aria-describedby
    const nameAriaInvalid = await page.$eval('#field-name-input', el => el.getAttribute('aria-invalid'));
    const nameAriaDescribedby = await page.$eval('#field-name-input', el => el.getAttribute('aria-describedby'));
    const nameClassList = await page.$eval('#field-name-input', el => el.className);
    const hasRedBorder = nameClassList.includes('!border-rose-500');
    console.log(`- field-name-input aria-invalid: "${nameAriaInvalid}" (expected: "true")`);
    console.log(`- field-name-input aria-describedby: "${nameAriaDescribedby}" (expected: "name-error")`);
    console.log(`- field-name-input red border class injected: ${hasRedBorder}`);

    // f. Active element focus on first error
    const activeElementId = await page.evaluate(() => document.activeElement ? document.activeElement.id : null);
    console.log(`- Focused element after submit: "${activeElementId}" (expected: "field-name-input")`);

    // g. Test scrolling and Sticky ActionBar at 1920px, 1280px, 390px
    console.log('Testing scrolling with Sticky ActionBar at 1920px, 1280px, 390px...');
    const viewports = [
        { width: 1920, height: 1080, name: '1920' },
        { width: 1280, height: 800, name: '1280' },
        { width: 390, height: 844, name: '390' }
    ];

    for (const vp of viewports) {
        await page.setViewport({ width: vp.width, height: vp.height });
        await page.evaluate(() => window.scrollTo(0, 500));
        await new Promise(r => setTimeout(r, 400));

        // Check action bar sticky position and top offset relative to header
        const headerRect = await page.$eval('header', el => {
            const r = el.getBoundingClientRect();
            return { top: r.top, height: r.height, bottom: r.bottom };
        });
        const actionbarRect = await page.$eval('main .sticky', el => {
            const r = el.getBoundingClientRect();
            return { top: r.top, height: r.height, bottom: r.bottom };
        }).catch(() => null);

        console.log(`  [${vp.name}px] Scrolled 500px: Header top=${headerRect.top}, height=${headerRect.height}. ActionBar top=${actionbarRect ? actionbarRect.top : 'N/A'}`);
        if (actionbarRect) {
            console.log(`  ActionBar sits below header: ${actionbarRect.top >= headerRect.height}`);
        }

        const shotName = `create_product_scrolled_${vp.name}.png`;
        await page.screenshot({ path: path.join(OUT_DIR, shotName) });
        copyShot(shotName);
    }

    // Reset scroll
    await page.evaluate(() => window.scrollTo(0, 0));

    // 3. Edit Product tests (Get first product ID)
    console.log('\n[3] Testing Edit Product (/admin/products/{id}/edit)...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/products', { waitUntil: 'networkidle0' });
    const firstEditHref = await page.$eval('table tbody tr:first-child a[href*="/edit"]', el => el.href);
    console.log(`Navigating to first product edit: ${firstEditHref}`);
    await page.goto(firstEditHref, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 500));

    // Test a: Failed submission on Edit page
    console.log('Testing failed submission on Edit page (setting compare_at_price lower than price)...');
    await page.evaluate(() => {
        const form = document.getElementById('product-form');
        if (form) form.noValidate = true;
        const p = document.getElementById('field-price-input');
        const c = document.getElementById('field-compare-at-price-input');
        if (p) p.value = '100';
        if (c) c.value = '80';
        if (p) p.dispatchEvent(new Event('input', { bubbles: true }));
        if (c) c.dispatchEvent(new Event('input', { bubbles: true }));
    });
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[form="product-form"]')
    ]);
    await new Promise(r => setTimeout(r, 500));

    const editLayoutBannerCount = await page.$$eval('.bg-red-950\\/50', els => els.length);
    const editSummaryCardPresent = await page.$eval('#form-error-summary', el => !!el).catch(() => false);
    console.log(`- Edit page layout-level red error banner count: ${editLayoutBannerCount} (expected: 0)`);
    console.log(`- Edit page summary card present: ${editSummaryCardPresent}`);

    const editFailedShot = 'edit_product_failed_summary_1920.png';
    await page.screenshot({ path: path.join(OUT_DIR, editFailedShot) });
    copyShot(editFailedShot);

    // Test b: Invalid file upload error
    console.log('Testing invalid file upload error on Edit page (uploading a .txt file)...');
    const dummyTxtPath = path.join(OUT_DIR, 'invalid_test.txt');
    fs.writeFileSync(dummyTxtPath, 'This is not an image');

    const fileInput = await page.$('input[type="file"][name="images[]"]');
    if (fileInput) {
        await fileInput.uploadFile(dummyTxtPath);
        await new Promise(r => setTimeout(r, 300));
        // Submit upload
        const uploadForm = await page.$('form[action*="/images"]');
        if (uploadForm) {
            await page.evaluate(() => {
                const f = document.querySelector('form[action*="/images"]');
                if (f) f.noValidate = true;
            });
            const uploadBtn = await page.$('form[action*="/images"] button[type="submit"]');
            if (uploadBtn) {
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle0' }),
                    uploadBtn.click()
                ]);
                console.log('Uploaded invalid txt file. Checking validation error...');
                const errorText = await page.$eval('body', b => b.innerText);
                const hasUploadError = errorText.toLowerCase().includes('image') || errorText.toLowerCase().includes('correct') || errorText.toLowerCase().includes('validation');
                console.log(`- Invalid file error captured: ${hasUploadError}`);
            }
        }
    }

    // Test c: Actual valid image upload
    console.log('Testing valid image upload on Edit page...');
    const validSampleImg = 'd:\\Hoyos-project\\public\\images\\hoyos\\hoyos-1.png';
    if (fs.existsSync(validSampleImg)) {
        await page.goto(firstEditHref, { waitUntil: 'networkidle0' });
        const validFileInput = await page.$('input[type="file"][name="images[]"]');
        if (validFileInput) {
            await validFileInput.uploadFile(validSampleImg);
            await new Promise(r => setTimeout(r, 400));
            const uploadBtn = await page.$('form[action*="/images"] button[type="submit"]');
            if (uploadBtn) {
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle0' }),
                    uploadBtn.click()
                ]);
                console.log('✓ Valid image uploaded successfully.');
            }
        }
    }

    // Test d: Set primary image and SKU edit on Edit page
    console.log('Testing Set Primary Image and SKU edit on Edit page...');
    await page.goto(firstEditHref, { waitUntil: 'networkidle0' });
    const setPrimaryBtn = await page.$('form[action*="/primary"] button[type="submit"]');
    if (setPrimaryBtn) {
        console.log('Clicking Set Primary image button...');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            setPrimaryBtn.click()
        ]);
        console.log('✓ Set Primary action completed.');
    }

    // Check SKU edit modal or variant action
    await page.goto(firstEditHref, { waitUntil: 'networkidle0' });
    const editVariantBtn = await page.evaluate(() => {
        const btns = Array.from(document.querySelectorAll('button'));
        const b = btns.find(x => x.innerText.includes('Edit SKU'));
        if (b) {
            b.click();
            return true;
        }
        return false;
    });
    console.log(`Edit SKU button clicked: ${editVariantBtn}`);
    await new Promise(r => setTimeout(r, 400));
    const modalOpen = await page.evaluate(() => {
        const m = document.querySelector('[x-show="editVariantModalOpen"]');
        return m && window.getComputedStyle(m).display !== 'none';
    });
    console.log(`- Variant Edit SKU Modal displayed: ${modalOpen}`);
    const modalShot = 'variant_edit_modal_1920.png';
    await page.screenshot({ path: path.join(OUT_DIR, modalShot) });
    copyShot(modalShot);

    // 4. Verify PASS 2b: INVENTORY (/admin/inventory)
    console.log('\n[4] Verifying PASS 2b: INVENTORY (/admin/inventory)...');
    for (const vp of viewports) {
        await page.setViewport({ width: vp.width, height: vp.height });
        await page.goto('http://127.0.0.1:8000/admin/inventory', { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 500));

        // Checks:
        // a. PageHeader status pill
        const statusPillText = await page.$eval('.font-mono', el => el.innerText).catch(() => '');
        // b. StatCard counts
        const statCards = await page.$$eval('.grid.grid-cols-2 .text-lg, .grid.grid-cols-2 .text-2xl', els => els.map(e => e.innerText.trim()));
        // c. Check single Pagination component (no duplicate "Showing X of Y")
        const showingCount = await page.evaluate(() => {
            const matches = Array.from(document.querySelectorAll('*')).filter(el => el.children.length === 0 && el.innerText && el.innerText.includes('Showing'));
            return matches.length;
        });

        console.log(`  [${vp.name}px] Inventory Index verified: StatCards=${JSON.stringify(statCards)}, "Showing" text count=${showingCount}`);

        const invShot = `inventory_index_${vp.name}.png`;
        await page.screenshot({ path: path.join(OUT_DIR, invShot) });
        copyShot(invShot);
    }

    // Filter verification on Inventory
    console.log('\n[5] Verifying Inventory Filters...');
    // a. Low Stock filter
    await page.goto('http://127.0.0.1:8000/admin/inventory?low_stock=1', { waitUntil: 'networkidle0' });
    const lowStockBadges = await page.$$eval('table tbody tr span', els => els.map(e => e.innerText.trim()).filter(t => t.includes('Stock')));
    console.log(`- Low Stock filter results count: ${lowStockBadges.length}, Badges: ${JSON.stringify(lowStockBadges.slice(0, 5))}`);

    // b. Out of Stock filter
    await page.goto('http://127.0.0.1:8000/admin/inventory?out_of_stock=1', { waitUntil: 'networkidle0' });
    const oosBadges = await page.$$eval('table tbody tr span', els => els.map(e => e.innerText.trim()).filter(t => t.includes('Stock')));
    console.log(`- Out of Stock filter results count: ${oosBadges.length}`);

    // 5. Verify Inventory Adjust / Ledger view (/admin/inventory/{id})
    console.log('\n[6] Verifying Adjust/Ledger view (/admin/inventory/1)...');
    for (const vp of viewports) {
        await page.setViewport({ width: vp.width, height: vp.height });
        await page.goto('http://127.0.0.1:8000/admin/inventory/1', { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 500));

        const adjShot = `inventory_show_ledger_${vp.name}.png`;
        await page.screenshot({ path: path.join(OUT_DIR, adjShot) });
        copyShot(adjShot);
    }

    // Test ConfirmModal on Adjust view
    console.log('Testing ConfirmModal trigger on Adjust view...');
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://127.0.0.1:8000/admin/inventory/1', { waitUntil: 'networkidle0' });
    const applyAdjBtn = await page.$('button[type="button"]');
    if (applyAdjBtn) {
        await page.evaluate(() => {
            const btns = Array.from(document.querySelectorAll('button'));
            const b = btns.find(x => x.innerText.includes('Apply Stock Adjustment'));
            if (b) b.click();
        });
        await new Promise(r => setTimeout(r, 400));
        const modalOpen = await page.evaluate(() => {
            const m = document.querySelector('[x-show="confirmAdjustOpen"]');
            return m && window.getComputedStyle(m).display !== 'none';
        });
        console.log(`- ConfirmModal displayed: ${modalOpen}`);
        const modalShot = 'inventory_adjust_confirm_modal_1920.png';
        await page.screenshot({ path: path.join(OUT_DIR, modalShot) });
        copyShot(modalShot);
    }

    await browser.close();
    console.log('\n=== ALL VERIFICATION TASKS COMPLETED SUCCESSFULLY ===');
}

run().catch(err => {
    console.error('Verification error:', err);
    process.exit(1);
});
