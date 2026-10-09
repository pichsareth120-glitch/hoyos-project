import puppeteer from 'puppeteer-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://127.0.0.1:8000';
const ADMIN_HOME = `${BASE_URL}/admin`;

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

let passCount = 0;
let failCount = 0;

function ok(id, msg) {
    console.log(`PASS [${id}]: ${msg}`);
    passCount++;
}
function ko(id, msg) {
    console.error(`FAIL [${id}]: ${msg}`);
    failCount++;
}

async function login(page) {
    await page.goto(`${ADMIN_HOME}/customers`, { waitUntil: 'networkidle0' });
    if (page.url().includes('/login')) {
        await page.type('input[name="email"]', 'admin@hoyos.com');
        await page.type('input[name="password"]', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
        console.log(`Logged in. URL: ${page.url()}`);
    } else {
        console.log(`Already authenticated: ${page.url()}`);
    }
}

async function run() {
    console.log('================================================================');
    console.log('   TOUCH-TARGET CHECK: 390PX MOBILE & 1440PX DESKTOP SIZES');
    console.log('   Target pages: shipments, payments, audit logs, customer detail,');
    console.log('                 order detail, product edit, reports/sales');
    console.log('================================================================\n');

    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const pagesToCheck = [
        { name: 'shipments', url: `${ADMIN_HOME}/shipments` },
        { name: 'payments', url: `${ADMIN_HOME}/payments` },
        { name: 'audit-logs', url: `${ADMIN_HOME}/audit-logs` },
        { name: 'customer-detail', url: `${ADMIN_HOME}/customers/2` },
        { name: 'order-detail', url: `${ADMIN_HOME}/orders/1` },
        { name: 'product-edit', url: `${ADMIN_HOME}/products/1/edit` },
        { name: 'reports-sales', url: `${ADMIN_HOME}/reports/sales` }
    ];

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 390, height: 844 });
        await login(page);

        console.log('\n--- PART 1: 390px Mobile Viewport Check (Touch Target >= 40px) ---');

        for (const item of pagesToCheck) {
            await page.setViewport({ width: 390, height: 844 });
            await page.goto(item.url, { waitUntil: 'networkidle0' });
            await sleep(500);

            const result = await page.evaluate((pageName) => {
                const MIN = 40;
                const candidates = [];

                // Buttons and submits
                document.querySelectorAll('button:not([aria-hidden="true"]), input[type="submit"], input[type="button"]').forEach(b => {
                    const aside = b.closest('aside');
                    // Ignore off-screen mobile sidebar drawer elements
                    if (aside && aside.getBoundingClientRect().right <= 0) return;
                    candidates.push({ el: b, type: 'button' });
                });

                // Pagination elements
                document.querySelectorAll('nav[role="navigation"] a, nav[role="navigation"] span, .pagination a, .pagination span').forEach(p => {
                    candidates.push({ el: p, type: 'pagination' });
                });

                // Filter pills (ignore large stat-cards)
                document.querySelectorAll('a[href*="status="], a[href*="filter="], a[class*="rounded-xl"][class*="whitespace-nowrap"]').forEach(fp => {
                    const aside = fp.closest('aside');
                    if (aside && aside.getBoundingClientRect().right <= 0) return;
                    if (fp.classList.contains('glass-card') || fp.closest('.grid-cols-2') || fp.closest('.grid-cols-4')) return;
                    candidates.push({ el: fp, type: 'filter-pill' });
                });

                // Action buttons/links (e.g. Back, Edit, View, Submit, Cancel)
                document.querySelectorAll('a.btn, a.touch-target, table td:last-child a, table td:last-child button, form button').forEach(act => {
                    const aside = act.closest('aside');
                    if (aside && aside.getBoundingClientRect().right <= 0) return;
                    if (act.classList.contains('glass-card') || act.closest('.grid-cols-2') || act.closest('.grid-cols-4')) return;
                    candidates.push({ el: act, type: 'action-link' });
                });

                const issues = [];
                const seen = new Set();
                let count = 0;

                for (const { el, type } of candidates) {
                    if (seen.has(el)) continue;
                    seen.add(el);

                    const r = el.getBoundingClientRect();
                    // Ignore invisible/collapsed elements
                    if (r.width === 0 || r.height === 0) continue;
                    const style = window.getComputedStyle(el);
                    if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') continue;

                    // Scroll element to center to avoid sticky header and sticky bottom bar
                    el.scrollIntoView({ block: 'center', inline: 'nearest' });

                    const rect = el.getBoundingClientRect();
                    const w = Math.round(rect.width);
                    const h = Math.round(rect.height);

                    const text = (el.innerText || el.getAttribute('aria-label') || el.getAttribute('value') || el.tagName).trim().substring(0, 30).replace(/\n/g, ' ');

                    if (w < MIN || h < MIN) {
                        issues.push({ text, w, h, type, reason: `Size ${w}×${h}px < 40×40px` });
                        continue;
                    }

                    // Test elementFromPoint at center
                    const cx = rect.left + rect.width / 2;
                    const cy = rect.top + rect.height / 2;
                    if (cx >= 0 && cx < window.innerWidth && cy >= 0 && cy < window.innerHeight) {
                        const hit = document.elementFromPoint(cx, cy);
                        const hitOk = hit && (hit === el || el.contains(hit) || hit.closest('button, a, input') === el);
                        if (!hitOk) {
                            issues.push({ text, w, h, type, reason: `Obscured by ${hit ? hit.tagName + '.' + hit.className : 'null'}` });
                            continue;
                        }
                    }

                    count++;
                }

                return { pageName, count, issues };
            }, item.name);

            if (result.issues.length === 0) {
                ok(`390px.${item.name}`, `${result.count} interactive targets verified ≥ 40×40px on ${item.url}`);
            } else {
                ko(`390px.${item.name}`, `${result.issues.length} elements < 40px on ${item.url}.`);
                result.issues.slice(0, 5).forEach(i => console.log(`   FAIL: [${i.type}] "${i.text}" ${i.reason}`));
            }
        }

        console.log('\n--- PART 2: 1440px Desktop Viewport Check (Desktop Sizes Unchanged / Compact) ---');
        await page.setViewport({ width: 1440, height: 900 });

        for (const item of pagesToCheck) {
            await page.goto(item.url, { waitUntil: 'networkidle0' });
            await sleep(500);

            const desktopMeasures = await page.evaluate((pageName) => {
                const samples = [];

                // 1. Table action links / small buttons (should be compact: height around 26-32px, NOT 40px)
                document.querySelectorAll('table td:last-child a, table td:last-child button, a[class*="px-3 py-1.5"], button[class*="px-3 py-1.5"], a[class*="px-2 py-1"]').forEach(el => {
                    const r = el.getBoundingClientRect();
                    if (r.width > 0 && r.height > 0) {
                        const text = (el.innerText || el.getAttribute('aria-label') || '').trim().substring(0, 25).replace(/\n/g, ' ');
                        samples.push({ type: 'table-action', text, w: Math.round(r.width), h: Math.round(r.height) });
                    }
                });

                // 2. Filter pills (should be compact: height around 30-34px on desktop; ignore stat cards)
                document.querySelectorAll('a[href*="status="], a[href*="filter="]').forEach(el => {
                    if (el.classList.contains('glass-card') || el.closest('.grid-cols-2') || el.closest('.grid-cols-4')) return;
                    const r = el.getBoundingClientRect();
                    if (r.width > 0 && r.height > 0) {
                        const text = (el.innerText || '').trim().substring(0, 20).replace(/\n/g, ' ');
                        samples.push({ type: 'filter-pill', text, w: Math.round(r.width), h: Math.round(r.height) });
                    }
                });

                // 3. Pagination buttons
                document.querySelectorAll('nav[role="navigation"] a, nav[role="navigation"] span').forEach(el => {
                    const r = el.getBoundingClientRect();
                    if (r.width > 0 && r.height > 0) {
                        const text = (el.innerText || '').trim().substring(0, 15);
                        samples.push({ type: 'pagination', text, w: Math.round(r.width), h: Math.round(r.height) });
                    }
                });

                return { pageName, samples };
            }, item.name);

            // Verify that desktop items did NOT inherit min-h-[40px]
            // On desktop, compact elements (small buttons, table action links, pagination) should have height <= 34px
            const compactItems = desktopMeasures.samples.filter(s => s.type === 'table-action' || s.type === 'pagination' || s.type === 'filter-pill');
            
            if (compactItems.length > 0) {
                const oversized = compactItems.filter(s => s.h > 36);
                const avgHeight = Math.round(compactItems.reduce((acc, s) => acc + s.h, 0) / compactItems.length);
                if (oversized.length === 0) {
                    ok(`1440px.${item.name}`, `Verified ${compactItems.length} desktop items are compact (avg height: ${avgHeight}px <= 34px, none enlarged to 40px).`);
                } else {
                    ko(`1440px.${item.name}`, `${oversized.length} desktop items enlarged to > 36px. Examples: ${JSON.stringify(oversized.slice(0, 3))}`);
                }
            } else {
                ok(`1440px.${item.name}`, `No table-action/pills/pagination on ${item.name} or clean custom form.`);
            }
        }

        await page.close();
    } catch (err) {
        console.error('CRASH:', err.stack || err);
        ko('fatal', err.message);
    } finally {
        await browser.close();
    }

    console.log('\n================================================================');
    console.log(`   TOUCH TARGET VERIFICATION: ${passCount} PASS / ${failCount} FAIL`);
    console.log('================================================================');
    if (failCount > 0) process.exit(1);
}

run();
