import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://127.0.0.1:8000';
const ADMIN_HOME = `${BASE_URL}/admin`;
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\08e809ed-7781-46b6-8eb6-3c56dd50e780';

const pages = [
    { name: 'Dashboard', url: `${ADMIN_HOME}` },
    { name: 'Audit Logs Index', url: `${ADMIN_HOME}/audit-logs` },
    { name: 'Audit Log Detail', url: `${ADMIN_HOME}/audit-logs/1` },
    { name: 'Customers Index', url: `${ADMIN_HOME}/customers` },
    { name: 'Customer Detail', url: `${ADMIN_HOME}/customers/2` },
    { name: 'Inventory Index', url: `${ADMIN_HOME}/inventory` },
    { name: 'Inventory Show', url: `${ADMIN_HOME}/inventory/2` },
    { name: 'Invoices Index', url: `${ADMIN_HOME}/invoices` },
    { name: 'Invoice Show', url: `${ADMIN_HOME}/invoices/1` },
    { name: 'Orders Index', url: `${ADMIN_HOME}/orders` },
    { name: 'Order Detail', url: `${ADMIN_HOME}/orders/1` },
    { name: 'Payments Index', url: `${ADMIN_HOME}/payments` },
    { name: 'Products Index', url: `${ADMIN_HOME}/products` },
    { name: 'Product Create', url: `${ADMIN_HOME}/products/create` },
    { name: 'Product Edit', url: `${ADMIN_HOME}/products/1/edit` },
    { name: 'Reports Index', url: `${ADMIN_HOME}/reports` },
    { name: 'Reports Sales', url: `${ADMIN_HOME}/reports/sales` },
    { name: 'Reports Products', url: `${ADMIN_HOME}/reports/products` },
    { name: 'Reports Customers', url: `${ADMIN_HOME}/reports/customers` },
    { name: 'Reports Regions', url: `${ADMIN_HOME}/reports/regions` },
    { name: 'Shipments Index', url: `${ADMIN_HOME}/shipments` }
];

async function run() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });

    // Login
    await page.goto(`${ADMIN_HOME}/customers`, { waitUntil: 'networkidle0' });
    if (page.url().includes('/login')) {
        await page.type('input[name="email"]', 'admin@hoyos.com');
        await page.type('input[name="password"]', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
    }

    const mode = process.argv[2] || 'before';
    console.log(`=== MEASURING BACKDROP-FILTER COUNTS (${mode.toUpperCase()}) ===\n`);

    const results = [];

    for (const p of pages) {
        await page.goto(p.url, { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 600));

        const info = await page.evaluate(() => {
            const all = Array.from(document.querySelectorAll('*'));
            const blurred = [];

            for (const el of all) {
                const cs = window.getComputedStyle(el);
                const bf = cs.backdropFilter || cs.webkitBackdropFilter || 'none';
                if (bf !== 'none' && !bf.includes('blur(0px)')) {
                    // Check if element is rendered and has size or is fixed
                    const rect = el.getBoundingClientRect();
                    blurred.push({
                        tag: el.tagName,
                        className: (el.className || '').toString().substring(0, 50),
                        filter: bf,
                        w: Math.round(rect.width),
                        h: Math.round(rect.height),
                        parentHasBlur: Boolean(el.parentElement?.closest('[style*="backdrop-filter"], .glass, .glass-card, .glass-strong, .glass-subtle, [class*="backdrop-blur"]'))
                    });
                }
            }

            return {
                count: blurred.length,
                elements: blurred
            };
        });

        results.push({ name: p.name, url: p.url, count: info.count, elements: info.elements });
        console.log(`[${p.name}]: ${info.count} blurred elements`);

        // Screenshots for 4 key pages
        if (mode === 'before' || mode === 'after') {
            const keyPages = {
                'Dashboard': 'dashboard',
                'Orders Index': 'orders',
                'Products Index': 'products',
                'Audit Logs Index': 'audit_logs'
            };

            if (keyPages[p.name]) {
                const filename = path.join(ARTIFACT_DIR, `${keyPages[p.name]}_${mode}_blur.png`);
                await page.screenshot({ path: filename, fullPage: false });
                console.log(`   Captured screenshot: ${filename}`);
            }
        }
    }

    fs.writeFileSync(`scripts/backdrop_counts_${mode}.json`, JSON.stringify(results, null, 2));
    console.log(`\nSaved results to scripts/backdrop_counts_${mode}.json`);

    await browser.close();
}

run().catch(err => {
    console.error(err);
    process.exit(1);
});
