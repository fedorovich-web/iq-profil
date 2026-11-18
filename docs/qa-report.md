# QA отчёт — 2026-11-18

## Проверенные артефакты
- `docs/browser-report.json`: реальный Chromium/Chrome, десктоп 1440px и мобильный 390px, 91/91 проверок.
- `docs/w3c-report.json`: Nu HTML Checker, публичная Landing и четыре сессионных экрана, 0 ошибок (информационные сообщения о self-closing void tags из Vite игнорируются).
- `docs/accessibility-report.json`: axe-core, пять мобильных экранов, 0 выявленных нарушений по выбранным WCAG 2.2 AA тегам.
- `php artisan test`: 8 интеграционных тестов, 213 assertions.
- `vendor/bin/pint --test`: 30 PHP файлов проходят форматирование.
- `npm run build`: Vite production build успешно сформирован.

## Не измерялось / не проверено
- Публичный HTTPS-деплой и persistence DB после перезапуска production-контейнера.
- Lighthouse / Core Web Vitals в полевых условиях.
- Кроссбраузерные Firefox и Safari.
- Визуальное pixel-perfect соответствие исходным AVIF-изображениям, не перенесённым из чата на Home-PC.
- Независимая психометрическая валидность и нормативные IQ-баллы.

Нельзя считать все пункты исходного ТЗ закрытыми до реального деплоя.
