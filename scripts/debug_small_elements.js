import puppeteer from 'puppeteer-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

async function run() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 390, height: 844 });
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@hoyos.com');
    await page.type('input[name="password"]', 'password');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);

    for (const path of ['/admin/customers', '/admin/products']) {
        await page.goto('http://127.0.0.1:8000' + path, { waitUntil: 'networkidle0' });
        const small = await page.evaluate(() => {
            const res = [];
            for (const el of document.querySelectorAll('button, a[href], input[type="submit"]')) {
                const r = el.getBoundingClientRect();
                if (r.width > 0 && r.height > 0 && (r.width < 40 || r.height < 40)) {
                    res.push({
                        tag: el.tagName,
                        text: el.innerText.trim().substring(0, 30).replace(/\n/g, ' '),
                        aria: el.getAttribute('aria-label') || '',
                        w: Math.round(r.width),
                        h: Math.round(r.height),
                        cls: (el.className || '').substring(0, 50)
                    });
                }
            }
            return res;
        });
        console.log(`\n=== ${path} (${small.length} small elements) ===`);
        small.forEach(s => console.log(`${s.tag} [${s.w}x${s.h}] "${s.aria || s.text}" cls: ${s.cls}`));
    }
    await browser.close();
}

run().catch(console.error);
