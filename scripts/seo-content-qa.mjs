import { chromium } from 'playwright';
import { resolveBrowserOptions } from './browser-options.mjs';
import { mkdirSync } from 'node:fs';

const output = 'storage/app/qa';
mkdirSync(output, {recursive:true});
const browser = await chromium.launch({
  ...resolveBrowserOptions(),
});
const results = [];
const errors = [];
for (const width of [320,390,768,1440]) {
  const page = await browser.newPage({viewport:{width,height:850}});
  page.on('pageerror', e => errors.push({width, error:e.message}));
  await page.goto(process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8123/',{waitUntil:'networkidle'});
  const details = await page.evaluate(()=>{
    const h1=document.querySelector('.hero h1');
    const hero=document.querySelector('.hero');
    const picture=document.querySelector('.hero-visual');
    const graph=JSON.parse(document.querySelector('script[type="application/ld+json"]').textContent);
    const rect=e=>{const r=e.getBoundingClientRect();return {x:Math.round(r.x),y:Math.round(r.y),width:Math.round(r.width),height:Math.round(r.height)}};
    return {
      title:document.title,
      description:document.querySelector('meta[name="description"]')?.content,
      h1:h1?.innerText,
      headings:[...document.querySelectorAll('h1,h2,h3')].map(h=>h.textContent.trim()),
      faqCount:document.querySelectorAll('.faq-list details').length,
      structuredFaqCount:graph['@graph'].find(i=>i['@type']==='FAQPage')?.mainEntity?.length ?? 0,
      canonical:document.querySelector('link[rel="canonical"]')?.href,
      graphTypes:graph['@graph'].map(i=>i['@type']),
      graphContext:graph['@context'],
      titleCount:document.querySelectorAll('h1').length,
      viewport:innerWidth,
      overflow:document.documentElement.scrollWidth-innerWidth,
      hero:rect(hero), heading:rect(h1), illustration:rect(picture),
      brokenImages:[...document.querySelectorAll('img')].filter(i=>i.complete&&!i.naturalWidth).map(i=>i.src),
      faqTitles:[...document.querySelectorAll('.faq-list summary')].map(e=>e.textContent.trim()),
    };
  });
  if(details.faqCount<1||details.structuredFaqCount!==details.faqCount||details.titleCount!==1||details.overflow>2||details.brokenImages.length||
     details.graphContext!=='https://schema.org'||details.graphTypes.join(',')!=='WebSite,WebPage,FAQPage'||
     !details.h1.includes('Тест на IQ онлайн')||
     details.headings.some(h=>/IQ\.Профиль|\b25\b/.test(h))) {
    throw new Error('SEO/UX issue at '+width+': '+JSON.stringify(details));
  }
  await page.locator('.hero').screenshot({path:output+'/seo-hero-'+width+'.png'});
  await page.locator('#faq').screenshot({path:output+'/seo-faq-'+width+'.png'});
  const drift=[];
  for(const index of [0,3,Math.floor(details.faqCount/2),details.faqCount-1,2,0]) {
    const sample=await page.evaluate(async (index)=>{
      const summary=document.querySelectorAll('.faq-list summary')[index];
      summary.scrollIntoView({block:'center',behavior:'instant'});
      await new Promise(r=>setTimeout(r,70));
      const start=summary.getBoundingClientRect().top;
      const positions=[];
      summary.click();
      const since=performance.now();
      await new Promise(done=>{
        const step=()=>{
          positions.push(summary.getBoundingClientRect().top);
          if(performance.now()-since>420) done(); else requestAnimationFrame(step);
        };step();
      });
      return {index, drift:Math.max(...positions.map(y=>Math.abs(start-y))), open:document.querySelectorAll('.faq-list details[open]').length};
    },index);
    drift.push(sample);
  }
  if(drift.some(x=>x.drift>2||x.open>1)) throw new Error('FAQ jumped at '+width+': '+JSON.stringify(drift));
  results.push({width, ...details, maxFaqDrift:Math.max(...drift.map(x=>x.drift))});
  await page.close();
}
await browser.close();
console.log(JSON.stringify({results,errors},null,2));
if(errors.length)process.exitCode=1;
