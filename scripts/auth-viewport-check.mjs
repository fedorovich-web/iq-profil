import { chromium } from 'playwright';
const browser=await chromium.launch({executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',headless:true});
let fail=0;
try{
for(const [width,height] of [[320,568],[360,640],[390,844],[768,1024],[1440,900]]){
const p=await browser.newPage({viewport:{width,height},isMobile:width<=390});
for(const route of ['/login','/register']){
await p.goto('http://127.0.0.1:8123'+route);
await p.waitForTimeout(100);
const d=await p.evaluate(()=>{
 const el=document.querySelector('.auth-main');const r=el.getBoundingClientRect();
 const last=document.querySelector('.auth-bottom')?.getBoundingClientRect();
 return {viewport:innerHeight,scroll:document.documentElement.scrollHeight,mainScroll:el.scrollHeight,mainClient:el.clientHeight,top:r.top,bottom:r.bottom,contentBottom:last?.bottom};
});
const good=d.scroll<=d.viewport+2&&d.top>=-1&&d.contentBottom<=d.viewport+1&&d.mainScroll<=d.mainClient+2;
console.log('AUTH',width,height,route,good?'PASS':'FAIL',JSON.stringify(d));if(!good)fail++;
}
await p.close();
}
}finally{await browser.close();}
if(fail)process.exitCode=1;
