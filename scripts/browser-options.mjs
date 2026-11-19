// Keep browser selection configurable across local machines and CI.
// On Windows, prefer the installed Chrome; Linux/macOS use Playwright Chromium.
export function resolveBrowserOptions() {
  const executablePath = process.env.IQ_TEST_BROWSER_EXECUTABLE;
  if (executablePath) return { executablePath, headless: true };
  if (process.platform === 'win32') return { channel: 'chrome', headless: true };
  return { headless: true };
}
