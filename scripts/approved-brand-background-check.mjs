import { chromium } from 'playwright';
const browser = await chromium.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: true });
for (const width of [1440, 390]) {
 const page=await browser.newPage({viewport:{width,height:900}});
 await page.goto('http://127.0.0.1:8123/');
 await page.waitForLoadState('networkidle');
 const assets=await page.evaluate(async()=>{
  const image=document.querySelector('.topbar .original-brand');
  const cta=document.querySelector('.bottom-cta');
  const bg=getComputedStyle(cta).backgroundImage;
  const res=await fetch('/assets/bg_converted.avif');
  const status=res.status;
  return {logoUrl:new URL(image.getAttribute('src'),document.baseURI).pathname,logoNaturalSize:[image.naturalWidth,image.naturalHeight],logoLoaded:image.complete&&image.naturalWidth>0,ctaBackground:bg,ctaBackgroundResponse:status,ctaBackgroundContentType:res.headers.get('content-type'),ctaHeight:Math.round(cta.getBoundingClientRect().height)}
 });
 console.log(width,JSON.stringify(assets));
 await page.locator('.bottom-cta').scrollIntoViewIfNeeded();
 await page.screenshot({path:'docs/screenshots/approved-brand-and-cta-'+width+'.png',fullPage:false});
 await page.close();
}
await browser.close();