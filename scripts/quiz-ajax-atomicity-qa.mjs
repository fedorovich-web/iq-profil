import { chromium } from 'playwright';
import { resolveBrowserOptions } from './browser-options.mjs';

const base = process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8123';
const browser = await chromium.launch(resolveBrowserOptions());
const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));

try {
  await page.goto(base + '/');
  await page.locator('.hero-actions form button').first().click();
  await page.waitForURL(/\/quiz\/intake\/gender/);
  await page.locator('.intake-gender-choice').first().click();
  await page.locator('.intake-submit').click();
  await page.waitForURL(/\/quiz\/intake\/age/);
  await page.locator('.intake-age-choice').first().click();
  await page.locator('.intake-submit').click();
  await page.waitForURL(/\/quiz$/);

  const initialHeading = await page.locator('#question-heading').textContent();
  // Keep the real POST and redirect intact, but simulate a truncated body
  // arriving over the network. Route interception skips some redirects,
  // so corrupt only the final Response.text() used by the AJAX renderer.
  await page.evaluate(() => {
    const realFetch = window.fetch.bind(window);
    window.__qaInjected = false;
    window.fetch = async (...args) => {
      const response = await realFetch(...args);
      if (!window.__qaInjected && String(args[0]).includes('/quiz/answer')
        && new URL(response.url).pathname === '/quiz' && response.ok) {
        window.__qaInjected = true;
        response.text = async () => '<!doctype html><html><head><title>Damaged response</title></head><body>'
          + '<section class="quiz-container"><div class="quiz-headline">'
          + '<h1 id="question-heading">Unexpected partial update</h1>'
          + '</div><div class="answer-block"></div></section></body></html>';
      }
      return response;
    };
  });

  await page.locator('.answer-tile').first().click();
  await page.waitForFunction(() => document.querySelector('#save-status')
    ?.textContent.includes('Не удалось сохранить ответ'), null, { timeout: 15000 });

  if (!await page.evaluate(() => window.__qaInjected)) throw new Error('AJAX response fault was not exercised');
  if ((await page.locator('.quiz-counter strong').textContent()).trim() !== '1') {
    throw new Error('Partial response advanced the visible question');
  }
  if ((await page.locator('#question-heading').textContent()) !== initialHeading) {
    throw new Error('Partial response corrupted the visible question');
  }
  if (await page.locator('.question-board').count() !== 1
    || await page.locator('.answer-tile input:enabled').count() !== 6) {
    throw new Error('Failed AJAX response left incomplete or disabled controls');
  }

  // The retry uses a stale question ID and must reload the authoritative
  // server state without creating duplicate or inconsistent answers.
  await page.locator('button[name="navigation"][value="next"]').click();
  await page.waitForFunction(() => document.querySelector('.quiz-counter strong')
    ?.textContent?.trim() === '2', null, { timeout: 15000 });

  if (errors.length) throw new Error('Browser JS errors: ' + errors.join('; '));
  console.log('QUIZ_AJAX_ATOMICITY_PASS: partial document rejected, retry synced to question 2');
} finally {
  await browser.close();
}
