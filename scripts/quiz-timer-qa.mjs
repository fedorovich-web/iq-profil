import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { resolveBrowserOptions } from './browser-options.mjs';

const base = process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8123';
const browser = await chromium.launch(resolveBrowserOptions());
const context = await browser.newContext();
const errors = [];
function attach(page) {
  page.on('pageerror', error => errors.push(error.message));
}
async function seconds(page) {
  const text = (await page.locator('#quiz-timer').textContent()).trim();
  assert.match(text, /^\d+:\d{2}\+?$/);
  const [minutes, rest] = text.split(':');
  return Number(minutes) * 60 + Number.parseInt(rest, 10);
}

try {
  let page = await context.newPage();
  attach(page);
  await page.goto(base);
  await page.locator('.hero-actions form button').first().click();
  await page.waitForURL(/\/quiz\/intake\/gender/);
  await page.locator('.intake-gender-choice').first().click();
  await page.locator('.intake-submit').click();
  await page.waitForURL(/\/quiz\/intake\/age/);
  await page.locator('.intake-age-choice').first().click();
  await page.locator('.intake-submit').click();
  await page.waitForURL(/\/quiz$/);
  const uuid = await page.locator('[name=attempt_uuid]').inputValue();
  assert.ok(await seconds(page) <= 2);

  let requests = 0;
  const count = () => requests++;
  page.on('request', count);
  await page.waitForTimeout(2200);
  page.off('request', count);
  const elapsed = await seconds(page);
  assert.ok(elapsed >= 2);
  assert.equal(requests, 0);

  // Exercise the visibility lifecycle deterministically in headless Chromium.
  await page.evaluate(() => {
    Object.defineProperty(document, 'hidden', { configurable: true, get: () => true });
    document.dispatchEvent(new Event('visibilitychange'));
  });
  const paused = await seconds(page);
  await page.waitForTimeout(1500);
  assert.equal(await seconds(page), paused);
  await page.evaluate(() => {
    delete document.hidden;
    document.dispatchEvent(new Event('visibilitychange'));
  });
  await page.waitForTimeout(1200);
  assert.ok(await seconds(page) > paused);

  const beforeReload = await seconds(page);
  await page.reload();
  assert.equal(await page.locator('[name=attempt_uuid]').inputValue(), uuid);
  assert.ok(await seconds(page) >= beforeReload);

  await page.locator('.answer-tile').first().click();
  await page.waitForFunction(() => Number(document.querySelector('.quiz-counter')?.textContent.match(/\d+/)?.[0]) === 2);
  const beforeClose = await seconds(page);
  await page.close();
  page = await context.newPage();
  attach(page);
  await page.goto(base + '/quiz');
  assert.equal(await page.locator('[name=attempt_uuid]').inputValue(), uuid);
  assert.equal(Number((await page.locator('.quiz-counter').textContent()).match(/\d+/)[0]), 2);
  assert.ok(await seconds(page) >= beforeClose);
  assert.deepEqual(errors, []);
  console.log('QUIZ_TIMER_PASS: start, tick, pause/resume, reload, close/reopen, persisted answers, zero idle requests');
} finally {
  await browser.close();
}
