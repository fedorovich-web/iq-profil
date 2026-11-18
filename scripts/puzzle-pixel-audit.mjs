import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { join, resolve } from 'node:path';

const dir = resolve('storage/app/qa');
const browser = await chromium.launch({headless:true, executablePath:'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'});
const scores = [];
for (let group=1; group<=5; group++) {
  const page=await browser.newPage({viewport:{width:1120,height:1000}});
  await page.goto(pathToFileURL(join(dir,'graphic-'+group+'.html')).href);
  const groupScores = await page.evaluate(async () => {
    async function pixels(svg) {
      const canvas = document.createElement('canvas');
      canvas.width=48;canvas.height=48;
      const ctx=canvas.getContext('2d',{willReadFrequently:true});
      ctx.fillStyle='#ffffff';ctx.fillRect(0,0,48,48);
      const source='data:image/svg+xml;charset=utf-8,'+encodeURIComponent(new XMLSerializer().serializeToString(svg));
      const img=new Image();
      img.src=source;
      await img.decode();
      ctx.drawImage(img,0,0,48,48);
      return ctx.getImageData(0,0,48,48).data;
    }
    const output = [];
    for(const card of document.querySelectorAll('.card')){
      const name=card.querySelector('h2').textContent;
      const variants=await Promise.all([...card.querySelectorAll('.opt svg')].map(pixels));
      let min=1, nearest=null;
      for(let a=0;a<variants.length;a++){
        for(let b=a+1;b<variants.length;b++){
          let changed=0;
          for(let p=0;p<variants[a].length;p+=4){
            const maximum=Math.max(...[0,1,2].map(j=>Math.abs(variants[a][p+j]-variants[b][p+j])));
            if(maximum>20) changed++;
          }
          const ratio=changed/(48*48);
          if(ratio<min){min=ratio;nearest=[String.fromCharCode(65+a),String.fromCharCode(65+b)];}
        }
      }
      output.push({name,minimumDistinguishablePixelRatio:Number(min.toFixed(4)),nearest});
    }
    return output;
  });
  scores.push(...groupScores);
  await page.close();
}
await browser.close();
const sorted=scores.sort((a,b)=>a.minimumDistinguishablePixelRatio-b.minimumDistinguishablePixelRatio);
console.log(JSON.stringify({pairsAudited:scores.length*15,minimum:sorted[0],nearlyIdentical:sorted.filter(x=>x.minimumDistinguishablePixelRatio<0.025),fiveClosest:sorted.slice(0,5)},null,2));
if(sorted.some(x=>x.minimumDistinguishablePixelRatio<0.015)){process.exitCode=1;}
