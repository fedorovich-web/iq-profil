import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { writeFileSync } from 'node:fs';

const browser = await chromium.launch({
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  headless: true,
});
const results = [];
const base = 'http://127.0.0.1:8123';

async function audit(page, name) {
  const result = await new AxeBuilder({ page }).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
  results.push({
    name,
    violations: result.violations.map(v => ({
      id: v.id, impact: v.impact, description: v.help,
      nodes: v.nodes.slice(0, 4).map(n => ({ target: n.target, summary: n.failureSummary })),
    })),
  });
  console.log('AXE', name, 'issues', result.violations.map(v => v.id + ':' + v.impact).join(', ') || 'none');
}

try {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await context.newPage();
  await page.goto(base);
  await audit(page, 'landing-mobile');
  await page.locator('.hero-actions form button').click();
  await audit(page, 'quiz-mobile');
  for (let i = 1; i <= 25; i++) {
    await page.locator('.answer-tile').first().click();
    await page.locator('button[name="navigation"][value="' + (i === 25 ? 'finish' : 'next') + '"]').click();
  }
  await audit(page, 'paywall-mobile');
  await page.locator('.offer-card .btn').click();
  await audit(page, 'checkout-mobile');
  await page.locator('.checkout-card form button').click();
  await audit(page, 'result-mobile');
  const severe = results.flatMap(r => r.violations.filter(v => ['serious','critical'].includes(v.impact)).map(v => r.name + ':' + v.id));
  console.log('AXE_SUMMARY', JSON.stringify({ pages: results.length, severe, violations: results.reduce((n, r) => n + r.violations.length, 0) }));
  if (severe.length) process.exitCode = 1;
} finally {
  writeFileSync('docs/accessibility-report.json', JSON.stringify(results, null, 2));
  await browser.close();
}
