import { chromium } from 'playwright';

const browser = await chromium.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: true });
const results = [];
for (const width of [320, 390, 768, 1440]) {
  const page = await browser.newPage({ viewport: { width, height: 800 } });
  await page.goto('http://127.0.0.1:8123', { waitUntil: 'networkidle' });
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
  await page.close();
}
await browser.close();
console.log(JSON.stringify({ failures: results.filter(r => r.drift > 2 || r.open > 1), results }, null, 2));
process.exit(results.some(r => r.drift > 2 || r.open > 1) ? 1 : 0);
