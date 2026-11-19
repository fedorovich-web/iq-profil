import { chromium } from 'playwright';
import { mkdirSync, existsSync } from 'node:fs';
import { resolve, join } from 'node:path';

const base = 'http://127.0.0.1:8124';
const qaDir = resolve('storage/app/qa');
const profile = resolve('storage/app/qa/session-cookie-browser-20261119');
if (existsSync(profile)) throw new Error('Refusing to reuse an old QA browser profile');
mkdirSync(qaDir, {recursive: true});
const browserOptions = {
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  headless: true,
  viewport: {width:390, height:844},
  acceptDownloads: true,
};
const assert = (ok, msg) => {if(!ok) throw new Error(msg)};
let context;
let page;
const errors = [];
const results = [];
async function openBrowser() {
  context = await chromium.launchPersistentContext(profile, browserOptions);
  page = await context.newPage();
  page.on('pageerror', error=>errors.push(error.message));
}
async function navigate(path) {
  await page.goto(base+path,{waitUntil:'networkidle'});
}
async function startTest() {
  await navigate('/');
  await page.locator('form[action$="/attempts"]').first().locator('button[type="submit"]').click();
  await page.waitForURL(/\/(quiz|paywall\/[^/]+)$/,{timeout:15000});
  return new URL(page.url()).pathname;
}
async function measure(label) {
  const data=await page.evaluate(()=>({
    overflow:document.documentElement.scrollWidth - innerWidth,
    title:document.title,
    missing:[...document.querySelectorAll('img')].filter(img=>img.complete&&!img.naturalWidth).length
  }));
  assert(data.overflow<=2,'Horizontal overflow '+label+': '+JSON.stringify(data));
  assert(data.missing===0,'Broken images '+label+': '+JSON.stringify(data));
  results.push({step:label,url:new URL(page.url()).pathname});
}
async function cookieCheck() {
  const cookies=await context.cookies(base);
  const session=cookies.find(c=>c.name.endsWith('-session')||c.name.includes('session'));
  const remember=cookies.find(c=>c.name.startsWith('remember_web_'));
  assert(session,'No browser session cookie');
  assert(remember,'No remember-me cookie');
  assert(session.httpOnly && remember.httpOnly,'Authentication cookie is not HttpOnly');
  assert(session.sameSite==='Lax' && remember.sameSite==='Lax','SameSite policy must be Lax');
  const now=Date.now()/1000;
  const sDays=(session.expires-now)/86400;
  const rDays=(remember.expires-now)/86400;
  assert(sDays>6.8&&sDays<7.2,'Session expiry must be ~7 days, got '+sDays);
  assert(rDays>29.8&&rDays<30.2,'Remember expiry must be ~30 days, got '+rDays);
  results.push({step:'cookie-attributes',sessionDays:Math.round(sDays),rememberDays:Math.round(rDays),httpOnly:true,sameSite:'Lax'});
}
try {
  await openBrowser();
  await navigate('/register');
  await page.locator('input[name="email"]').fill('session-qa-20261119@example.test');
  await page.locator('input[name="password"]').fill('StrongPassword7788');
  await page.locator('input[name="password_confirmation"]').fill('StrongPassword7788');
  await page.locator('input[name="personal_data_consent"]').check();
  await page.locator('input[name="terms_acceptance"]').check();
  await page.locator('button[type="submit"]').click();
  await page.waitForURL('**/account');
  await measure('register-logged-in');
  await cookieCheck();

  await context.close();
  await openBrowser();
  await navigate('/account');
  assert(new URL(page.url()).pathname==='/account','Login did not survive browser restart');
  await measure('persistent-account-after-browser-restart');

  assert(await startTest()==='/quiz','First attempt must start');
  await page.locator('.answer-grid input').first().check({force:true});
  await page.waitForFunction(()=>document.querySelector('.quiz-counter strong')?.textContent.trim()==='2');

  assert(await startTest()==='/quiz','Repeated start must resume the quiz');
  assert((await page.locator('.quiz-counter strong').textContent()).trim()==='2','Attempt progress was reset');
  await measure('resume-existing-unfinished-attempt');

  for(let position=2;position<=25;position++){
    await page.locator('.answer-grid input').nth((position*2)%6).check({force:true});
    if(position===25){await page.waitForURL('**/paywall/**');}
    else await page.waitForFunction(p=>document.querySelector('.quiz-counter strong')?.textContent.trim()===String(p+1),position);
  }
  const unpaidPath=new URL(page.url()).pathname;
  const attemptId=unpaidPath.split('/').pop();
  assert(unpaidPath.startsWith('/paywall/'),'Quiz did not end at paywall');

  const secondAttempt=await startTest();
  assert(secondAttempt===unpaidPath,'Unpaid attempt was not gated; got '+secondAttempt);
  await page.getByText('Предыдущая попытка завершена.',{exact:false}).waitFor();
  await page.screenshot({path:join(qaDir,'session-gate-unpaid-390.png'),fullPage:true});
  await measure('blocked-duplicate-start-unpaid');

  await context.close();
  await openBrowser();
  await navigate('/account');
  assert(new URL(page.url()).pathname==='/account','User was logged out after second browser restart');
  assert(await startTest()===unpaidPath,'Unpaid gate lost after browser restart');
  await measure('unpaid-block-persists-after-restart');

  await page.locator('a[href*="/checkout/"]').click();
  await page.waitForURL('**/checkout/**');
  await page.locator('button:has-text("Подтвердить без списания")').click();
  await page.waitForURL('**/result/**');
  assert(new URL(page.url()).pathname==='/result/'+attemptId,'Wrong result after demo payment');
  await measure('demo-payment-opened-report');
  assert(await startTest()==='/quiz','New attempt did not unlock after payment');
  await measure('new-attempt-after-payment');

  await navigate('/account');
  await page.locator('form[action$="/logout"]').first().locator('button[type="submit"]').click();
  await page.waitForURL(base+'/');
  await context.close();
  await openBrowser();
  await navigate('/account');
  assert(new URL(page.url()).pathname==='/login','Logout must invalidate persistent login');
  await measure('logout-still-effective-after-restart');

  await navigate('/login');
  const checkbox=page.locator('input[type="checkbox"][name="remember"]');
  assert(await checkbox.isChecked(),'Remember-me checkbox should be enabled by default');
  await checkbox.uncheck();
  await page.locator('input[name="email"]').fill('session-qa-20261119@example.test');
  await page.locator('input[name="password"]').fill('StrongPassword7788');
  await page.locator('button[type="submit"]').click();
  await page.waitForURL('**/account');
  let cookies=await context.cookies(base);
  assert(!cookies.some(c=>c.name.startsWith('remember_web_')),'Unchecked remember-me still issued long-lived cookie');
  await measure('remember-disabled');

  await page.locator('form[action$="/logout"]').first().locator('button[type="submit"]').click();
  await page.waitForURL(base+'/');
  await navigate('/login');
  assert(await page.locator('input[name="remember"][type="checkbox"]').isChecked(),'Remember checkbox default missing');
  await page.locator('input[name="email"]').fill('session-qa-20261119@example.test');
  await page.locator('input[name="password"]').fill('StrongPassword7788');
  await page.locator('button[type="submit"]').click();
  await page.waitForURL('**/account');
  await cookieCheck();
  await measure('remember-enabled-by-default');
  await page.screenshot({path:join(qaDir,'session-gate-account-390.png'),fullPage:true});

  assert(errors.length===0,'Unexpected JavaScript errors: '+errors.join(';'));
  console.log(JSON.stringify({status:'passed',results,jsErrors:errors},null,2));
} catch(error) {
  console.error(error.stack||String(error));
  process.exitCode=1;
} finally {
  if(context) await context.close();
}
