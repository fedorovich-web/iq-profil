// Progressive enhancement: the quiz also works without JavaScript via POST forms.
const timer = document.querySelector('#quiz-timer');
if (timer) {
    const started = Number(timer.dataset.start) * 1000;
    const serverNow = Number(timer.dataset.server) * 1000;
    const offset = serverNow - Date.now();
    const show = () => {
        const seconds = Math.max(0, Math.floor((Date.now() + offset - started) / 1000));
        const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
        const remainder = String(seconds % 60).padStart(2, '0');
        timer.textContent = minutes + ':' + remainder;
    };
    show();
    setInterval(show, 1000);
}

const form = document.querySelector('#answer-form');
if (form) {
    const inputs = form.querySelectorAll('input[name="answer_option_id"]');
    const status = document.querySelector('#save-status');
    const token = form.querySelector('input[name="_token"]')?.value;
    const csrfHeader = token ? { 'X-CSRF-TOKEN': token } : {};
    let pending = null;

    for (const input of inputs) {
        input.addEventListener('change', async () => {
            status.textContent = 'Сохраняем ответ…';
            if (pending) pending.abort();
            const controller = new AbortController();
            pending = controller;
            try {
                const res = await fetch('/quiz/selection', {
                    method: 'POST',
                    headers: { ...csrfHeader, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ answer_option_id: Number(input.value) }),
                    signal: controller.signal
                });
                if (!res.ok) throw new Error('Не удалось сохранить');
                status.textContent = 'Ответ сохранён';
            } catch (error) {
                if (error.name !== 'AbortError') {
                    status.textContent = 'Ошибка автосохранения. Нажмите «Далее», чтобы сохранить ответ.';
                }
            } finally {
                if (pending === controller) pending = null;
            }
        });
    }
}
