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

const answerForm = document.querySelector('#answer-form');
if (answerForm) {
    const answers = [...answerForm.querySelectorAll('input[name="answer_option_id"]')];
    const forward = answerForm.querySelector('button[name="navigation"][value="next"], button[name="navigation"][value="finish"]');
    let movingForward = false;

    answers.forEach(input => input.addEventListener('change', () => {
        if (movingForward || !input.checked || !forward) return;
        movingForward = true;
        answerForm.classList.add('is-advancing');
        answers.forEach(radio => { radio.disabled = radio !== input; });
        window.setTimeout(() => answerForm.requestSubmit(forward), 200);
    }));

    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;
        movingForward = false;
        answerForm.classList.remove('is-advancing');
        answers.forEach(input => { input.disabled = false; });
    });
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

// Opening one FAQ item must not move the activated heading when another item closes.
// Update the layout synchronously and compensate only for the actual displacement.
const faq = document.querySelector('.faq-list');
if (faq) {
    const entries = [...faq.querySelectorAll('details')];
    for (const entry of entries) {
        const summary = entry.querySelector('summary');
        if (!summary) continue;
        summary.addEventListener('click', event => {
            event.preventDefault();
            const initialTop = summary.getBoundingClientRect().top;
            const shouldOpen = !entry.open;
            for (const other of entries) {
                if (other !== entry && other.open) other.open = false;
            }
            entry.open = shouldOpen;
            const displacement = summary.getBoundingClientRect().top - initialTop;
            if (Math.abs(displacement) > 0.5) {
                window.scrollBy({ top: displacement, left: 0, behavior: 'instant' });
            }
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
