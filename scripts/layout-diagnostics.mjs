import { chromium } from 'playwright';
const browser=await chromium.launch({executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',headless:true});
for (const width of [1440,1200,1024,900,768,600,390,360]) {
  const page=await browser.newPage({viewport:{width,height:844}});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8123');
  await page.waitForLoadState('networkidle');
  const info=await page.evaluate(()=>{
    const rect=(selector)=>{const e=document.querySelector(selector);if(!e)return null;const r=e.getBoundingClientRect();const s=getComputedStyle(e);return{x:Math.round(r.x),right:Math.round(r.right),width:Math.round(r.width),y:Math.round(r.y),h:Math.round(r.height),visible:s.display!=='none'&&s.visibility!=='hidden',z:s.zIndex};};
    const img=document.querySelector('.original-brand');
    return {
      bodyWidth:document.documentElement.scrollWidth,
      brand:rect('.brand'),logo:rect('.original-brand'),logoLoaded:img?img.complete&&img.naturalWidth>0:false,
      nav:rect('.navigation'),actions:rect('.header-actions'),burger:rect('.mobile-menu-toggle'),
      hero:rect('.hero'),cta:rect('.bottom-cta'),footer:rect('.footer'),
      ctaFooterGap:rect('.footer')?.y-rect('.bottom-cta')?.y-rect('.bottom-cta')?.h,
      cssSheet:document.querySelector('link[href*="app-"]')?.href,
    }
  });
  console.log('VIEWPORT',width,JSON.stringify(info),'JS_ERRORS',errors);
  if(width===390||width===768||width===1440) {
    await page.locator('.faq-list').scrollIntoViewIfNeeded();
    await page.locator('.faq-list summary').first().click();
    const s=page.locator('.faq-list summary').nth(1);
    const before=await s.evaluate(e=>({y:e.getBoundingClientRect().top,scroll:scrollY}));
    await s.click();
    const after=await s.evaluate(e=>({y:e.getBoundingClientRect().top,scroll:scrollY}));
    console.log('FAQ',width,JSON.stringify({before,after,delta:after.y-before.y,open:await page.locator('.faq-list details[open]').count()}));
  }
  await page.close();
}
await browser.close();