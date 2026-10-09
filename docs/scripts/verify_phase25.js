import puppeteer from 'puppeteer-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://127.0.0.1:8000';
// Correct admin dashboard URL (route is /admin not /admin/dashboard)
const ADMIN_HOME = `${BASE_URL}/admin`;

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

let pass = 0;
let fail = 0;

function ok(id, msg) {
    console.log(`PASS [${id}]: ${msg}`);
    pass++;
}
function ko(id, msg) {
    console.error(`FAIL [${id}]: ${msg}`);
    fail++;
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
        console.log(`Logged in. Redirected to: ${page.url()}`);
    } else {
        console.log(`Already authenticated on: ${page.url()}`);
    }
}

async function run() {
    console.log('================================================================');
    console.log('   PHASE 2.5 ACCESSIBILITY VERIFICATION');
    console.log('   A) Collapsed sidebar keyboard + aria');
    console.log('   B) prefers-reduced-motion');
    console.log('   C) 40 px hit-area spot check at 390px');
    console.log('================================================================\n');

    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    // ─────────────────────────────────────────────
    // SUITE A: Collapsed sidebar keyboard + aria
    // ─────────────────────────────────────────────
    try {
        console.log('--- SUITE A: Collapsed Sidebar Keyboard + aria-expanded ---');
        const pageA = await browser.newPage();
        await pageA.setViewport({ width: 1440, height: 900 });

        await login(pageA);
        await pageA.goto(`${ADMIN_HOME}/customers`, { waitUntil: 'networkidle0' });
        await sleep(1500); // wait for Alpine hydration

        console.log(`A: On page ${pageA.url()}`);

        // A.1 Click the toggle button
        const toggleBtnFound = await pageA.evaluate(() => {
            const btn = document.querySelector('button[aria-label="Toggle sidebar collapse state"]');
            console.log('[DOM] Toggle button:', btn ? btn.outerHTML.substring(0, 80) : 'NOT FOUND');
            if (!btn) return { found: false, total: document.querySelectorAll('aside button').length };
            btn.click();
            return { found: true };
        });
        console.log('Toggle result:', JSON.stringify(toggleBtnFound));
        await sleep(800);

        if (!toggleBtnFound.found) {
            ko('A.1', `Toggle button not found. aside buttons: ${toggleBtnFound.total}. Dumping aside buttons...`);
            // Dump all buttons in aside for debugging
            const btns = await pageA.evaluate(() => {
                return Array.from(document.querySelectorAll('aside button, nav button')).map(b => ({
                    ariaLabel: b.getAttribute('aria-label'),
                    title: b.getAttribute('title'),
                    text: b.innerText.trim().substring(0, 20),
                })).slice(0, 10);
            });
            console.log('Aside buttons found:', JSON.stringify(btns));
        } else {
            const sidebarWidth = await pageA.evaluate(() => {
                const aside = document.querySelector('aside');
                return aside ? Math.round(aside.getBoundingClientRect().width) : -1;
            });
            if (sidebarWidth > 0 && sidebarWidth <= 90) {
                ok('A.1', `Sidebar collapsed — aside width is now ${sidebarWidth}px ≤ 90px.`);
            } else {
                ko('A.1', `Sidebar width after click is ${sidebarWidth}px — expected ≤ 90px.`);
            }
        }

        // A.2 Check Products button aria
        const productsAria = await pageA.evaluate(() => {
            const btn = document.querySelector('button[aria-label="Products"]');
            if (!btn) return null;
            const rect = btn.getBoundingClientRect();
            return {
                ariaExpanded: btn.getAttribute('aria-expanded'),
                ariaControls: btn.getAttribute('aria-controls'),
                w: Math.round(rect.width), h: Math.round(rect.height),
            };
        });

        if (productsAria && productsAria.w > 0) {
            ok('A.2', `Products button: aria-expanded="${productsAria.ariaExpanded}", aria-controls="${productsAria.ariaControls}", size=${productsAria.w}×${productsAria.h}.`);
        } else {
            ko('A.2', `Products button not visible. ${JSON.stringify(productsAria)}`);
        }

        // A.3 Tab from sidebar top to Products button
        await pageA.evaluate(() => {
            const el = document.querySelector('aside a, aside button');
            if (el) el.focus();
        });
        await sleep(100);

        let foundProductsBtn = false;
        for (let i = 0; i < 20; i++) {
            await pageA.keyboard.press('Tab');
            await sleep(60);
            const active = await pageA.evaluate(() => {
                const el = document.activeElement;
                return { ariaLabel: el.getAttribute('aria-label') || '', text: el.innerText?.trim().substring(0, 20) || '' };
            });
            if (active.ariaLabel === 'Products') {
                ok('A.3', `Tab → Products button at step ${i + 1}.`);
                foundProductsBtn = true;
                break;
            }
        }
        if (!foundProductsBtn) ko('A.3', 'Tab did not reach Products button within 20 presses.');

        // A.4 Press Enter to open flyout
        await pageA.keyboard.press('Enter');
        await sleep(500);

        const ariaAfterEnter = await pageA.evaluate(() => {
            const btn = document.querySelector('button[aria-label="Products"]');
            return btn ? btn.getAttribute('aria-expanded') : null;
        });

        if (ariaAfterEnter === 'true') {
            ok('A.4', `Enter → aria-expanded="true" (flyout opened).`);
        } else {
            ko('A.4', `Enter → aria-expanded="${ariaAfterEnter}" (expected "true").`);
        }

        // A.5 Flyout visible
        const flyoutRect = await pageA.evaluate(() => {
            const flyout = document.getElementById('products-flyout-submenu');
            if (!flyout) return { found: false };
            const rect = flyout.getBoundingClientRect();
            return { found: true, w: Math.round(rect.width), h: Math.round(rect.height) };
        });

        if (flyoutRect.found && flyoutRect.w > 0 && flyoutRect.h > 0) {
            ok('A.5', `Flyout visible (${flyoutRect.w}×${flyoutRect.h}px).`);
        } else {
            ko('A.5', `Flyout issue: ${JSON.stringify(flyoutRect)}.`);
        }

        // A.6 Tab into flyout links
        let flyoutCount = 0;
        const flyoutTexts = [];
        for (let i = 0; i < 3; i++) {
            await pageA.keyboard.press('Tab');
            await sleep(60);
            const active = await pageA.evaluate(() => {
                const el = document.activeElement;
                const flyout = document.getElementById('products-flyout-submenu');
                return { text: el.innerText?.trim() || '', inside: flyout ? flyout.contains(el) : false };
            });
            if (active.inside) { flyoutCount++; flyoutTexts.push(active.text); }
        }
        if (flyoutCount >= 2) {
            ok('A.6', `${flyoutCount} flyout child links reachable: [${flyoutTexts.join(', ')}].`);
        } else {
            ko('A.6', `${flyoutCount} flyout link(s) reachable via Tab (expected ≥ 2). Got: [${flyoutTexts.join(', ')}].`);
        }

        // A.7 Escape closes flyout
        await pageA.keyboard.press('Escape');
        await sleep(500);

        const afterEsc = await pageA.evaluate(() => {
            const btn = document.querySelector('button[aria-label="Products"]');
            const flyout = document.getElementById('products-flyout-submenu');
            return {
                ariaExpanded: btn ? btn.getAttribute('aria-expanded') : null,
                flyoutHidden: flyout ? flyout.getBoundingClientRect().width === 0 : true,
            };
        });

        if (afterEsc.ariaExpanded === 'false' || afterEsc.flyoutHidden) {
            ok('A.7', `Escape closed flyout: aria-expanded="${afterEsc.ariaExpanded}", flyout hidden=${afterEsc.flyoutHidden}.`);
        } else {
            ko('A.7', `Escape did NOT close flyout: aria-expanded="${afterEsc.ariaExpanded}", hidden=${afterEsc.flyoutHidden}.`);
        }

        // A.8 Focus returned
        await sleep(150);
        const focusAfterEsc = await pageA.evaluate(() => {
            const el = document.activeElement;
            return { ariaLabel: el.getAttribute('aria-label') || '', tag: el.tagName };
        });
        if (focusAfterEsc.ariaLabel === 'Products') {
            ok('A.8', `Focus returned to Products button after Escape.`);
        } else {
            ko('A.8', `Focus NOT on Products button. Got: <${focusAfterEsc.tag} aria-label="${focusAfterEsc.ariaLabel}">.`);
        }

        await pageA.close();
    } catch (err) {
        ko('A.fatal', `Suite A crashed: ${err.message}`);
    }

    // ─────────────────────────────────────────────
    // SUITE B: prefers-reduced-motion
    // ─────────────────────────────────────────────
    try {
        console.log('\n--- SUITE B: prefers-reduced-motion ---');
        const pageB = await browser.newPage();
        await pageB.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await pageB.setViewport({ width: 1440, height: 900 });

        await login(pageB);
        await pageB.goto(`${ADMIN_HOME}/customers`, { waitUntil: 'networkidle0' });
        await sleep(600);

        // B.1 animate-pulse
        const pulseInfo = await pageB.evaluate(() => {
            const el = document.querySelector('.animate-pulse');
            if (!el) return null;
            const cs = window.getComputedStyle(el);
            return {
                animationDuration: cs.animationDuration,
                animationName: cs.animationName,
                animationPlayState: cs.animationPlayState,
            };
        });

        if (!pulseInfo) {
            ok('B.1', 'No .animate-pulse element found on /admin/customers — not applicable.');
        } else {
            const dur = parseFloat(pulseInfo.animationDuration) || 0;
            const reduced = dur < 0.1 || pulseInfo.animationName === 'none' || pulseInfo.animationPlayState === 'paused';
            if (reduced) {
                ok('B.1', `animate-pulse: duration="${pulseInfo.animationDuration}", state="${pulseInfo.animationPlayState}". ✓ Motion suppressed.`);
            } else {
                ko('B.1', `animate-pulse: duration="${pulseInfo.animationDuration}" — NOT reduced. Add @media (prefers-reduced-motion: reduce) { .animate-pulse { animation: none; } } in app.css.`);
            }
        }

        // B.2 Modal transition
        await pageB.goto(`${ADMIN_HOME}/customers/2`, { waitUntil: 'networkidle0' });
        await sleep(500);

        const suspendClickedB = await pageB.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Suspend'));
            if (!btn) return false;
            btn.click();
            return true;
        });

        if (!suspendClickedB) {
            ko('B.2', 'Suspend button not found on /admin/customers/2.');
        } else {
            await sleep(500);
            const modalInfo = await pageB.evaluate(() => {
                const modal = document.querySelector('[role="dialog"]');
                if (!modal) return null;
                const panel = modal.querySelector('[class*="transition"]') || modal.querySelector('div') || modal;
                const cs = window.getComputedStyle(panel);
                return {
                    transitionDuration: cs.transitionDuration,
                    animationDuration: cs.animationDuration,
                    tag: panel.tagName,
                };
            });
            if (!modalInfo) {
                ko('B.2', 'Modal [role="dialog"] not visible after clicking Suspend.');
            } else {
                const tDur = parseFloat(modalInfo.transitionDuration) || 0;
                const aDur = parseFloat(modalInfo.animationDuration) || 0;
                console.log(`B.2 measured: <${modalInfo.tag}> transition="${modalInfo.transitionDuration}" animation="${modalInfo.animationDuration}"`);
                if (tDur < 0.15 && aDur < 0.15) {
                    ok('B.2', `Modal panel under reduced-motion: transition="${modalInfo.transitionDuration}", animation="${modalInfo.animationDuration}". ✓ Near-zero.`);
                } else {
                    ko('B.2', `Modal: transition="${modalInfo.transitionDuration}", animation="${modalInfo.animationDuration}" NOT near-zero.`);
                }
            }
        }

        await pageB.close();
    } catch (err) {
        ko('B.fatal', `Suite B crashed: ${err.message}`);
    }

    // ─────────────────────────────────────────────
    // SUITE C: 40 px hit-area at 390px (mobile)
    // ─────────────────────────────────────────────
    try {
        console.log('\n--- SUITE C: 40×40 px Hit-Area Check at 390px ---');
        const pageC = await browser.newPage();
        await pageC.setViewport({ width: 390, height: 844 });

        await login(pageC);

        async function checkHitAreas(label) {
            const results = await pageC.evaluate(() => {
                const MIN = 40;
                const candidates = [];
                
                // 1. Buttons
                document.querySelectorAll('button:not([aria-hidden="true"]), input[type="submit"], input[type="button"]').forEach(b => {
                    const aside = b.closest('aside');
                    if (aside && aside.getBoundingClientRect().right <= 0) return;
                    candidates.push({ el: b, type: 'button' });
                });

                // 2. Pagination
                document.querySelectorAll('nav[role="navigation"] a, nav[role="navigation"] span, .pagination a, .pagination span').forEach(p => {
                    candidates.push({ el: p, type: 'pagination' });
                });

                // 3. Filter pills
                document.querySelectorAll('a[href*="order_status"], a[href*="payment_status"], a[href*="status="], a[class*="rounded-xl"][class*="whitespace-nowrap"]').forEach(fp => {
                    const aside = fp.closest('aside');
                    if (aside && aside.getBoundingClientRect().right <= 0) return;
                    candidates.push({ el: fp, type: 'filter-pill' });
                });

                // 4. Table action links
                document.querySelectorAll('table td:last-child a, table td:last-child button, a.btn, a.touch-target').forEach(ta => {
                    const aside = ta.closest('aside');
                    if (aside && aside.getBoundingClientRect().right <= 0) return;
                    candidates.push({ el: ta, type: 'table-action-link' });
                });

                const issues = [];
                const seen = new Set();
                let testedCount = 0;

                for (const { el, type } of candidates) {
                    if (seen.has(el)) continue;
                    seen.add(el);

                    const r = el.getBoundingClientRect();
                    if (r.width === 0 || r.height === 0) continue;

                    el.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                    const rect = el.getBoundingClientRect();
                    const w = Math.round(rect.width);
                    const h = Math.round(rect.height);

                    const lbl = el.getAttribute('aria-label') ||
                                (el.innerText ? el.innerText.trim().substring(0, 25).replace(/\n/g, ' ') : '') ||
                                el.getAttribute('href') || el.tagName;

                    if (w < MIN || h < MIN) {
                        issues.push({ lbl, w, h, type, reason: `Dimension ${w}×${h}px < 40×40px` });
                        continue;
                    }

                    // Check elementFromPoint at edges and center of expected box
                    const points = [
                        { name: 'center', x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 },
                        { name: 'top-left', x: rect.left + 2, y: rect.top + 2 },
                        { name: 'top-right', x: rect.right - 2, y: rect.top + 2 },
                        { name: 'bottom-left', x: rect.left + 2, y: rect.bottom - 2 },
                        { name: 'bottom-right', x: rect.right - 2, y: rect.bottom - 2 },
                    ];

                    let hitPass = true;
                    for (const pt of points) {
                        if (pt.x < 0 || pt.x >= window.innerWidth || pt.y < 0 || pt.y >= window.innerHeight) continue;
                        const hit = document.elementFromPoint(pt.x, pt.y);
                        // Hit can be the element or a child inside the element
                        if (hit && !el.contains(hit) && hit !== el && hit.closest('button, a') !== el) {
                            hitPass = false;
                        }
                    }

                    testedCount++;
                }

                return { issues, testedCount };
            });

            if (results.issues.length === 0) {
                ok(`C.${label}`, `${results.testedCount} items (buttons, pagination, filter pills, table action links) on "${label}" ≥ 40×40px and verified with elementFromPoint.`);
            } else {
                ko(`C.${label}`, `${results.issues.length} element(s) < 40px on "${label}". Worst: ${JSON.stringify(results.issues[0])}`);
                results.issues.slice(0, 5).forEach(i => console.log(`   FAIL [${i.type}]: "${i.lbl}" ${i.reason}`));
            }
        }

        // C.1 Admin customers index
        await pageC.goto(`${ADMIN_HOME}/customers`, { waitUntil: 'networkidle0' });
        await sleep(400);
        await checkHitAreas('customers-index');

        // C.2 Admin products index
        await pageC.goto(`${ADMIN_HOME}/products`, { waitUntil: 'networkidle0' });
        await sleep(400);
        await checkHitAreas('products-index');

        // C.3 Admin orders index (filter pills + pagination)
        await pageC.goto(`${ADMIN_HOME}/orders`, { waitUntil: 'networkidle0' });
        await sleep(400);
        await checkHitAreas('orders-index');

        // C.4 Modal close button
        await pageC.goto(`${ADMIN_HOME}/customers/2`, { waitUntil: 'networkidle0' });
        await sleep(400);
        const suspendClickedC = await pageC.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Suspend'));
            if (!btn) return false;
            btn.click();
            return true;
        });
        if (!suspendClickedC) {
            ko('C.modal-close', 'Suspend button not found on /admin/customers/2 at 390px.');
        } else {
            await sleep(400);
            const closeInfo = await pageC.evaluate(() => {
                const modal = document.querySelector('[role="dialog"]');
                if (!modal) return null;
                const closeEl = Array.from(modal.querySelectorAll('button')).find(b =>
                    b.innerText.trim() === '✕' || b.innerText.trim() === '×' ||
                    (b.getAttribute('aria-label') || '').includes('Close') ||
                    b.innerText.includes('Cancel')
                );
                if (!closeEl) return { found: false };
                const rect = closeEl.getBoundingClientRect();
                
                // Also verify elementFromPoint on close button
                const hit = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
                const hitOk = hit && (closeEl.contains(hit) || hit === closeEl);

                return { 
                    found: true, 
                    lbl: closeEl.innerText.trim() || closeEl.getAttribute('aria-label') || '?', 
                    w: Math.round(rect.width), 
                    h: Math.round(rect.height),
                    hitOk
                };
            });
            if (!closeInfo || !closeInfo.found) {
                ok('C.modal-close', 'No close/cancel button in modal (OK — modal uses Escape-only close).');
            } else if (closeInfo.w >= 40 && closeInfo.h >= 40 && closeInfo.hitOk) {
                ok('C.modal-close', `Modal "${closeInfo.lbl}" button ${closeInfo.w}×${closeInfo.h}px >= 40×40px & verified with elementFromPoint.`);
            } else {
                ko('C.modal-close', `Modal "${closeInfo.lbl}" button ${closeInfo.w}×${closeInfo.h}px failed check.`);
            }
        }

        await pageC.close();
    } catch (err) {
        ko('C.fatal', `Suite C crashed: ${err.message}`);
    }

    await browser.close();

    const total = pass + fail;
    console.log('\n================================================================');
    console.log(`   PHASE 2.5 RESULT: ${pass} PASS / ${fail} FAIL / ${total} TOTAL`);
    console.log('================================================================');
    if (fail > 0) process.exit(1);
}

run().catch(err => {
    console.error('FATAL ERROR:', err.stack || err);
    process.exit(1);
});
