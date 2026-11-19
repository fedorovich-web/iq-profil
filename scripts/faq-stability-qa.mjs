import { chromium } from 'playwright';
import { resolveBrowserOptions } from './browser-options.mjs';

const browser = await chromium.launch(resolveBrowserOptions());
const results = [];
for (const width of [320, 390, 768, 1440]) {
  const page = await browser.newPage({ viewport: { width, height: 800 } });
  await page.goto(process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8123', { waitUntil: 'networkidle' });
  for (const index of [0, 1, 2, 3, 1, 1, 5, 0]) {
    const result = await page.evaluate(async (i) => {
      const summary = document.querySelectorAll('.faq-list summary')[i];
      summary.scrollIntoView({ block: 'center', behavior: 'instant' });
      await new Promise(resolve => setTimeout(resolve, 60));
      const initial = summary.getBoundingClientRect().top;
      const startScroll = window.scrollY;
      const samples = [];
      summary.click();
      await new Promise(resolve => {
        const started = performance.now();
        const sample = () => {
          samples.push({ top: summary.getBoundingClientRect().top, scroll: window.scrollY });
          if (performance.now() - started >= 450) resolve();
          else requestAnimationFrame(sample);
        };
        requestAnimationFrame(sample);
      });
      const drift = Math.max(...samples.map(s => Math.abs(s.top - initial)));
      return { index: i, drift: Math.round(drift * 10) / 10, scrollChange: window.scrollY - startScroll, open: document.querySelectorAll('.faq-list details[open]').length };
    }, index);
    results.push({ width, ...result });
  }
  const transition = await page.evaluate(async () => {
    const list = document.querySelector('.faq-list');
    const details = [...list.querySelectorAll('details')];
    for (const item of details) {
      if (item.classList.contains('is-expanded')) item.querySelector('summary').click();
    }

    // End the previous interaction through the same public click handler.
    // Forcing the native open attribute off bypasses the closing animation.
    await new Promise(resolve => setTimeout(resolve, 390));

    const first = details[0];
    const content = first.querySelector('.faq-answer');
    first.querySelector('summary').click();
    const initial = content.getBoundingClientRect().height;
    await new Promise(resolve => setTimeout(resolve, 95));
    const intermediate = content.getBoundingClientRect().height;
    await new Promise(resolve => setTimeout(resolve, 340));
    const final = content.getBoundingClientRect().height;

    return { initial, intermediate, final };
  });
  results.push({
    width,
    transition,
    smooth: transition.initial < transition.intermediate
      && transition.intermediate < transition.final,
  });

  await page.close();
}
const noJavaScript = await browser.newPage({ javaScriptEnabled: false });
await noJavaScript.goto(process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8123', { waitUntil: 'domcontentloaded' });
await noJavaScript.locator('.faq-list summary').first().click();
const worksWithoutJavaScript = await noJavaScript.locator('.faq-list details').first().evaluate(
  item => item.open && item.querySelector('.faq-answer p').getBoundingClientRect().height > 0,
);
await noJavaScript.close();

const reducedMotion = await browser.newPage({ reducedMotion: 'reduce' });
await reducedMotion.goto(process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8123', { waitUntil: 'domcontentloaded' });
const respectsReducedMotion = await reducedMotion.locator('.faq-list .faq-answer').first().evaluate(
  item => getComputedStyle(item).transitionDuration === '0s',
);
await reducedMotion.close();

await browser.close();
const failures = results.filter(r => r.drift > 2 || r.open > 1 || r.smooth === false);
if (!worksWithoutJavaScript || !respectsReducedMotion) {
  failures.push({ worksWithoutJavaScript, respectsReducedMotion });
}
console.log(JSON.stringify({ failures, results, worksWithoutJavaScript, respectsReducedMotion }, null, 2));
process.exit(failures.length ? 1 : 0);
