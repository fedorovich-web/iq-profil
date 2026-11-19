import { chromium } from 'playwright';
import { mkdirSync,writeFileSync } from 'node:fs';
const root=process.env.QA_BASE_URL || 'http://127.0.0.1:8123';
const out='storage/app/qa/mobile-layout';
mkdirSync(out,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'});
const results=[];
try{
 for(const width of [320,360,375,390,430,690,768,1024,1100,1280,1440]){
  const page=await browser.newPage({viewport:{width,height:844},deviceScaleFactor:1});
  const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.goto(root,{waitUntil:'networkidle'});
  const data=await page.evaluate(()=>{
   const cta=document.querySelector('.bottom-cta');
   const copy=cta.querySelector('.bottom-cta-copy').getBoundingClientRect();
   const description=cta.querySelector('p').getBoundingClientRect();
   const button=cta.querySelector('form').getBoundingClientRect();
   const header=cta.querySelector('h2').getBoundingClientRect();
   const lineDetails=[...cta.querySelectorAll('.bottom-cta-description-line')].map(e=>{
     const st=getComputedStyle(e),r=e.getBoundingClientRect();
     return {display:st.display,width:r.width,height:r.height,lines:Math.round(r.height/parseFloat(st.lineHeight))};
   });
   const crosses=!(copy.right < button.left-1 || copy.bottom < button.top-1 || button.bottom < copy.top-1 || button.right < copy.left-1);
   const textCrosses=!(description.right < button.left-1 || description.bottom < button.top-1 || button.bottom < description.top-1 || button.right < description.left-1);
   const inCard=r=>r.left>=cta.getBoundingClientRect().left-2 && r.right<=cta.getBoundingClientRect().right+2;
   return {overflow:document.documentElement.scrollWidth-innerWidth,ctaContained:inCard(copy)&&inCard(button)&&inCard(header),contentButtonOverlap:crosses,descriptionButtonOverlap:textCrosses,lines:lineDetails,buttonWidth:button.width,ctaWidth:cta.getBoundingClientRect().width};
  });
  const result={width,...data,errors};
  results.push(result);
  if(result.overflow>2||!result.ctaContained||result.contentButtonOverlap||result.descriptionButtonOverlap||errors.length)throw Error('Mobile/CTA layout defect: '+JSON.stringify(result));
  if(width>=1280&&data.lines.some(l=>l.lines!==1||l.display!=='block'))throw Error('Description should be exactly two lines on desktop: '+JSON.stringify(result));
  if([320,390,768,1440].includes(width))await page.locator('.bottom-cta').screenshot({path:out+'/cta-final-'+width+'.png'});
  console.log('PASS '+width+'px '+JSON.stringify({lines:data.lines,overflow:data.overflow}));
  await page.close();
 }
 console.log(JSON.stringify({status:'passed',widths:results.length},null,2));
}catch(err){console.error(err.stack||String(err));process.exitCode=1}
finally{writeFileSync(out+'/cta-audit.json',JSON.stringify(results,null,2));await browser.close();}
