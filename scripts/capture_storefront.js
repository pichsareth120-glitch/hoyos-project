import puppeteer from 'puppeteer-core';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const ARTIFACT_DIR = 'C:\\Users\\pichs\\.gemini\\antigravity-ide\\brain\\08e809ed-7781-46b6-8eb6-3c56dd50e780';
const suffix = process.argv[2] || 'before';

async function run() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });

    const pages = [
        { name: 'home', url: 'http://127.0.0.1:8000/' },
        { name: 'catalog', url: 'http://127.0.0.1:8000/products' },
        { name: 'product', url: 'http://127.0.0.1:8000/product/neon-spray-moto-denim-set' },
        { name: 'track', url: 'http://127.0.0.1:8000/orders/track' },
    ];

    for (const p of pages) {
        await page.goto(p.url, { waitUntil: 'networkidle0' });
        const filePath = path.join(ARTIFACT_DIR, `storefront_${p.name}_${suffix}.png`);
        await page.screenshot({ path: filePath, fullPage: false });
        console.log(`Captured ${filePath}`);
    }

    await browser.close();
}

run().catch(console.error);
