import { chromium } from 'playwright';
import { resolveBrowserOptions } from './browser-options.mjs';
import { resolve } from 'node:path';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const url = process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8123';
const profile = mkdtempSync(join(tmpdir(), 'iq-guest-qa-'));
const options = {
  ...resolveBrowserOptions(),
  viewport:{width:390,height:844},
};
let context;
let page;
const assert = (truth, message) => {if(!truth)throw new Error(message)};
async function restart() {
  if (context) await context.close();
  context=await chromium.launchPersistentContext(profile,options);
  page=await context.newPage();
}
async function start() {
  await page.goto(url,{waitUntil:'networkidle'});
  await page.locator('form[action$="/attempts"]').first().locator('button[type="submit"]').click();
  await page.waitForURL(/\/(quiz|paywall\/[^/]+)$/);
  return new URL(page.url()).pathname;
}
try {
  await restart();
  assert(await start()==='/quiz','Initial guest attempt did not open');
  await page.locator('.answer-grid input').first().check({force:true});
  await page.waitForFunction(()=>document.querySelector('.quiz-counter strong')?.textContent.trim()==='2');
  await restart();
  assert(await start()==='/quiz','Guest progress was lost after restarting the browser');
  assert((await page.locator('.quiz-counter strong').textContent()).trim()==='2','Guest resumed at wrong question');
  const total = Number((await page.locator('.quiz-counter').textContent()).match(/из\s+(\d+)/)?.[1]);
  assert(total >= 3, 'Question count not found');
  for(let i=2;i<=total;i++){
    await page.locator('.answer-grid input').nth(i%6).check({force:true});
    if(i===total)await page.waitForURL('**/paywall/**');
    else {
      await page.waitForFunction(p=>document.querySelector('.quiz-counter strong')?.textContent.trim()===String(p+1),i);
      const dialog=page.locator('[data-quiz-milestone]:visible');
      if(await dialog.count())await dialog.locator('.quiz-milestone-continue').click();
    }
  }
  const paywall=new URL(page.url()).pathname;
  assert((await start())===paywall,'Guest bypassed unpaid gate before browser restart');
  await restart();
  assert((await start())===paywall,'Guest bypassed unpaid gate after browser restart');
  const cookies=await context.cookies(url);
  const session=cookies.find(c=>c.name.endsWith('-session')||c.name.includes('session'));
  assert(session&&session.httpOnly&&session.sameSite==='Lax','Guest session cookie attributes invalid');
  assert(!cookies.some(c=>c.name.startsWith('remember_web_')),'Guest should not get a login recaller cookie');
  await page.locator('a[href*="/checkout/"]').click();
  await page.waitForURL('**/checkout/**');
  await page.locator('button:has-text("Подтвердить без списания")').click();
  await page.waitForURL('**/result/**');
  assert((await start())==='/quiz','Payment did not allow new guest attempt');
  console.log(JSON.stringify({status:'passed',guestProgressSurvivedRestart:true,unpaidGateSurvivedRestart:true,paidNewAttemptAllowed:true,cookieHttpOnly:true,cookieSameSite:'Lax'},null,2));
} catch(error) {
  console.error(error.stack||String(error));
  process.exitCode=1;
} finally {if(context)await context.close();rmSync(profile,{recursive:true,force:true});}
