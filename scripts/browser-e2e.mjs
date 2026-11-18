import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';

const base = 'http://127.0.0.1:8123';
mkdirSync('docs/screenshots', { recursive: true });

const browser = await chromium.launch({
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  headless: true,
});
const report = { tests: [], pageErrors: [], screenshots: [], checkedAt: new Date().toISOString() };
function check(condition, label) {
  report.tests.push({ label, passed: Boolean(condition) });
  if (!condition) throw new Error('FAIL: ' + label);
}
function captureErrors(page) {
  page.on('pageerror', error => report.pageErrors.push(error.message));
}
async function screenshot(page, name) {
  const path = 'docs/screenshots/' + name + '.png';
  await page.screenshot({ path, fullPage: true });
  report.screenshots.push(path);
}
async function overflow(page, label) {
  const data = await page.evaluate(() => ({ scroll: document.documentElement.scrollWidth, window: window.innerWidth }));
  check(data.scroll <= data.window + 1, label + ' no horizontal overflow: ' + JSON.stringify(data));
}
async function navigateAndTake(page, name) {
  await page.goto(base + '/');
  check((await page.title()).includes('IQ.Профиль'), name + ' landing title');
  await overflow(page, name + ' landing');
  await screenshot(page, name + '-landing');

  await page.locator('.hero-actions form button').click();
  check(new URL(page.url()).pathname === '/quiz', name + ' quiz opened');
  await overflow(page, name + ' quiz');
  await screenshot(page, name + '-quiz');

  const initialTimer = await page.locator('#quiz-timer').textContent();
  await page.locator('.answer-tile').first().click();
  await page.locator('button[name="navigation"][value="next"]').click();
  check((await page.locator('.quiz-counter').textContent()).includes('2'), name + ' advance to question 2');
  await page.locator('button[name="navigation"][value="back"]').click();
  check((await page.locator('.quiz-counter').textContent()).includes('1'), name + ' go back');
  await page.locator('.answer-tile').nth(1).click();
  await page.reload();
  check(await page.locator('.answer-tile input:checked').count() === 1, name + ' selected answer restored');
  check((await page.locator('.answer-tile input:checked').inputValue()) === (await page.locator('.answer-tile input').nth(1).inputValue()), name + ' revised answer retained');
  const nextTimer = await page.locator('#quiz-timer').textContent();
  check(initialTimer !== '' && nextTimer !== '', name + ' timer rendered');
  await page.locator('button[name="navigation"][value="next"]').click();

  for (let question = 2; question <= 25; question++) {
    check((await page.locator('.quiz-counter').textContent()).includes(String(question)), name + ' question ' + question);
    await page.locator('.answer-tile').first().click();
    await page.locator('button[name="navigation"][value="' + (question === 25 ? 'finish' : 'next') + '"]').click();
  }
  check(new URL(page.url()).pathname.startsWith('/paywall/'), name + ' paywall after 25');
  const uuid = new URL(page.url()).pathname.split('/')[2];
  await screenshot(page, name + '-paywall');
  await overflow(page, name + ' paywall');

  await page.goto(base + '/result/' + uuid);
  check(new URL(page.url()).pathname === '/paywall/' + uuid, name + ' result blocked before demo payment');
  await page.locator('.offer-card .btn').click();
  check(new URL(page.url()).pathname === '/checkout/' + uuid, name + ' demo checkout');
  await screenshot(page, name + '-checkout');
  await overflow(page, name + ' checkout');

  await page.locator('.checkout-card form button').click();
  check(new URL(page.url()).pathname === '/result/' + uuid, name + ' result after demo payment');
  check((await page.locator('.review-card').count()) === 25, name + ' individual explanations (25)');
  await screenshot(page, name + '-result');
  await overflow(page, name + ' result');

  await page.goto(base + '/checkout/' + uuid);
  await page.locator('.checkout-card form button').click();
  check(new URL(page.url()).pathname === '/result/' + uuid, name + ' repeated demo confirmation accepted');

  await page.locator('.result-actions form button').click();
  check(new URL(page.url()).pathname === '/quiz', name + ' new attempt opened');
  check((await page.locator('.quiz-counter').textContent()).includes('1'), name + ' new attempt starts at question one');

  return uuid;
}
try {
  const desktop = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const pageDesktop = await desktop.newPage(); captureErrors(pageDesktop);
  const desktopUuid = await navigateAndTake(pageDesktop, 'desktop');
  await desktop.close();

  const mobile = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const pageMobile = await mobile.newPage(); captureErrors(pageMobile);
  const mobileUuid = await navigateAndTake(pageMobile, 'mobile');
  check(mobileUuid !== desktopUuid, 'different users have different attempt IDs');
  await mobile.close();

  const strangerContext = await browser.newContext();
  const stranger = await strangerContext.newPage();
  const blocked = await stranger.goto(base + '/result/' + desktopUuid);
  check(blocked.status() === 404, 'unowned result URL returns 404');
  await strangerContext.close();
  check(report.pageErrors.length === 0, 'no browser JavaScript errors');
  report.status = 'passed';
} catch (error) {
  report.status = 'failed';
  report.failure = String(error);
  process.exitCode = 1;
} finally {
  writeFileSync('docs/browser-report.json', JSON.stringify(report, null, 2));
  console.log('BROWSER_REPORT', JSON.stringify({
    status: report.status,
    tests: report.tests.length,
    passed: report.tests.filter(test => test.passed).length,
    errors: report.pageErrors,
    failure: report.failure || null,
    screenshots: report.screenshots.length,
  }));
  await browser.close();
}
