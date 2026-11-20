import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { resolveBrowserOptions } from './browser-options.mjs';

const base = process.env.IQ_TEST_BASE_URL || 'http://127.0.0.1:8139';
const output = process.env.IQ_TEST_ARTWORK_SCREENSHOT_DIR || '.incoming/quiz-artwork-qa';
mkdirSync(output, { recursive: true });

const browser = await chromium.launch(resolveBrowserOptions());
let checked = 0;
try {
    for (const { width, height } of [
        { width: 320, height: 568 },
        { width: 360, height: 640 },
        { width: 390, height: 844 },
        { width: 430, height: 932 },
        { width: 1366, height: 768 },
        { width: 1440, height: 900 },
        { width: 1728, height: 900 },
    ].filter(({ width }) => !process.env.IQ_TEST_ONLY_VIEWPORT || width === Number(process.env.IQ_TEST_ONLY_VIEWPORT))) {
        const page = await browser.newPage({ viewport: { width, height }, deviceScaleFactor: 1 });
        await page.goto(base + '/');
        await page.locator('.hero-actions form button').click();
        await page.waitForURL(/\/quiz\/intake\/gender/);
        await page.locator('.intake-gender-choice').first().click();
        await page.locator('.intake-submit').click();
        await page.waitForURL(/\/quiz\/intake\/age/);
        await page.locator('.intake-age-choice').first().click();
        await page.locator('.intake-submit').click();
        await page.waitForURL(/\/quiz$/);

        for (let position = 1; position <= 25; position++) {
            await page.waitForFunction(expected => Number(
                (document.querySelector('.quiz-counter')?.textContent || '').match(/\d+/)?.[0]
            ) === expected, position);

            const q = await page.evaluate(() => {
                const svgs = [...document.querySelectorAll('.answer-tile svg')];
                const cells = [...document.querySelectorAll('.matrix-cell svg')];
                const container = document.querySelector('.quiz-container');
                const board = document.querySelector('.question-board').getBoundingClientRect();
                const answers = document.querySelector('.answer-grid').getBoundingClientRect();
                const nav = document.querySelector('.quiz-navigation').getBoundingClientRect();

                return {
                    options: svgs.length,
                    matrixCells: cells.length,
                    invalidAnswers: svgs.map((svg, i) => {
                        const { width, height } = svg.getBoundingClientRect();
                        const bounds = svg.viewBox.baseVal;
                        const ink = svg.getBBox();
                        return {
                            i, width, height,
                            ratio: Math.abs(width - height),
                            empty: !ink.width && !ink.height,
                            crop: ink.x < -1.5 || ink.y < -1.5
                                || ink.x + ink.width > bounds.width + 1.5
                                || ink.y + ink.height > bounds.height + 1.5,
                        };
                    }).filter(x => x.ratio > 0.7 || x.width < 40 || x.height < 40 || x.empty || x.crop),
                    horizontalOverflow: document.documentElement.scrollWidth > innerWidth + 1,
                    nestedScroll: container.scrollHeight > container.clientHeight + 2,
                    boardAnswerGap: answers.top - board.bottom,
                    answerNavGap: nav.top - answers.bottom,
                    docHeight: document.documentElement.scrollHeight,
                    navBottom: nav.bottom + window.scrollY,
                    viewportHeight: innerHeight,
                };
            });

            if (q.options !== 6 || q.matrixCells !== 8 || q.invalidAnswers.length
                || q.horizontalOverflow || q.nestedScroll
                || q.boardAnswerGap < (width <= 690 ? 28 : 34)
                || q.answerNavGap < (width <= 690 ? 40 : 44)
                || q.docHeight + 2 < q.navBottom
                || ((width >= 390 && height >= 768 && width !== 768)
                    && (q.docHeight > height + 2 || q.navBottom > height + 1))) {
                throw new Error(JSON.stringify({ position, viewport: width + 'x' + height, ...q }));
            }
            checked++;

            if ([1, 10, 18, 21, 24, 25].includes(position)) {
                await page.screenshot({
                    path: output + '/q' + String(position).padStart(2, '0')
                        + '-' + width + 'x' + height + '.png',
                    fullPage: true,
                });
            }

            // A natural, document-level scroll is allowed on short screens.
            // AJAX replacement must not move the viewport to a different position.
            await page.evaluate(() => {
                const grid = document.querySelector('.answer-grid').getBoundingClientRect();
                window.scrollTo({ top: window.scrollY + grid.top - innerHeight * 0.25, behavior: 'instant' });
            });
            const beforeScroll = await page.evaluate(() => window.scrollY);
            await page.locator('.answer-tile').first().evaluate(tile => tile.click());

            if (position === 25) {
                await page.waitForURL(/\/paywall\//);
                break;
            }

            await page.waitForFunction(expected => Number(
                (document.querySelector('.quiz-counter')?.textContent || '').match(/\d+/)?.[0]
            ) === expected, position + 1);

            const afterScroll = await page.evaluate(() => window.scrollY);
            if (Math.abs(afterScroll - beforeScroll) > 2) {
                throw new Error('Quiz viewport jumped on AJAX navigation: '
                    + JSON.stringify({ width, height, position, beforeScroll, afterScroll }));
            }

            const milestone = page.locator('[data-quiz-milestone]:visible');
            if (await milestone.count()) await milestone.locator('.quiz-milestone-continue').click();
        }
        console.log('VIEWPORT PASSED', width + 'x' + height, '25 questions with stable scroll');
        await page.close();
    }
    console.log('PASSED:', checked, 'question/viewport checks; 25 questions, 6 square SVG answers, generous gaps, no layout jumps');
} finally {
    await browser.close();
}
