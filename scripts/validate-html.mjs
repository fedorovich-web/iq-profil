import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const browser = await chromium.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await context.newPage();
const base = 'http://127.0.0.1:8123';
const report = [];

async function validate(label) {
  let html = await page.content();
  html = html.replace(/(<input[^>]+name="_token"[^>]+value=")[^"]+(")/g, '$1REDACTED$2');
  html = html.replace(/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/gi, '00000000-0000-4000-8000-000000000000');
  const response = await fetch('https://validator.w3.org/nu/?out=json', {
    method: 'POST', headers: { 'Content-Type': 'text/html; charset=utf-8', 'User-Agent': 'IQProfil-validator/1.0' }, body: html,
  });
  if (!response.ok) throw new Error('Validator status ' + response.status);
  const json = await response.json();
  const errors = json.messages.filter(item => item.type === 'error');
  report.push({ label, errors: errors.map(x=>({message:x.message,firstLine:x.firstLine,lastLine:x.lastLine,extract:x.extract})), warnings: json.messages.length - errors.length });
  console.log('W3C', label, 'errors', errors.length);
}
try {
  await page.goto(base); await validate('landing');
  await page.locator('.hero-actions form button').click(); await validate('quiz');
  for (let i = 1; i <= 25; i++) {
    await page.locator('.answer-tile').first().click();
    await page.locator('button[name="navigation"][value="' + (i === 25 ? 'finish' : 'next') + '"]').click();
  }
  await validate('paywall');
  await page.locator('.offer-card .btn').click(); await validate('checkout');
  await page.locator('.checkout-card form button').click(); await validate('result');
  const total = report.reduce((sum, p) => sum + p.errors.length, 0);
  console.log('W3C_SUMMARY', JSON.stringify({ pages: report.length, errors: total }));
  if (total) process.exitCode = 1;
} finally {
  writeFileSync('docs/w3c-report.json', JSON.stringify(report, null, 2));
  await browser.close();
}
