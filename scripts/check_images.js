import http from 'http';

const BASE_URL = 'http://127.0.0.1:8000';
const PAGES = [
    '/',
    '/category',
    '/credits',
    '/product/neon-spray-moto-denim-set',
    '/product/midnight-matrix-silhouette',
    '/product/stone-guardian-armor-set',
    '/product/boxy-cyber-jersey-tee',
    '/product/camo-tactical-cargo-set',
    '/product/dirty-acid-wash-denim-set'
];

function fetchUrl(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve({ status: res.statusCode, body: data, headers: res.headers }));
        }).on('error', reject);
    });
}

function checkImage(imgUrl) {
    return new Promise((resolve) => {
        const fullUrl = imgUrl.startsWith('http') ? imgUrl : `${BASE_URL}${imgUrl.startsWith('/') ? '' : '/'}${imgUrl}`;
        http.get(fullUrl, (res) => {
            resolve({ url: fullUrl, status: res.statusCode });
        }).on('error', (err) => {
            resolve({ url: fullUrl, status: 0, error: err.message });
        });
    });
}

async function run() {
    console.log('--- Checking Pages for Broken Image URLs (404s) ---');
    const allBroken = [];
    const checkedUrls = new Set();
    let totalImagesFound = 0;

    for (const pagePath of PAGES) {
        const pageUrl = `${BASE_URL}${pagePath}`;
        try {
            const pageRes = await fetchUrl(pageUrl);
            if (pageRes.status !== 200) {
                console.log(`[WARN] Page ${pagePath} returned HTTP ${pageRes.status}`);
                continue;
            }

            // Extract all static img src attributes (not :src)
            const imgSrcMatches = [...pageRes.body.matchAll(/<img[^>]+(?<!:)src=["']([^"']+)["']/gi)];
            // Also extract alpine :src defaults or asset paths if present
            const assetMatches = [...pageRes.body.matchAll(/asset\(["']([^"']+)["']\)/gi)];
            
            const pageImages = new Set();
            for (const m of imgSrcMatches) {
                // Ignore alpine expressions like :src
                if (!m[1].includes('{') && !m[1].includes('$') && !m[1].startsWith('data:')) {
                    pageImages.add(m[1]);
                }
            }

            console.log(`Page: ${pagePath} -> Found ${pageImages.size} image URLs`);
            totalImagesFound += pageImages.size;

            for (const imgUrl of pageImages) {
                if (checkedUrls.has(imgUrl)) continue;
                checkedUrls.add(imgUrl);

                const res = await checkImage(imgUrl);
                if (res.status !== 200) {
                    allBroken.push({ page: pagePath, img: imgUrl, status: res.status });
                    console.log(`  ❌ BROKEN [${res.status}]: ${imgUrl} on ${pagePath}`);
                } else {
                    console.log(`  ✓ OK [200]: ${imgUrl}`);
                }
            }
        } catch (e) {
            console.error(`Error loading page ${pagePath}:`, e.message);
        }
    }

    console.log('\n--- Summary ---');
    console.log(`Total unique image URLs checked: ${checkedUrls.size}`);
    console.log(`Total broken images (404s): ${allBroken.length}`);
    if (allBroken.length > 0) {
        console.log('Broken images list:');
        console.table(allBroken);
    } else {
        console.log('Zero broken images found! All 200 OK.');
    }
}

run();
