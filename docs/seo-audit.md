# Техническое SEO — IQ.Профиль

Дата: 18.11.2026.

## Сделано
- Публичный SSR Landing, ru/lang, title/description, OpenGraph и JSON-LD WebSite, canonical.
- robots.txt генерируется сервером по активному домену и содержит три отдельных группы: Yandex, Googlebot, *.
- Запрет сканирования приватных маршрутов: /admin, /account, /auth/, /login, /register, /forgot-password, /reset-password, /quiz, /attempts, /paywall/, /checkout/, /result/, /subscription/cancel.
- Для Yandex указана директива Clean-param для рекламных меток utm_*, yclid, gclid; sitemap.xml отдаётся абсолютным URL.
- Sitemap содержит только четыре открытые страницы: Landing, Политика, Демо-оферта, Согласие. Личные отчёты и демо-отмена исключены.
- У юридических страниц предусмотрены персональные canonical; коммерческая юридическая индексация имеет смысл только после подстановки данных реального оператора и правовой проверки.
- Все приватные экраны имеют noindex и/или закрыты серверной авторизацией; robots.txt не является средством защиты приватных данных.
- В Login/Register обязательный серверный процесс авторизации/валидации. JavaScript не нужен для основной воронки.
- В тестах LegalSeoSubscriptionTest проверены группы robots, sitemap, доступность юридических страниц, наличие ссылок в футере, согласие и отмена.
- HTML Nu Validator (пять экранов в исходной воронке): 0 ошибок. Axe мобильный (пять экранов): 0 найденных нарушений в выбранном списке правил.

## Не проверено
- Полевой Core Web Vitals / Lighthouse, индексация в Яндекс Вебмастере и Google Search Console, реальный домен, robots/sitemap после HTTPS-развёртывания, полный юридический контент с заполненными реквизитами.
- Отсутствуют данные Яндекс Wordstat и других инструментов по частотности — значения не выдумываются.

## Источники
https://yandex.ru/support/webmaster/ru/controlling-robot/robots-txt
https://yandex.ru/support/webmaster/ru/robot-workings/clean-param
https://developers.google.com/crawling/docs/robots-txt/robots-txt-spec
https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap
