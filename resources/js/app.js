// IQ.Профиль — progressive enhancement. Quiz and authentication remain functional without JS.
document.documentElement.classList.add('js-ready');
const timer = document.querySelector('#quiz-timer');
if (timer) {
    const startedAt = Number(timer.dataset.start) * 1000;
    const serverOffset = Number(timer.dataset.server) * 1000 - Date.now();
    const updateTimer = () => {
        const seconds = Math.max(0, Math.floor((Date.now() + serverOffset - startedAt) / 1000));
        timer.textContent = String(Math.floor(seconds / 60)).padStart(2, '0')
            + ':' + String(seconds % 60).padStart(2, '0');
    };
    updateTimer();
    setInterval(updateTimer, 1000);
}

// Keep the question on the same page. Only the question, its answers and
// progress are replaced; the timer, viewport and header stay mounted.
// The server-rendered form remains fully functional when JS is unavailable.
function showQuizMilestone(notice) {
    if (!notice) return;

    // Move out of the shell so that the background can be made inert.
    document.body.append(notice);
    const shell = document.querySelector('.site-shell');
    const controls = [...notice.querySelectorAll('[data-milestone-close]')];
    const continueButton = notice.querySelector('.quiz-milestone-continue');
    let closed = false;

    const onKeyDown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        } else if (event.key === 'Tab') {
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    };

    function close() {
        if (closed) return;
        closed = true;
        document.removeEventListener('keydown', onKeyDown);
        if (shell) shell.inert = false;
        notice.remove();
        document.querySelector('#answer-form input[name="answer_option_id"]')
            ?.focus({ preventScroll: true });
    }

    controls.forEach(button => button.addEventListener('click', close));
    notice.hidden = false;
    if (shell) shell.inert = true;
    document.addEventListener('keydown', onKeyDown);
    continueButton.focus({ preventScroll: true });
}

function bindQuizForm(form) {
    const answers = [...form.querySelectorAll('input[name="answer_option_id"]')];
    const forward = form.querySelector('button[name="navigation"][value="next"], button[name="navigation"][value="finish"]');
    let movingForward = false;
    let submitting = false;

    function unlock(errorMessage) {
        submitting = false;
        movingForward = false;
        form.classList.remove('is-advancing');
        answers.forEach(input => { input.disabled = false; });
        if (errorMessage) {
            form.classList.add('has-error');
            const message = form.querySelector('#save-status');
            if (message) message.textContent = errorMessage;
        }
    }

    answers.forEach(input => input.addEventListener('change', () => {
        if (submitting || movingForward || !input.checked || !forward) return;
        movingForward = true;
        form.classList.add('is-advancing');
        answers.forEach(radio => { radio.disabled = radio !== input; });
        window.setTimeout(() => {
            if (form.isConnected) form.requestSubmit(forward);
        }, 160);
    }));

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting) return;
        submitting = true;

        const submitter = event.submitter;
        const payload = new FormData(form);
        if (submitter && submitter.name === 'navigation') {
            payload.set('navigation', submitter.value);
        }

        const oldContainer = document.querySelector('.quiz-container');
        // Preserve the document viewport while replacing only the question. The
        // quiz is allowed to extend beyond 100dvh; it must not jump to the top.
        const savedScrollY = window.scrollY;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                redirect: 'follow',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload
            });

            if (response.status === 409) {
                // A second tab advanced the same attempt; sync with the server.
                window.location.assign('/quiz');
                return;
            }
            if (!response.ok) throw new Error('Request failed: ' + response.status);

            const destination = new URL(response.url, window.location.href);
            if (destination.origin !== window.location.origin ||
                destination.pathname !== '/quiz') {
                window.location.assign(destination.href);
                return;
            }

            const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextContainer = documentFragment.querySelector('.quiz-container');

            if (!nextContainer) throw new Error('No question returned by server');

            // Do not replace the entire page or the persistent timer.
            for (const selector of ['.quiz-headline', '.question-board', '.answer-block']) {
                const current = oldContainer.querySelector(selector);
                const next = nextContainer.querySelector(selector);
                if (!current || !next) throw new Error('Incomplete question response');
                current.replaceWith(next);
            }

            const counter = oldContainer.querySelector('.quiz-counter strong');
            const nextCounter = nextContainer.querySelector('.quiz-counter strong');
            if (counter && nextCounter) counter.textContent = nextCounter.textContent;

            const progress = oldContainer.querySelector('.progress');
            const nextProgress = nextContainer.querySelector('.progress');
            if (progress && nextProgress) {
                progress.setAttribute('aria-valuenow', nextProgress.getAttribute('aria-valuenow'));
                progress.setAttribute('aria-valuemax', nextProgress.getAttribute('aria-valuemax'));
                progress.querySelector('span').style.width = nextProgress.querySelector('span').style.width;
            }

            document.title = documentFragment.title;
            window.scrollTo({ top: savedScrollY, left: 0, behavior: 'instant' });
            bindQuizForm(oldContainer.querySelector('#answer-form'));
            showQuizMilestone(documentFragment.querySelector('[data-quiz-milestone]'));
        } catch (error) {
            unlock('Не удалось сохранить ответ. Проверьте соединение и нажмите «Далее» ещё раз.');
        }
    });

}

const initialAnswerForm = document.querySelector('#answer-form');
if (initialAnswerForm) {
    bindQuizForm(initialAnswerForm);
    showQuizMilestone(document.querySelector('[data-quiz-milestone]'));
}

const toggle = document.querySelector('.mobile-menu-toggle');
const menu = document.querySelector('#mobile-site-menu');
const backdrop = document.querySelector('[data-menu-overlay]');
if (toggle && menu && backdrop) {
    const closeButton = menu.querySelector('.mobile-menu-close');
    let isOpen = false;
    let savedScrollY = 0;

    const setOpen = (open, restoreFocus = false) => {
        if (isOpen === open) return;
        isOpen = open;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        menu.inert = !open;
        menu.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);

        if (open) {
            savedScrollY = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.top = `-${savedScrollY}px`;
            document.body.style.width = '100%';
            closeButton.focus({ preventScroll: true });
        } else {
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            window.scrollTo({ top: savedScrollY, behavior: 'instant' });
            if (restoreFocus) toggle.focus({ preventScroll: true });
        }
    };

    toggle.addEventListener('click', () => setOpen(!isOpen, isOpen));
    closeButton.addEventListener('click', () => setOpen(false, true));
    backdrop.addEventListener('click', () => setOpen(false, true));
    menu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));

    document.addEventListener('keydown', event => {
        if (!isOpen) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false, true);
        } else if (event.key === 'Tab') {
            const focusable = [closeButton, ...menu.querySelectorAll('a[href]')];
            const current = focusable.indexOf(document.activeElement);
            if (event.shiftKey && (current === 0 || current === -1)) {
                event.preventDefault();
                focusable[focusable.length - 1].focus();
            } else if (!event.shiftKey && current === focusable.length - 1) {
                event.preventDefault();
                focusable[0].focus();
            }
        }
    });

    window.matchMedia('(min-width: 1101px)').addEventListener('change', event => {
        if (event.matches) setOpen(false);
    });
}

// Animate FAQ content rather than replacing the full height in one frame.
// Keep the clicked heading stationary while another answer above collapses.
const faq = document.querySelector('.faq-list');
if (faq) {
    const entries = [...faq.querySelectorAll('details')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const closeTimers = new WeakMap();
    let scrollFrame = 0;

    function finishClose(entry) {
        if (!entry.classList.contains('is-expanded')) {
            entry.open = false;
        }
    }

    function closeEntry(entry) {
        if (!entry.open) return;
        window.clearTimeout(closeTimers.get(entry));
        entry.classList.remove('is-expanded');
        if (reducedMotion.matches) {
            finishClose(entry);
        } else {
            closeTimers.set(entry, window.setTimeout(() => finishClose(entry), 340));
        }
    }

    function openEntry(entry) {
        window.clearTimeout(closeTimers.get(entry));
        entry.open = true;
        // Force the initial collapsed grid track to be calculated before
        // expanding it, including when reopening during the close transition.
        entry.querySelector('.faq-answer').getBoundingClientRect();
        entry.classList.add('is-expanded');
    }

    function keepHeadingInPlace(summary, originalTop) {
        window.cancelAnimationFrame(scrollFrame);
        const start = performance.now();
        function stabilize(now) {
            if (!summary.isConnected) return;
            const shift = summary.getBoundingClientRect().top - originalTop;
            if (Math.abs(shift) > 0.5) {
                window.scrollBy({ top: shift, left: 0, behavior: 'instant' });
            }
            if (now - start < (reducedMotion.matches ? 40 : 390)) {
                scrollFrame = window.requestAnimationFrame(stabilize);
            }
        }
        scrollFrame = window.requestAnimationFrame(stabilize);
    }

    faq.classList.add('is-animated');
    for (const entry of entries) {
        if (entry.open) entry.classList.add('is-expanded');
        const summary = entry.querySelector('summary');
        if (!summary) continue;
        summary.addEventListener('click', event => {
            event.preventDefault();
            const initialTop = summary.getBoundingClientRect().top;
            if (entry.classList.contains('is-expanded')) {
                closeEntry(entry);
            } else {
                for (const other of entries) {
                    if (other !== entry) closeEntry(other);
                }
                openEntry(entry);
            }
            keepHeadingInPlace(summary, initialTop);
        });
    }
}

// Desktop footer is always expanded; on mobile each category is an accordion.
const footerGroups = [...document.querySelectorAll('.footer-group')];
if (footerGroups.length) {
    const breakpoint = window.matchMedia('(max-width: 690px)');
    const syncFooter = () => {
        footerGroups.forEach(group => {
            group.open = !breakpoint.matches;
            const summary = group.querySelector('summary');
            if (summary) summary.setAttribute('aria-disabled', String(!breakpoint.matches));
        });
    };
    syncFooter();
    breakpoint.addEventListener('change', syncFooter);
    footerGroups.forEach(group => group.addEventListener('toggle', () => {
        if (!breakpoint.matches || !group.open) return;
        footerGroups.forEach(other => { if (other !== group) other.open = false; });
    }));
}
