import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';
const browser = await chromium.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: true });
const base='http://127.0.0.1:8123';
const report={checks:[],errors:[],screenshots:[]};
const check=(pass,label,detail=null)=>{report.checks.push({label,pass:!!pass,detail});if(!pass)throw Error(label+' '+JSON.stringify(detail));};
try {
 for(const width of [1440,768,390,360]){
  const context=await browser.newContext({viewport:{width,height:844},isMobile:width<=390,hasTouch:width<=390});
  const page=await context.newPage();
  page.on('pageerror',e=>report.errors.push(e.message));
  await page.goto(base);await page.waitForLoadState('networkidle');
  check(await page.locator('.test-card form').count()===1,'only first card starts test width '+width);
  check(await page.locator('.test-card-coming').count()===2,'two unavailable cards width '+width);
  check((await page.locator('.test-card h3').first().textContent()).trim()==='IQ-тест','test is named IQ-тест '+width);
  const dims=await page.evaluate(()=>({body:document.documentElement.scrollWidth,viewport:innerWidth}));
  check(dims.body<=dims.viewport+1,'no horizontal overflow width '+width,dims);

  if(width<=390){
    const toggle=page.locator('.mobile-menu-toggle');
    check(await toggle.isVisible(),'burger visible '+width);
    const b=await toggle.boundingBox(),l=await page.locator('.header-actions .nav-login').boundingBox();
    check(Math.abs((b.x-l.x-l.width)-15)<1.2,'login 15px left of burger '+width,{gap:b.x-l.x-l.width});
    check(l.x< b.x,'login to left of burger '+width);
    await toggle.click();
    await page.waitForTimeout(350);
    check(await toggle.getAttribute('aria-expanded')==='true','burger expands '+width);
    check(await page.locator('#mobile-site-menu').isVisible(),'menu displayed '+width);
    await page.keyboard.press('Escape');
    check(await toggle.getAttribute('aria-expanded')==='false','Escape closes burger '+width);
    await page.locator('.faq-list details').first().locator('summary').click();
    check(await page.locator('.faq-list details[open]').count()===1,'faq first opens '+width);
    const summary2=page.locator('.faq-list details').nth(1).locator('summary');
    const old=await summary2.evaluate(el=>el.getBoundingClientRect().top);
    await summary2.click();
    const newTop=await summary2.evaluate(el=>el.getBoundingClientRect().top);
    check(await page.locator('.faq-list details[open]').count()===1,'only one faq open '+width);
    check(Math.abs(newTop-old)<10,'faq click does not jump heading '+width,{old,newTop});
    await page.locator('.footer').scrollIntoViewIfNeeded();
    await page.locator('.footer-group').last().locator('summary').click();
    check(await page.locator('.footer-group[open]').count()===1,'footer accordion opens one mobile '+width);
    const linkCount=await page.locator('.footer-group').last().locator('.footer-links a').count();
    check(linkCount===4,'all legal links under Information '+width,{linkCount});
    await page.screenshot({path:'docs/screenshots/landing-corrected-'+width+'.png',fullPage:true});
  } else {
    if(width<=1100){
      check(await page.locator('.mobile-menu-toggle').isVisible(),'burger displayed on narrow tablet '+width);
    } else {
      check(await page.locator('.mobile-menu-toggle').isHidden(),'burger hidden on wide desktop '+width);
    }
    check((await page.locator('.footer-group[open]').count())===3,'desktop footer columns expanded '+width);
  }

  if(width===390){
    for(const path of ['/login','/register']){
      await page.goto(base+path);await page.waitForLoadState('networkidle');
      const h=await page.evaluate(()=>({body:document.documentElement.scrollHeight,viewport:innerHeight,scrollY:window.scrollY}));
      check(h.body<=h.viewport+2,'auth no body scroll '+path,h);
      check(await page.locator('.auth-main').isVisible(),'auth form visible '+path);
      await page.screenshot({path:'docs/screenshots/'+path.slice(1)+'-corrected.png',fullPage:true});
    }
    await page.goto(base);
    await page.locator('.hero-actions form button').click();
    check(new URL(page.url()).pathname==='/quiz','quiz started');
    await page.locator('.answer-tile').first().click();
    await page.waitForURL('**/quiz');
    await page.waitForFunction(()=>document.querySelector('.quiz-counter')?.textContent?.includes('2'),{timeout:5000});
    check((await page.locator('.quiz-counter').textContent()).includes('2'),'auto advance without next');
    await page.locator('.quiz-navigation button[value=back]').click();
    check((await page.locator('.quiz-counter').textContent()).includes('1'),'back navigation works');
    check(await page.locator('.answer-tile input:checked').count()===1,'answer remembered');
  }
  await context.close();
 }
 check(report.errors.length===0,'no JS page errors',report.errors);
 report.status='passed';
} catch(e){report.status='failed';report.failure=String(e);process.exitCode=1;}
finally{writeFileSync('docs/ui-fixes-report.json',JSON.stringify(report,null,2));console.log('UI_FIXES_REPORT',JSON.stringify({status:report.status,checks:report.checks.length,passed:report.checks.filter(x=>x.pass).length,error:report.failure||null,jsErrors:report.errors}));await browser.close();}
