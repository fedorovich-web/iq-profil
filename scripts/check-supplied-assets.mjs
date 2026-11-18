import { chromium } from 'playwright';
const browser = await chromium.launch({executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',headless:true});
const page = await browser.newPage();
for(const file of ['logo_converted.avif','bg_converted.avif']) {
  const url = 'http://127.0.0.1:8123/assets/'+file;
  const result = await page.evaluate(async url => {
    const img = new Image();
    img.src = url;
    try { await img.decode(); return {loaded:true,width:img.naturalWidth,height:img.naturalHeight,src:img.src}; }
    catch(e){return {loaded:false,error:String(e),src:img.src};}
  },url);
  console.log(JSON.stringify({file,...result}));
}
await browser.close();