import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const root = 'http://127.0.0.1:8124';
const output = 'storage/app/qa/portal';
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  headless: true,
});
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
const jsErrors = [];
const records = [];
page.on('pageerror', e => jsErrors.push(e.message));
const assert = (ok, msg) => { if (!ok) throw new Error(msg); };
const goto = async path => {
  const response = await page.goto(root + path, { waitUntil: 'networkidle' });
  assert(response?.status() === 200, 'HTTP ' + response?.status() + ' at ' + path);
};
const check = async (name, expected) => {
  const data = await page.evaluate(() => ({
    width: innerWidth,
    overflow: document.documentElement.scrollWidth - innerWidth,
    broken: [...document.querySelectorAll('img')].filter(img => img.complete && !img.naturalWidth).map(img => img.src),
    headings: [...document.querySelectorAll('h1')].map(h => h.textContent.trim()),
    status: document.querySelector('.subscription-panel .portal-tag')?.textContent.trim() ||
      document.querySelector('.portal-full .portal-tag')?.textContent.trim(),
    geometry: (() => {
      const rect = selector => document.querySelector(selector)?.getBoundingClientRect();
      const price=rect('.subscription-panel .sub-price');
      const tag=rect('.subscription-panel .portal-tag');
      const box=rect('.portal-recommendation');
      const button=rect('.portal-recommendation button');
      return {
        tagOverlapsPrice: !!(price && tag && price.top < tag.bottom && tag.top < price.bottom && price.left < tag.right && tag.left < price.right),
        recommendationButtonOutside: !!(box && button && (button.right > box.right + 1 || button.left < box.left - 1)),
      };
    })(),
  }));
  assert(data.overflow <= 2, 'Overflow '+name+': '+JSON.stringify(data));
  assert(data.broken.length === 0, 'Broken images '+name+': '+JSON.stringify(data.broken));
  assert(!data.geometry.tagOverlapsPrice, 'Price/tag overlap '+name+': '+JSON.stringify(data.geometry));
  assert(!data.geometry.recommendationButtonOutside, 'Recommendation CTA overflow '+name+': '+JSON.stringify(data.geometry));
  if (expected) assert(data.status?.includes(expected), 'Subscription status '+name+': '+JSON.stringify(data));
  records.push({ name, ...data });
};
try {
  await goto('/register');
  await page.locator('input[name="email"]').fill('qa-portal-20261118-v2@example.test');
  await page.locator('input[name="password"]').fill('TestSecure12345');
  await page.locator('input[name="password_confirmation"]').fill('TestSecure12345');
  await page.locator('input[name="personal_data_consent"]').check();
  await page.locator('input[name="terms_acceptance"]').check();
  await page.locator('button[type="submit"]').click();
  await page.waitForURL('**/account');
  await check('account-initial', 'Нет активных');

  for (const width of [320, 390, 768, 1440]) {
    await page.setViewportSize({ width, height: 844 });
    await goto('/account');
    await check('account-before-' + width, 'Нет активных');
    if ([320, 390, 1440].includes(width)) {
      await page.screenshot({ path: output+'/account-before-'+width+'.png', fullPage: true });
    }
    await goto('/account?section=subscription');
    await check('subscription-before-' + width, 'Нет активных');
  }

  await page.setViewportSize({width:390,height:844});
  await goto('/account');
  await page.locator('form[action$="/attempts"] button').first().click();
  await page.waitForURL('**/quiz');
  for (let number=1; number<=25; number++) {
    assert((await page.locator('.quiz-counter strong').textContent()).trim()===String(number), 'Wrong question '+number);
    await page.locator('.answer-grid input').nth((number*3 + 2)%6).check({force:true});
    if(number<25){
      await page.waitForFunction(previous => document.querySelector('.quiz-counter strong')?.textContent.trim()===String(previous+1),number);
    } else await page.waitForURL('**/paywall/**');
  }
  const uuid=page.url().split('/').pop();
  assert(uuid?.length===36, 'No completed attempt UUID');

  await page.locator('a[href*="/checkout/"]').click();
  await page.waitForURL('**/checkout/**');
  await page.locator('button:has-text("Подтвердить без списания")').click();
  await page.waitForURL('**/result/**');
  await goto('/paywall/' + uuid);
  assert(page.url().endsWith('/result/'+uuid), 'Paid attempt must skip paywall');
  await goto('/checkout/' + uuid);
  assert(page.url().endsWith('/result/'+uuid), 'Paid attempt must skip checkout');
  await goto('/attempts/' + uuid + '/resume');
  assert(page.url().endsWith('/result/'+uuid), 'Paid resume must open report');

  for (const width of [320, 390, 768, 1440]) {
    await page.setViewportSize({ width, height: 844 });
    await goto('/account');
    await check('account-active-' + width, 'Активное');
    if ([320,390,1440].includes(width)) {
      await page.screenshot({ path: output+'/account-active-'+width+'.png', fullPage: true });
    }
    await goto('/account?section=subscription');
    await check('subscription-active-' + width, 'Активна');
    if ([320,390,1440].includes(width)) {
      await page.screenshot({ path: output+'/subscription-active-'+width+'.png', fullPage: true });
    }
    await goto('/account?section=reports');
    await check('reports-' + width);
    if (width === 390) await page.screenshot({ path: output+'/reports-'+width+'.png', fullPage: true });
    await goto('/account?section=history');
    await check('history-' + width);
    await goto('/account?section=profile');
    await check('profile-' + width);
    await goto('/account?section=security');
    await check('security-' + width);
    await goto('/account?section=support');
    await check('support-' + width);
  }

  const csv=await page.request.get(root+'/account/export/'+uuid);
  assert(csv.ok(),'CSV export HTTP status '+csv.status());
  const content=await csv.text();
  assert(content.includes('Правильных решений') && !content.includes('correct_code'), 'CSV export wrong structure');

  await goto('/account?section=subscription');
  await page.locator('a:has-text("Отменить демо-продление")').click();
  await page.waitForURL('**/subscription/cancel');
  await page.locator('input[name="confirm_cancel"]').check();
  await page.locator('button:has-text("Подтвердить отмену")').click();
  await page.waitForURL('**/subscription/cancel');
  await goto('/account');
  await check('account-cancelled','Нет активных');
  await goto('/account?section=subscription');
  await check('subscription-cancelled','Нет активных');
  await goto('/result/'+uuid);
  await check('result-after-cancel');
  assert(jsErrors.length===0, 'JS errors: '+jsErrors.join('; '));
  console.log(JSON.stringify({status:'passed',checks:records.length,report:uuid,jsErrors,summary:records.filter(r=>r.name.includes('subscription'))},null,2));
} catch(error) {
  console.error(error.stack||String(error));
  process.exitCode=1;
} finally {
  await browser.close();
}
