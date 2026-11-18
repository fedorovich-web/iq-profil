import { chromium } from 'playwright';

const browser = await chromium.launch({
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  headless: true,
});
const checks = [];
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
const errors = [];
page.on('pageerror', error => errors.push(error.message));
try {
  await page.goto('http://127.0.0.1:8123/', { waitUntil: 'networkidle' });
  await page.locator('form[action*="/attempts"]').first().locator('button[type="submit"]').click();
  await page.waitForURL('**/quiz');
  for (let position = 1; position <= 25; position++) {
    await page.waitForFunction(expected => document.querySelector('.quiz-counter strong')?.textContent?.trim() === String(expected), position);
    const info = await page.evaluate(() => {
      const inputs = [...document.querySelectorAll('.answer-grid input[name="answer_option_id"]')];
      return {
        count: inputs.length,
        svgs: [...document.querySelectorAll('.answer-grid svg')].map(svg => svg.outerHTML),
        questionSVG: document.querySelectorAll('.question-board svg').length,
        markup: document.querySelector('.quiz-container')?.outerHTML || '',
        overflow: document.documentElement.scrollWidth - innerWidth,
      };
    });
    if (info.count !== 6 || info.questionSVG !== 8 || new Set(info.svgs).size !== 6 || info.overflow > 2 || /is_correct|correct_code|correct_value/.test(info.markup)) {
      throw new Error('Quiz rendering failed at question ' + position + ': ' + JSON.stringify({ count: info.count, svgs: new Set(info.svgs).size, questionSVG: info.questionSVG, overflow: info.overflow }));
    }
    checks.push({ question: position, uniqueAnswers: 6, matrixCells: 9 });
    if ([1, 13, 25].includes(position)) {
      await page.screenshot({ path: `docs/quiz-mobile-question-${position}.png`, fullPage: true });
    }
    await page.locator('.answer-grid input').nth((position * 3 + 2) % 6).check({ force: true });
    if (position < 25) await page.waitForFunction(previous => document.querySelector('.quiz-counter strong')?.textContent?.trim() === String(previous + 1), position, { timeout: 9000 });
    else await page.waitForURL('**/paywall/**');
  }

  await page.screenshot({ path: 'docs/paywall-icons-mobile-qa.png', fullPage: true });
  const paymentUrl = new URL(page.url());
  const uuid = paymentUrl.pathname.split('/').pop();
  if (!uuid || uuid.length < 30) throw new Error('Attempt UUID not found');
  await page.locator('a[href*="/checkout/"]').click();
  await page.waitForURL('**/checkout/**');
  await page.screenshot({ path: 'docs/checkout-icons-mobile-qa.png', fullPage: true });
  await page.locator('button:has-text("Подтвердить демо-оплату")').click();
  await page.waitForURL('**/result/**');
  const report = await page.evaluate(() => ({
    headline: document.querySelector('#result-title')?.textContent?.trim(),
    hasCorrectCodes: /correct_code|Правильно:|Ваш ответ:|Подробный разбор/.test(document.body.innerText),
    overflow: document.documentElement.scrollWidth - innerWidth,
    categories: document.querySelectorAll('.category-result-card').length,
    progress: document.querySelector('.chart-track')?.getAttribute('aria-valuenow'),
  }));
  await page.screenshot({ path: 'docs/new-quiz-result-mobile.png', fullPage: true });
  if (report.headline !== 'Ваш результат' || report.hasCorrectCodes || report.overflow > 2 || report.categories !== 5) {
    throw new Error('Result rendering failed: ' + JSON.stringify(report));
  }
  console.log(JSON.stringify({ status: 'passed', steps: checks.length, uuid, report, pageErrors: errors }, null, 2));
  if (errors.length) process.exitCode = 1;
} catch (error) {
  console.error(error.stack || error.message);
  process.exitCode = 1;
} finally {
  await browser.close();
}
