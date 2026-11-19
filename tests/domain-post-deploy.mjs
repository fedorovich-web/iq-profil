// Run after Dokploy redeploy: node tests/domain-post-deploy.mjs
// This script makes read-only HTTP requests and never touches production data.
const origin = 'https://flatero.ru';
const cases = [
    ['HTTP WWW redirect', 'http://www.flatero.ru/seo-audit?utm_source=qa', 'https://flatero.ru/seo-audit?utm_source=qa'],
    ['HTTPS WWW redirect', 'https://www.flatero.ru/seo-audit?utm_source=qa', 'https://flatero.ru/seo-audit?utm_source=qa'],
];
const failures = [];
const report = [];

function check(name, condition, detail) {
    const item = { name, passed: Boolean(condition), detail };
    report.push(item);
    if (!condition) failures.push(item);
}

for (const [name, url, expected] of cases) {
    try {
        const response = await fetch(url, {
            redirect: 'manual',
            signal: AbortSignal.timeout(12000),
        });
        const location = response.headers.get('location');
        check(name, response.status === 301 && location === expected, {
            status: response.status,
            location,
            expected,
        });
    } catch (error) {
        check(name, false, String(error));
    }
}

try {
    const homepage = await fetch(origin + '/', { signal: AbortSignal.timeout(12000) });
    const html = await homepage.text();
    check('Canonical landing', homepage.ok && html.includes('<link rel="canonical" href="https://flatero.ru/">'), homepage.status);
    check('Open Graph landing URL', html.includes('<meta property="og:url" content="https://flatero.ru/">'), homepage.status);

    const jsonLd = html.match(/<script type="application\/ld\+json">(.*?)<\/script>/s);
    const structuredData = jsonLd ? JSON.parse(jsonLd[1]) : null;
    const entries = structuredData?.['@graph'] ?? [];
    check('WebSite structured URL', entries.some(item => item['@type'] === 'WebSite' && item.url === origin + '/'), entries.map(item => item['@type']));
    check('WebPage structured URL', entries.some(item => item['@type'] === 'WebPage' && item.url === origin + '/'), entries.map(item => item['@type']));

    const sitemap = await fetch(origin + '/sitemap.xml', { signal: AbortSignal.timeout(12000) });
    const sitemapText = await sitemap.text();
    check('Sitemap contains canonical URL', sitemap.ok && sitemapText.includes('<loc>https://flatero.ru/</loc>'), sitemapText);
    check('Sitemap excludes www', !sitemapText.includes('www.flatero.ru'), sitemapText);
} catch (error) {
    check('Homepage and sitemap', false, String(error));
}

console.log(JSON.stringify({
    status: failures.length === 0 ? 'passed' : 'failed',
    passed: report.length - failures.length,
    total: report.length,
    failures,
}, null, 2));
if (failures.length) process.exitCode = 1;
