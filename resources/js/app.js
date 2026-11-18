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
if (toggle && menu) {
    const setOpen = open => {
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        menu.classList.toggle('is-open', open);
        menu.inert = !open;
    };
    setOpen(false);
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
        }
    });
    document.addEventListener('click', event => {
        if (!toggle.contains(event.target) && !menu.contains(event.target)) setOpen(false);
    });
    window.matchMedia('(min-width: 1101px)').addEventListener('change', event => {
        if (event.matches) setOpen(false);
    });
}

// FAQ: single-open accordion, keep the clicked heading at the same viewport offset.
const faq = document.querySelector('.faq-list');
if (faq) {
    const entries = [...faq.querySelectorAll('details')];
    for (const entry of entries) {
        const summary = entry.querySelector('summary');
        if (!summary) continue;
        summary.addEventListener('click', event => {
            event.preventDefault();
            const beforeTop = summary.getBoundingClientRect().top;
            const openNew = !entry.open;
            for (const other of entries) {
                if (other !== entry) other.open = false;
            }
            entry.open = openNew;
            const afterTop = summary.getBoundingClientRect().top;
            const shift = afterTop - beforeTop;
            if (Math.abs(shift) > 1) window.scrollBy({ top: shift, left: 0, behavior: 'instant' });
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
