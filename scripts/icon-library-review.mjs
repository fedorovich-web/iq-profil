import { chromium } from 'playwright';
const groups={
 client:['calendar','download','history','home','layers','logout','notifications','profile','reports','security','settings','support'],
 admin:['add-question','alerts','analytics','audit-log','demo-transactions','edit','export','filter','lock','overview','question-bank','refund-demo','search','settings','subscription','test-attempts','users','view']
};
const browser=await chromium.launch({headless:true,executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'});
const page=await browser.newPage({viewport:{width:1180,height:900},deviceScaleFactor:1});
await page.goto('http://127.0.0.1:8123/');
await page.setContent('<style>*{box-sizing:border-box}body{font-family:Arial;background:#eef4ff;padding:22px}.grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px}.item{background:#fff;padding:12px;border-radius:12px;text-align:center;font-size:12px;height:120px}.item img{display:block;width:66px;height:66px;object-fit:contain;margin:auto auto 8px}.item span{display:block}h2{margin:8px 0 14px}</style>'+Object.entries(groups).map(([g,list])=>'<h2>'+g+'</h2><div class=grid>'+list.map(n=>'<div class=item><img src="/assets/icons/'+g+'/'+n+'.avif"><span>'+n+'</span></div>').join('')+'</div>').join(''));
await page.waitForTimeout(450);
console.log('broken images',await page.locator('img').evaluateAll(imgs=>imgs.filter(i=>!i.complete||!i.naturalWidth).map(i=>i.src)));
await page.screenshot({path:'docs/icon-library-contact-sheet.png',fullPage:true});
await browser.close();