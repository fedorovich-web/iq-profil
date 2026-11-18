import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { join, resolve } from 'node:path';
const dir = resolve('storage/app/qa');
const browser = await chromium.launch({headless:true, executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'});
for (let index=1; index<=5; index++) {
  const page=await browser.newPage({viewport:{width:1120,height:1000},deviceScaleFactor:1});
  await page.goto(pathToFileURL(join(dir,'graphic-'+index+'.html')).href, {waitUntil:'load'});
  const summary = await page.evaluate(()=>({
    matrices: document.querySelectorAll('.card').length,
    svgs: document.querySelectorAll('svg').length,
    overflow: document.documentElement.scrollWidth-innerWidth,
    height:document.documentElement.scrollHeight,
  }));
  await page.screenshot({path:join(dir,'graphic-'+index+'.png'),fullPage:true});
  console.log(JSON.stringify({index,...summary}));
  await page.close();
}
await browser.close();
