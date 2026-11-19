import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
const url=process.env.QA_BASE_URL || 'http://127.0.0.1:8124',out='storage/app/qa/mobile-layout';
const qaEmail='mobile-audit-'+Date.now()+'@example.test';
const qaPassword=process.env.QA_TEST_PASSWORD || 'StrongPassword771188';
mkdirSync(out,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'});
const page=await browser.newPage({viewport:{width:390,height:844},deviceScaleFactor:1});
const logs=[], errors=[];
page.on('pageerror',e=>errors.push(e.message));
async function go(path) {
 const response=await page.goto(url+path,{waitUntil:'domcontentloaded'});
 if(response?.status()!==200)throw Error('HTTP '+response?.status()+' for '+path);
 await page.locator('body').waitFor({state:'visible'});
}
async function check(label,width,height=844){
 await page.setViewportSize({width,height});
 await page.waitForTimeout(60);
 const result=await page.evaluate(()=>{
  const viewport=innerWidth;
  const cards='.portal-panel,.account-hero,.auth-main,.checkout-card,.offer-card,.category-result-card,.result-score-hero,.answer-block,.question-board,.quiz-toolbar';
  const rect=r=>({l:Math.round(r.left),r:Math.round(r.right),t:Math.round(r.top),b:Math.round(r.bottom),w:Math.round(r.width)});
  const desc=e=>e.tagName.toLowerCase()+(e.id?'#'+e.id:'')+(typeof e.className==='string'&&e.className?'.'+e.className.trim().split(/\s+/).slice(0,2).join('.'):'');
  const defects=[];
  for(const el of document.querySelectorAll('button,a,h1,h2,h3,p,strong,summary,form,input')){
    if(el.closest('.skip-link,.mobile-site-menu,.hero-visual'))continue;
    const st=getComputedStyle(el);
    if(st.display==='none'||st.visibility==='hidden'||st.opacity==='0'||!el.getClientRects().length)continue;
    const r=el.getBoundingClientRect(); if(r.width<8||r.height<5)continue;
    if(r.left < -4 || r.right>viewport+4){defects.push({type:'viewport',name:desc(el),text:el.textContent?.trim()?.slice(0,60),rect:rect(r)});continue;}
    const card=el.closest(cards);
    if(card){
      const cr=card.getBoundingClientRect();
      if(r.left < cr.left-4 || r.right>cr.right+4){
        defects.push({type:'outside-card',name:desc(el),card:desc(card),rect:rect(r),cardRect:rect(cr),text:el.textContent?.trim()?.slice(0,55)});
      }
    }
  }
  const auth=document.querySelector('.auth-main');
  const authInfo=auth?{scrollHeight:auth.scrollHeight,clientHeight:auth.clientHeight,bodyScrollHeight:document.body.scrollHeight,viewportHeight:innerHeight,transform:getComputedStyle(auth).transform}:null;
  return {overflow:document.documentElement.scrollWidth-viewport,defects:defects.slice(0,15),auth:authInfo,broken:[...document.querySelectorAll('img')].filter(i=>i.complete&&!i.naturalWidth).map(i=>i.src)};
 });
 logs.push({label,width,height,...result});
 if(result.overflow>2||result.defects.length||result.broken.length)console.log('ISSUE '+JSON.stringify({label,width,height,...result}));
 else console.log('PASS '+label+' '+width+'x'+height);
 return result;
}
try{
 for(const route of ['/login','/register']){
  await go(route);
  for(const [width,height] of [[320,568],[320,640],[360,640],[390,844],[430,844]]){
   const data=await check(route,width,height);
   if(data.auth&&data.auth.scrollHeight-data.auth.clientHeight>4)throw Error('Form clipped vertically: '+route+' '+width+'x'+height);
   if(width===320&&height===568)await page.screenshot({path:out+'/auth-'+route.slice(1)+'-320x568.png',fullPage:true});
  }
 }
 await page.setViewportSize({width:390,height:844});
 await go('/register');
 await page.locator('input[name="email"]').fill(qaEmail);
 await page.locator('input[name="password"]').fill(qaPassword);
 await page.locator('input[name="password_confirmation"]').fill(qaPassword);
 await page.locator('input[name="personal_data_consent"]').check();
 await page.locator('input[name="terms_acceptance"]').check();
 await page.locator('button[type="submit"]').click();
 await page.waitForURL('**/account');
 for(const route of ['/account','/account?section=subscription','/account?section=history','/account?section=reports','/account?section=profile','/account?section=security','/account?section=support']){
  await go(route);
  for(const width of [320,360,375,390,430,768])await check(route,width);
  if(route==='/account')await page.screenshot({path:out+'/account-320.png',fullPage:true});
 }
 await page.setViewportSize({width:390,height:844});
 await go('/account');
 await page.locator('form[action$="/attempts"] button').first().click();
 await page.waitForURL('**/quiz');
 for(const width of [320,360,375,390,430,768])await check('/quiz',width);
 await page.setViewportSize({width:390,height:844});
 for(let n=1;n<=25;n++){
  await page.locator('.answer-grid input').nth(n%6).check({force:true});
  if(n<25)await page.waitForFunction(n=>Number(document.querySelector('.quiz-counter strong')?.textContent.trim())===n+1,n);
  else await page.waitForURL('**/paywall/**');
 }
 for(const width of [320,360,375,390,430,768]){
   await check('/paywall',width);
   if(width===320)await page.screenshot({path:out+'/paywall-320.png',fullPage:true});
 }
 await page.setViewportSize({width:390,height:844});
 await page.locator('a[href*="/checkout/"]').click();
 await page.waitForURL('**/checkout/**');
 for(const width of [320,360,375,390,430,768])await check('/checkout',width);
 await page.setViewportSize({width:390,height:844});
 await page.locator('button:has-text("Подтвердить без списания")').click();
 await page.waitForURL('**/result/**');
 for(const width of [320,360,375,390,430,768]){
  await check('/result',width);
  if(width===320)await page.screenshot({path:out+'/result-320.png',fullPage:true});
 }
 await go('/account');
 for(const width of [320,390,430])await check('/account-with-result',width);
 const critical=logs.filter(r=>r.overflow>2||r.defects.length||r.broken.length||r.auth?.scrollHeight>r.auth?.clientHeight+4);
 if(errors.length||critical.length)throw Error(JSON.stringify({critical,errors},null,2));
 console.log(JSON.stringify({status:'passed',screens:logs.length,errors},null,2));
}catch(e){console.error(e.stack||String(e));process.exitCode=1}
finally{writeFileSync(out+'/private-audit.json',JSON.stringify({logs,errors},null,2));await browser.close();}
