import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { resolveBrowserOptions } from './browser-options.mjs';

const php = process.env.IQ_TEST_PHP_BINARY || 'php';
const markup = execFileSync(php, ['scripts/svg-contact-sheet.php'], {
  encoding: 'utf8',
  maxBuffer: 2 * 1024 * 1024,
});
const destination = process.env.IQ_TEST_SVG_SCREENSHOT_DIR || '.incoming/svg-qa';
mkdirSync(destination, { recursive: true });

const browser = await chromium.launch(resolveBrowserOptions());
try {
  for (const width of [320, 390, 768, 1440, 1920]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    await page.setContent(markup, { waitUntil: 'load' });
    const quality = await page.evaluate(() => {
      const cards = [...document.querySelectorAll('.card')];
      const svgs = [...document.querySelectorAll('.cell svg, .opt svg')];
      const problems = [];
      if (cards.length !== 25) problems.push('Expected 25 cards, got ' + cards.length);
      if (svgs.length !== 350) problems.push('Expected 350 illustrations, got ' + svgs.length);
      if (document.documentElement.scrollWidth > innerWidth + 1) problems.push('Horizontal overflow');
      cards.forEach((card, cardIndex) => {
        if (card.querySelectorAll('.matrix .cell').length !== 9) problems.push('Question '+(cardIndex+1)+' missing matrix cells');
        const choices = [...card.querySelectorAll('.opt svg')];
        if (choices.length !== 6) problems.push('Question '+(cardIndex+1)+' missing SVG choices');
        if (new Set(choices.map(svg => svg.innerHTML)).size !== 6) problems.push('Question '+(cardIndex+1)+' has duplicate illustrated answers');
      });
      svgs.forEach((svg, index) => {
        if (svg.getAttribute('viewBox') !== '0 0 100 100') problems.push('SVG '+index+' inconsistent viewBox');
        const r = svg.getBoundingClientRect();
        if (!r.width || !r.height || Math.abs(r.width-r.height) > .5) problems.push('SVG '+index+' invalid aspect ratio');
        const box = svg.getBBox();
        if (box.width === 0 && box.height === 0) problems.push('SVG '+index+' empty graphic');
        const pad = 1.5;
        if (box.x < -pad || box.y < -pad || box.x+box.width > 100+pad || box.y+box.height > 100+pad) {
          problems.push('SVG '+index+' clipped artwork');
        }
        if (svg.querySelector('image,foreignObject,script')) problems.push('SVG '+index+' embeds unsupported content');
      });
      return { cards:cards.length, svgs:svgs.length, problems };
    });
    await page.screenshot({ path: join(destination,'contact-'+width+'.png'), fullPage: true });
    console.log(JSON.stringify({ width, ...quality }));
    await page.close();
    if (quality.problems.length) throw new Error('SVG visual QA failed at '+width+': '+quality.problems.join('; '));
  }
  console.log('SVG VISUAL QA PASSED: 25 matrices, 150 distinct options, 350 SVG render instances, five viewports');
} finally {
  await browser.close();
}
