import {chromium} from 'playwright';
import {mkdirSync,writeFileSync} from 'node:fs';
const url=process.env.QA_BASE_URL || 'http://127.0.0.1:8124',output='storage/app/qa/mobile-layout';
const qaEmail=process.env.QA_ADMIN_EMAIL,qaPassword=process.env.QA_ADMIN_PASSWORD;
if(!qaEmail||!qaPassword)throw Error('Set QA_ADMIN_EMAIL and QA_ADMIN_PASSWORD for an administrator in the isolated QA database.');
mkdirSync(output,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'});
const page=await browser.newPage({viewport:{width:390,height:844}});
const logs=[],errors=[];
page.on('pageerror',e=>errors.push(e.message));
try{
 await page.goto(url+'/login',{waitUntil:'networkidle'});
 await page.locator('[name=email]').fill(qaEmail);
 await page.locator('[name=password]').fill(qaPassword);
 await page.locator('button[type=submit]').first().click();
 await page.waitForURL('**/admin');
 for(const section of ['overview','users','attempts','transactions','reports','audit']){
  for(const width of [320,360,390,430,768]){
   await page.setViewportSize({width,height:844});
   const path='/admin?section='+section;
   const r=await page.goto(url+path,{waitUntil:'networkidle'});
   if(r?.status()!==200)throw Error('Admin route error '+section+': '+r?.status());
   const data=await page.evaluate(()=>{
    const bad=[];
    for(const e of document.querySelectorAll('.portal-main h1,.portal-panel h2,.admin-stat,.admin-head,.admin-report-metrics,.admin-report-metrics>div,.admin-filter button,.admin-filter input,.portal-panel-heading,.portal-panel-heading a')){
     if(!e.getClientRects().length)continue;
     const r=e.getBoundingClientRect();
     if(r.left<-3||r.right>innerWidth+3)bad.push({selector:e.tagName.toLowerCase()+'.'+e.className,left:Math.round(r.left),right:Math.round(r.right),text:e.textContent.trim().slice(0,45)});
    }
    const internal=[...document.querySelectorAll('.portal-table-wrap')].map(e=>({client:e.clientWidth,scroll:e.scrollWidth}));
    return {overflow:document.documentElement.scrollWidth-innerWidth,bad,internal};
   });
   if(data.overflow>2||data.bad.length)console.log('ISSUE '+JSON.stringify({section,width,data}));
   else console.log('PASS '+section+' '+width);
   logs.push({section,width,...data});
   if(width===320||width===390)await page.screenshot({path:output+'/admin-'+section+'-'+width+'.png',fullPage:true});
  }
 }
 if(logs.some(l=>l.overflow>2||l.bad.length)||errors.length)throw Error('Admin overflow or clipped content found');
 console.log(JSON.stringify({status:'passed',screens:logs.length},null,2));
}catch(e){console.error(e.stack||String(e));process.exitCode=1}
finally{writeFileSync(output+'/admin-audit.json',JSON.stringify({logs,errors},null,2));await browser.close()}
