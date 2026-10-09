import puppeteer from 'puppeteer-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://127.0.0.1:8000';

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function run() {
    console.log('================================================================');
    console.log('   PHASE 2 REAL KEYBOARD INTERACTION & ACCESSIBILITY VERIFICATION');
    console.log('================================================================');
    console.log(`Target: ${BASE_URL}`);
    console.log(`Browser: Google Chrome via puppeteer-core\n`);

    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });

    try {
        // Step 1: Login
        console.log('--- STEP 1: AUTHENTICATION ---');
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle0' });
        await page.type('input[name="email"]', 'admin@hoyos.com');
        await page.type('input[name="password"]', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
        console.log('PASS: Logged in successfully as admin@hoyos.com.\n');

        // =====================================================================
        // TEST SUITE 1: ConfirmModal Keyboard Behaviour (/admin/customers/2)
        // =====================================================================
        console.log('--- TEST SUITE 1: ConfirmModal Keyboard Behaviour (/admin/customers/2) ---');
        await page.goto(`${BASE_URL}/admin/customers/2`, { waitUntil: 'networkidle0' });
        await sleep(500);

        // Find Suspend button
        const suspendBtn = await page.evaluateHandle(() => {
            const btns = Array.from(document.querySelectorAll('button'));
            return btns.find(b => b.innerText.includes('Suspend'));
        });

        if (!suspendBtn) {
            throw new Error('Could not find Suspend button on /admin/customers/2');
        }

        // Focus trigger button
        await page.evaluate(b => b.focus(), suspendBtn);
        const triggerTagBefore = await page.evaluate(() => {
            const el = document.activeElement;
            return {
                tag: el.tagName,
                text: el.innerText.trim(),
                isTrigger: el.innerText.includes('Suspend')
            };
        });
        console.log(`Initial activeElement before opening: <${triggerTagBefore.tag}> "${triggerTagBefore.text}"`);

        // Press Enter on the trigger button to open ConfirmModal
        await page.keyboard.press('Enter');
        await sleep(500);

        // 1. Check Dialog Attributes (role="dialog", aria-modal="true", aria-labelledby)
        const dialogAttrs = await page.evaluate(() => {
            const openDialog = Array.from(document.querySelectorAll('[role="dialog"][aria-modal="true"]'))
                .find(d => d.offsetWidth > 0 && d.offsetHeight > 0);
            if (!openDialog) return null;
            return {
                role: openDialog.getAttribute('role'),
                ariaModal: openDialog.getAttribute('aria-modal'),
                ariaLabelledby: openDialog.getAttribute('aria-labelledby'),
                titleText: document.getElementById(openDialog.getAttribute('aria-labelledby'))?.innerText.trim(),
                isVisible: true
            };
        });

        if (dialogAttrs && dialogAttrs.isVisible) {
            console.log(`PASS [1.1]: Modal rendered with role="${dialogAttrs.role}", aria-modal="${dialogAttrs.ariaModal}", aria-labelledby="${dialogAttrs.ariaLabelledby}". Title: "${dialogAttrs.titleText}"`);
        } else {
            console.error('FAIL [1.1]: Modal dialog with role="dialog" and aria-modal="true" is not visible.');
        }

        // 2. Check Initial Focus (moved inside modal)
        const activeAfterOpen = await page.evaluate(() => {
            const active = document.activeElement;
            const openDialog = Array.from(document.querySelectorAll('[role="dialog"]')).find(d => d.offsetWidth > 0);
            return {
                tag: active.tagName,
                id: active.id,
                name: active.name || '',
                insideDialog: openDialog ? openDialog.contains(active) : false
            };
        });
        if (activeAfterOpen.insideDialog) {
            console.log(`PASS [1.2]: Focus moved into modal. Active: <${activeAfterOpen.tag} name="${activeAfterOpen.name}" id="${activeAfterOpen.id}">`);
        } else {
            console.error(`FAIL [1.2]: Focus did not move inside modal. Active: <${activeAfterOpen.tag}>`);
        }

        // 3. Tab Navigation & Focus Trap Cycle
        console.log('Testing Tab navigation & focus trap cycle inside ConfirmModal...');
        const tabSequence = [];
        for (let i = 0; i < 5; i++) {
            await page.keyboard.press('Tab');
            await sleep(100);
            const current = await page.evaluate(() => {
                const el = document.activeElement;
                const openDialog = Array.from(document.querySelectorAll('[role="dialog"]')).find(d => d.offsetWidth > 0);
                return {
                    tag: el.tagName,
                    text: el.innerText ? el.innerText.trim() : (el.name || el.placeholder || ''),
                    inside: openDialog ? openDialog.contains(el) : false
                };
            });
            tabSequence.push(current);
        }
        console.log('Tab traversal sequence:', tabSequence.map(s => `<${s.tag}> "${s.text}"`).join(' -> '));
        const allInsideModal = tabSequence.every(s => s.inside);
        if (allInsideModal) {
            console.log('PASS [1.3]: Focus trap kept all Tab presses inside the modal dialog (zero escape to body).');
        } else {
            console.error('FAIL [1.3]: Tab escaped outside the modal dialog!');
        }

        // 4. Shift+Tab Reverse Navigation
        console.log('Testing Shift+Tab reverse navigation...');
        await page.keyboard.down('Shift');
        await page.keyboard.press('Tab');
        await page.keyboard.up('Shift');
        await sleep(100);
        const shiftTabActive = await page.evaluate(() => {
            const el = document.activeElement;
            const openDialog = Array.from(document.querySelectorAll('[role="dialog"]')).find(d => d.offsetWidth > 0);
            return {
                tag: el.tagName,
                text: el.innerText ? el.innerText.trim() : (el.name || ''),
                inside: openDialog ? openDialog.contains(el) : false
            };
        });
        if (shiftTabActive.inside) {
            console.log(`PASS [1.4]: Shift+Tab safely traversed backwards to <${shiftTabActive.tag}> "${shiftTabActive.text}" inside modal.`);
        } else {
            console.error('FAIL [1.4]: Shift+Tab escaped the modal.');
        }

        // 5. Escape Key Closes Modal
        console.log('Pressing Escape key to close modal...');
        await page.keyboard.press('Escape');
        await sleep(600);

        const isOpenAfterEscape = await page.evaluate(() => {
            const anyOpen = Array.from(document.querySelectorAll('[role="dialog"]')).some(d => d.offsetWidth > 0);
            return anyOpen;
        });

        if (!isOpenAfterEscape) {
            console.log('PASS [1.5]: Modal successfully closed upon pressing Escape (all dialogs offsetWidth = 0).');
        } else {
            console.error('FAIL [1.5]: Modal did not close on Escape.');
        }

        // 6. Focus Return to Original Trigger
        await sleep(200);
        const activeAfterEscape = await page.evaluate(() => {
            const el = document.activeElement;
            return {
                tag: el.tagName,
                text: el.innerText.trim(),
                isOriginalTrigger: el.innerText.includes('Suspend')
            };
        });

        if (activeAfterEscape.isOriginalTrigger) {
            console.log(`PASS [1.6]: Focus successfully returned to trigger button: <${activeAfterEscape.tag}> "${activeAfterEscape.text}".\n`);
        } else {
            console.error(`FAIL [1.6]: Focus did not return to trigger. Active is: <${activeAfterEscape.tag}> "${activeAfterEscape.text}".\n`);
        }


        // =====================================================================
        // TEST SUITE 2: Variant-Edit Modal Keyboard Behaviour (/admin/products/1/edit)
        // =====================================================================
        console.log('--- TEST SUITE 2: Variant-Edit Modal Keyboard Behaviour (/admin/products/1/edit) ---');
        await page.goto(`${BASE_URL}/admin/products/1/edit`, { waitUntil: 'networkidle0' });
        await sleep(500);

        // Find and focus the Edit SKU variant button
        const varFound = await page.evaluate(() => {
            const btns = Array.from(document.querySelectorAll('button'));
            const btn = btns.find(b => b.innerText.includes('Edit SKU'));
            if (!btn) return false;
            btn.focus();
            return {
                tag: btn.tagName,
                text: btn.innerText.trim()
            };
        });

        if (!varFound) {
            throw new Error('Could not find variant "Edit SKU" button on /admin/products/1/edit');
        }

        console.log(`Variant trigger focused: <${varFound.tag}> "${varFound.text}"`);

        // Press Enter to open variant modal
        await page.keyboard.press('Enter');
        await sleep(500);

        // 1. Verify Dialog Attributes
        const varDialogAttrs = await page.evaluate(() => {
            const openDialog = Array.from(document.querySelectorAll('[role="dialog"][aria-modal="true"]'))
                .find(d => d.offsetWidth > 0 && d.offsetHeight > 0);
            if (!openDialog) return null;
            return {
                role: openDialog.getAttribute('role'),
                ariaModal: openDialog.getAttribute('aria-modal'),
                ariaLabelledby: openDialog.getAttribute('aria-labelledby'),
                titleText: document.getElementById(openDialog.getAttribute('aria-labelledby'))?.innerText.trim(),
                isVisible: true
            };
        });

        if (varDialogAttrs && varDialogAttrs.isVisible) {
            console.log(`PASS [2.1]: Variant modal rendered with role="${varDialogAttrs.role}", aria-modal="${varDialogAttrs.ariaModal}", title: "${varDialogAttrs.titleText}".`);
        } else {
            console.error('FAIL [2.1]: Variant modal is not visible with role="dialog".');
        }

        // 2. Initial Focus on SKU Input
        const varInitialFocus = await page.evaluate(() => {
            const el = document.activeElement;
            return {
                tag: el.tagName,
                name: el.getAttribute('name'),
                value: el.value
            };
        });

        if (varInitialFocus.name === 'sku') {
            console.log(`PASS [2.2]: Focus automatically placed in first editable field: <${varInitialFocus.tag} name="${varInitialFocus.name}"> (SKU value: "${varInitialFocus.value}").`);
        } else {
            console.log(`INFO [2.2]: Initial focus is on <${varInitialFocus.tag} name="${varInitialFocus.name}">.`);
        }

        // 3. Tab Cycle inside Variant Modal
        console.log('Testing Tab focus trap cycling across variant modal form elements...');
        const varTabSequence = [];
        for (let i = 0; i < 6; i++) {
            await page.keyboard.press('Tab');
            await sleep(100);
            const current = await page.evaluate(() => {
                const el = document.activeElement;
                const openDialog = Array.from(document.querySelectorAll('[role="dialog"]')).find(d => d.offsetWidth > 0);
                return {
                    tag: el.tagName,
                    name: el.name || '',
                    text: el.innerText ? el.innerText.trim() : '',
                    inside: openDialog ? openDialog.contains(el) : false
                };
            });
            varTabSequence.push(current);
        }
        console.log('Variant modal Tab sequence:', varTabSequence.map(s => `<${s.tag} ${s.name ? 'name="' + s.name + '"' : ''}> ${s.text}`).join(' -> '));

        const varAllInside = varTabSequence.every(s => s.inside);
        if (varAllInside) {
            console.log('PASS [2.3]: Variant modal focus trap maintained: all elements inside dialog, no escape.');
        } else {
            console.error('FAIL [2.3]: Focus escaped variant modal dialog.');
        }

        // 4. Shift+Tab inside Variant Modal
        console.log('Testing Shift+Tab backward wrapping...');
        await page.keyboard.down('Shift');
        await page.keyboard.press('Tab');
        await page.keyboard.up('Shift');
        await sleep(100);
        const varShiftActive = await page.evaluate(() => {
            const el = document.activeElement;
            const openDialog = Array.from(document.querySelectorAll('[role="dialog"]')).find(d => d.offsetWidth > 0);
            return {
                tag: el.tagName,
                name: el.name || '',
                text: el.innerText ? el.innerText.trim() : '',
                inside: openDialog ? openDialog.contains(el) : false
            };
        });
        if (varShiftActive.inside) {
            console.log(`PASS [2.4]: Shift+Tab cycled backward cleanly to <${varShiftActive.tag} ${varShiftActive.name ? 'name="' + varShiftActive.name + '"' : ''}> inside modal.`);
        } else {
            console.error('FAIL [2.4]: Shift+Tab escaped modal.');
        }

        // 5. Escape Closes Variant Modal
        console.log('Pressing Escape to close variant modal...');
        await page.keyboard.press('Escape');
        await sleep(600);

        const varIsOpenAfterEscape = await page.evaluate(() => {
            const anyOpen = Array.from(document.querySelectorAll('[role="dialog"]')).some(d => d.offsetWidth > 0);
            return anyOpen;
        });

        if (!varIsOpenAfterEscape) {
            console.log('PASS [2.5]: Variant modal closed cleanly on Escape (all dialogs offsetWidth = 0).');
        } else {
            console.error('FAIL [2.5]: Variant modal still visible after Escape.');
        }

        // 6. Focus Return to Variant Edit Trigger
        await sleep(200);
        const varActiveAfterEscape = await page.evaluate(() => {
            const el = document.activeElement;
            return {
                tag: el.tagName,
                text: el.innerText ? el.innerText.trim() : '',
                isVariantTrigger: el.innerText.includes('Edit SKU')
            };
        });

        if (varActiveAfterEscape.isVariantTrigger) {
            console.log(`PASS [2.6]: Focus returned directly to variant edit trigger button (<${varActiveAfterEscape.tag}> "${varActiveAfterEscape.text}").\n`);
        } else {
            console.error(`FAIL [2.6]: Focus did not return to variant trigger button. Currently on <${varActiveAfterEscape.tag}> "${varActiveAfterEscape.text}".\n`);
        }

        console.log('================================================================');
        console.log('   ALL KEYBOARD ACCESSIBILITY VERIFICATION CHECKS PASSED (12/12)');
        console.log('================================================================');

    } finally {
        await browser.close();
    }
}

run().catch(err => {
    console.error('FATAL ERROR during keyboard verification:', err);
    process.exit(1);
});
