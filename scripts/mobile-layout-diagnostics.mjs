import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
const root=process.env.QA_BASE_URL || 'http://127.0.0.1:8123';
mkdirSync('storage/app/qa/mobile-layout',{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'});
const widths=[320,360,375,390,414,430,690,768,1024];
const pages=['/','/login','/register','/privacy','/offer','/subscription/cancel'];
const data=[];
for(const path of pages){
 for(const width of widths){
   const page=await browser.newPage({viewport:{width,height:820},deviceScaleFactor:1});
   const pageErrors=[];
   page.on('pageerror',e=>pageErrors.push(e.message));
   const res=await page.goto(root+path,{waitUntil:'networkidle'});
   const s=await page.evaluate(()=>{
     const ignored = e=>e.closest('.skip-link, .mobile-site-menu, .hero-picture, .hero-visual, .footer-art, .hero-illustration')!==null;
     const vis=e=>{const s=getComputedStyle(e);return s.display!=='none'&&s.visibility!=='hidden'&&parseFloat(s.opacity)!==0&&e.getClientRects().length>0};
     const rect=r=>({left:Math.round(r.left*10)/10,top:Math.round(r.top*10)/10,right:Math.round(r.right*10)/10,bottom:Math.round(r.bottom*10)/10,width:Math.round(r.width*10)/10,height:Math.round(r.height*10)/10});
     const sname=e=>e.tagName.toLowerCase()+(e.id?'#'+e.id:'')+(typeof e.className==='string'&&e.className?'.'+e.className.trim().split(/\s+/).slice(0,2).join('.'):'');
     const text=e=>e.textContent.trim().replace(/\s+/g,' ').slice(0,100);
     const clipped=[],overflow=[],edges=[];
     const all=[...document.body.querySelectorAll('*')];
     for(const e of all){
       if(!vis(e)||ignored(e))continue;
       const style=getComputedStyle(e);const r=e.getBoundingClientRect();
       if(['block','flex','grid','inline-flex','inline-grid'].some(x=>style.display===x)&&e.scrollWidth>e.clientWidth+4&&/hidden|clip/.test(style.overflowX)){
         if(e.clientWidth>20 && e.clientHeight>15)clipped.push({selector:sname(e),scroll:e.scrollWidth,client:e.clientWidth,side:style.overflowX,rect:rect(r),text:text(e)});
       }
       if(['h1','h2','h3','p','button','a','summary','label'].includes(e.tagName.toLowerCase())&&r.width>10&&r.height>7){
         if(r.left< -3||r.right>innerWidth+3)edges.push({selector:sname(e),rect:rect(r),text:text(e)});
         let parent=e.parentElement;
         for(let j=0;parent&&j<8;j++,parent=parent.parentElement){
           const ps=getComputedStyle(parent);
           if((['hidden','clip'].includes(ps.overflowX))&&vis(parent)){
             const pr=parent.getBoundingClientRect();
             if(r.left<pr.left-4||r.right>pr.right+4){
               clipped.push({selector:sname(e),by:sname(parent),rect:rect(r),clip:rect(pr),text:text(e)});break;
             }
           }
         }
       }
     }
     const cta=document.querySelector('.bottom-cta'),ctaText=cta?.querySelector('p');
     const lines=cta? [...cta.querySelectorAll('.bottom-cta-description-line')].map(e=>({height:e.getBoundingClientRect().height,numberOfTextLines:Math.round(e.getBoundingClientRect().height/parseFloat(getComputedStyle(e).lineHeight)),width:e.getBoundingClientRect().width})):null;
     const targetSelectors=['.hero','.hero h1','.hero-copy','.hero-actions','.hero-picture','.feature-grid','.feature-card','.test-grid','.steps-grid','.faq-list','.bottom-cta','.bottom-cta-copy','.bottom-cta h2','.bottom-cta p','.bottom-cta button','.footer','.auth-main'];
     const target=Object.fromEntries(targetSelectors.map(sel=>[sel,document.querySelector(sel)]).filter(([_,e])=>e&&vis(e)).map(([sel,e])=>[sel,rect(e.getBoundingClientRect())]));
     return {viewport:innerWidth,scroll:document.documentElement.scrollWidth,bodyScroll:document.body.scrollWidth,overflow:document.documentElement.scrollWidth-innerWidth,clipped:clipped.slice(0,25),edges:edges.slice(0,25),ctaLines:lines,target};
   });
   if(path==='/'&&[320,375,390,430,768].includes(width))await page.screenshot({path:'storage/app/qa/mobile-layout/landing-'+width+'.png',fullPage:true});
   if(path==='/'&&[320,390,768].includes(width))await page.locator('.bottom-cta').screenshot({path:'storage/app/qa/mobile-layout/cta-'+width+'.png'});
   data.push({path,width,http:res?.status(),errors:pageErrors,...s});
   console.log(JSON.stringify({path,width,overflow:s.overflow,clipped:s.clipped.slice(0,6),edges:s.edges.slice(0,6),ctaLines:s.ctaLines}));
   await page.close();
 }
}
await browser.close();
await import('node:fs').then(({writeFileSync})=>writeFileSync('storage/app/qa/mobile-layout/layout-audit.json',JSON.stringify(data,null,2)));
