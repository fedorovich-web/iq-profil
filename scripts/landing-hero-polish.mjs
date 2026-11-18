import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const browser = await chromium.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless:true });
const out={checks:[],screenshots:[]};
function check(ok,what,detail){out.checks.push({what,pass:Boolean(ok),detail}); if(!ok) throw Error(what+' '+JSON.stringify(detail));}
try{
 for(const width of [1440,1024,768,390,360]){
  const page=await browser.newPage({viewport:{width,height:900}});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8123/');
  await page.waitForLoadState('networkidle');
  const x=await page.evaluate(()=>{
    const rect=(sel)=>{const el=document.querySelector(sel);if(!el)return null;const b=el.getBoundingClientRect();return {x:b.x,y:b.y,width:b.width,height:b.height}};
    return {
      logoTag:document.querySelector('.topbar .brand')?.tagName,
      logoLoaded:document.querySelector('.topbar .original-brand')?.naturalWidth>0,
      brand:rect('.topbar .brand'),
      logo:rect('.topbar .original-brand'),
      hero:rect('.hero'),
      heroCopy:rect('.hero-copy'),
      heroVisual:rect('.hero-visual'),
      heroTransform:getComputedStyle(document.querySelector('.hero-visual')).transform,
      footerLogoTag:document.querySelector('.footer-brand')?.tagName,
      scrollWidth:document.documentElement.scrollWidth,windowWidth:innerWidth
    };
  });
  check(x.logoTag==='SPAN','homepage header logo is not a link '+width,x);
  check(x.footerLogoTag==='SPAN','homepage footer logo is not a link '+width,x);
  check(x.logoLoaded,'logo loads '+width,x);
  check(x.scrollWidth<=x.windowWidth+1,'no horizontal overflow '+width,x);
  check(x.logo.width<=226,'logo reduced to <=225px '+width,x.logo);
  check(errors.length===0,'no JS errors '+width,errors);
  if(width===1440) check(x.heroTransform!=='none','desktop illustration moved upward',x.heroTransform);
  if(width===390) check(x.heroTransform==='none','mobile illustration remains unshifted',x.heroTransform);
  if([1440,390].includes(width)){
   await page.screenshot({path:'docs/screenshots/landing-hero-polish-'+width+'.png',fullPage:false});
   out.screenshots.push('docs/screenshots/landing-hero-polish-'+width+'.png');
  }
  console.log('LAYOUT',width,JSON.stringify(x));
  await page.goto('http://127.0.0.1:8123/login');
  check(await page.locator('.topbar .brand').count()===0,'login uses standalone auth layout '+width);
  await page.goto('http://127.0.0.1:8123/privacy');
  check((await page.locator('.topbar .brand').evaluate(el=>el.tagName))==='A','other pages retain logo home link '+width);
  await page.close();
 }
 out.status='passed';
} catch(error){out.status='failed';out.failure=String(error);process.exitCode=1}
finally{
 writeFileSync('docs/landing-hero-polish-report.json',JSON.stringify(out,null,2));
 console.log('SUMMARY',JSON.stringify({status:out.status,checks:out.checks.length,passed:out.checks.filter(x=>x.pass).length,failure:out.failure??null}));
 await browser.close();
}