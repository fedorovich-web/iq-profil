# Технический SEO-аудит локального MVP
Дата 2026-11-18.

- Landing: серверно отрендеренный HTML, ru lang, title/description, canonical, OG metadata и JSON-LD WebSite.
- robots.txt и sitemap.xml генерируются приложением из текущего домена; sitemap содержит только Landing.
- Quiz/Paywall/Checkout/Result получают noindex,nofollow,noarchive.
- Любые сессионные результаты имеют защиту доступа по текущей Laravel-сессии; закрытые данные не публикуются в sitemap.
- Важные CTA формируют настоящие серверные POST-запросы; сайт работает без обязательной JS-гидратации.
- Мобильный E2E: без горизонтального скролла на Landing, Quiz, Paywall, Checkout и Result.
- Nu HTML Checker: пять страниц, ноль ошибок в локальном браузерном прогоне.
- Axe-core мобильный: пять страниц, ноль нарушений в выбранном наборе тегов WCAG 2.2 AA.

Не проводились полевые измерения Core Web Vitals, Lighthouse, PageSpeed и проверка индексации после публичного деплоя. Их показатели не заявляются. Публичный домен пока не подтверждён.
