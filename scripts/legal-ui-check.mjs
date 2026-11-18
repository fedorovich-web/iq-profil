import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import { writeFileSync } from 'node:fs';

const routes = ['/privacy', '/offer', '/personal-data-consent', '/subscription/cancel', '/register', '/login'];
const browser = await chromium.launch({
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: true,
});
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
const result = [];
try {
  for (const route of routes) {
    const response = await page.goto('http://127.0.0.1:8123' + route);
    const html = (await page.content()).replace(/(<input[^>]+name="_token"[^>]+value=")[^"]+(")/g, '$1REDACTED$2');
    const validation = await fetch('https://validator.w3.org/nu/?out=json', {
      method: 'POST', headers: { 'Content-Type': 'text/html; charset=utf-8' }, body: html,
    });
    if (!validation.ok) throw new Error('HTML validator response ' + validation.status);
    const data = await validation.json();
    const errors = data.messages.filter(m => m.type === 'error').map(m => ({ message: m.message, extract: m.extract }));
    const axe = await new AxeBuilder({ page }).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
    const violations = axe.violations.map(v => ({ id: v.id, impact: v.impact, targets: v.nodes.map(n => n.target) }));
    result.push({ route, status: response.status(), errors, violations });
    console.log(route, 'HTTP', response.status(), 'HTML errors', errors.length, 'a11y violations', violations.length);
  }
  const issues = result.reduce((sum,x) => sum+x.errors.length+x.violations.length+(x.status!==200 ? 1:0),0);
  if (issues>0) process.exitCode = 1;
  console.log('LEGAL_UI_QA',JSON.stringify({pages:result.length,issues}));
} finally {
  writeFileSync('docs/legal-ui-report.json',JSON.stringify(result,null,2));
  await browser.close();
}
