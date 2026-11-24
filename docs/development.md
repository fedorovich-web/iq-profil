# Разработка и эксплуатация IQ.Профиль

Техническая документация вынесена из основного README, чтобы публичная страница репозитория описывала продукт, а не локальные настройки машины.

## Локальный запуск

Требуются PHP 8.5 с расширением SQLite, Composer 2, Node.js и npm. Команды запускаются из корня проекта:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8123
```

Перед запуском миграций убедитесь, что в `.env` выбрана **новая локальная база**. Не применяйте `migrate:fresh`, `db:wipe` или команды очистки к пользовательской SQLite.

При локальном запуске без системного PHP в конкретной Windows-среде возможен собственный путь к PHP. Каталог `.tools` не входит в репозиторий и не требуется пользователям других машин.

## Проверки

```powershell
php artisan test
vendor\bin\pint --test
npm run build
node scripts/browser-e2e.mjs
```

Перед Playwright-запуском нужен локальный сервер и установленный Google Chrome (Windows). На Linux/macOS установите Chromium командой npx playwright install chromium. При необходимости укажите IQ_TEST_BROWSER_EXECUTABLE для собственного бинарного файла браузера. Скрипт использует `IQ_TEST_BASE_URL` (по умолчанию `http://127.0.0.1:8123`), `IQ_TEST_REPORT_FILE` и `IQ_TEST_SCREENSHOT_DIR`, чтобы сохранять отчёты вне Git. Для E2E рекомендуется **отдельная временная SQLite**, а не рабочая база.

Дополнительные регрессионные сценарии проверяют гостевые сессии, демонстрационные подписки и SEO. Не путайте успешные локальные тесты с завершённой проверкой публичного production.

## Docker Compose / Dokploy

В `compose.yaml` определены PHP-FPM (`app`) и Nginx (`web`). SQLite хранится в **именованном Docker volume** `iq_database` по пути `/var/www/html/database/database.sqlite`. Режим развёртывания — **Docker Compose, не Docker Stack** (Stack не собирает образы через `build:`).

### Порядок первого развёртывания

1. Проверьте DNS A-запись `flatero.ru`: она должна указывать на публичный IP нужного сервера Dokploy. Убедитесь, что порты 80 и 443 доступны Traefik.
2. В уже созданном проекте `iq-profil`, окружение `production`, нажмите **Create Service → Docker Compose**. Источник GitHub: `fedorovich-web/iq-profil`, ветка `main`, путь файла Compose — `compose.yaml` (корень репозитория).
3. В разделе **Environment** добавьте `APP_KEY=<случайный_ключ_Laravel>` и `APP_URL=https://flatero.ru`. Сгенерировать ключ **на доверенной машине**: `php artisan key:generate --show`. Он должен остаться неизменным при дальнейших деплоях и никогда не попадать в Git. Переменные передаются через `${APP_KEY}` и `${APP_URL}` в Compose. **Не копируйте весь локальный .env в Dokploy**.
4. В **Domains → Add Domain** укажите `flatero.ru`, сервис `web`, **Container Port 80**, HTTPS через Let's Encrypt и перенаправление HTTP → HTTPS (если опция доступна). Домен **не привязывается к `app:9000`**. Не публикуйте внешний `ports: 8080:80`.
5. В настройках Compose по возможности включите **Isolated Deployments**. Иначе необходимо убедиться через **Preview Compose**, что `web` и `app` находятся в общей Docker-сети, а `web` также доступен Traefik. Иначе возникнет 502 или ошибка `app:9000`.
6. Нажмите **Deploy**. Проверьте логи контейнера `app`: миграции завершаются успешно и seed добавляет 25 вопросов, контейнер `web` получает статус `healthy`. Создание таблиц допустимо только для **новой пустой** SQLite; не указывайте на существующий volume без резервной копии.

### WWW -> основной домен

DNS-записи flatero.ru и www.flatero.ru указывают на один сервер. Для
Docker Compose маршруты www.flatero.ru на HTTP (web) и HTTPS (websecure)
с сертификатом Let's Encrypt объявлены в compose.yaml отдельными
Traefik labels. Middleware iqprofil-www-redirect выполняет постоянный
редирект (301 или 308) на https://flatero.ru с сохранением пути и query string.
Основной домен продолжает обслуживаться через Dokploy Domains -> web:80.
Существующую запись flatero.ru удалять не следует.

Изменения Docker labels требуют Deploy/Redeploy Compose в Dokploy.
После него нужно проверить http://www.flatero.ru/test?x=1 и
https://www.flatero.ru/test?x=1: оба адреса должны перенаправлять на
https://flatero.ru/test?x=1 с действительным HTTPS-сертификатом.
Для автоматической проверки после Deploy запустите node tests/domain-post-deploy.mjs.
Скрипт проверяет 301 с обоих протоколов, canonical, Open Graph и sitemap.

В sitemap, canonical, Open Graph и JSON-LD для главной страницы используется
один абсолютный URL: https://flatero.ru/ (с завершающим слешем).

В production заданы `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_LIFETIME=10080` (семь дней), `SESSION_SECURE_COOKIE=true`. SSL завершается на Traefik. Laravel доверяет forwarded-заголовкам только от адресов приватных сетей.

**Важно:** при удалении Compose-сервиса никогда не включайте «Delete volumes» без проверенной резервной копии. Не запускайте `docker compose down -v`, `migrate:fresh`, `db:wipe` и не меняйте имя Compose-проекта: это может скрыть старый volume под новым именем.

### Проверка публичного сайта

Проверить `https://flatero.ru/`, `https://flatero.ru/robots.txt`, `https://flatero.ru/sitemap.xml` и `https://flatero.ru/up`. Sitemap и директива `Sitemap:` в robots должны содержать **https://flatero.ru**, а не `localhost` или `http://`. JSON-LD главной должен содержать один `FAQPage` и все видимые пары `Question` / `Answer`.

Далее в реальном браузере проверить гостевой тест, продолжение, оплату **только в деморежиме**, отчёт, регистрацию, авторизацию, личный кабинет и отмену подписки. После первой проверки — сделать бэкап и выполнить **обычный redeploy без смены Compose-проекта**, проверить сохранение записей SQLite.

Контейнерный `entrypoint` выполняет миграции и идемпотентный seed при запуске. При изменениях банка вопросов обязательно проверяйте версии попыток и сохранность старых результатов. Для восстановления пароля по email требуется настроить реальный SMTP; без него `MAIL_MAILER=log` не отправляет письма пользователям.

## SQLite и резервные копии

Перед переносом, изменением объёма Docker Compose, сменой mount/volume или миграциями:

1. Определите реальный volume `iq_database` и путь `/var/www/html/database/database.sqlite` внутри контейнера.
2. Создайте проверенную консистентную резервную копию с использованием SQLite backup API или `VACUUM INTO` на доступной файловой системе.
3. Проверьте `PRAGMA integrity_check`, наличие таблиц и числа строк.
4. Убедитесь, что контейнер после перезапуска использует **тот же volume**, и только затем продолжайте.

Для локальной стандартной SQLite предусмотрен [scripts/backup-database.php](../scripts/backup-database.php). В production сначала подтвердите путь базы; не выполняйте локальные скрипты наугад.

## Production-приёмка

- HTTP → HTTPS, сертификат, canonical, `robots.txt`, `sitemap.xml`, 404/500.
- Регистрация, вход, выход, восстановление пароля (при настроенном канале отправки), ограничения доступа.
- Полный тест на десктопе и мобильном: начало → сохранение → завершение → paywall → демо-checkout → отчёт.
- Демо-подписка, повторное подтверждение и отмена.
- Сохранение SQLite после restart/redeploy, корректность seed и отсутствие дубликатов.
- Отсутствие секретов в Git, открытых debug-режимов, ошибок JS и горизонтального скролла.
- Юридические документы с действительными данными оператора до реального коммерческого использования.

Демо-тарифы — 20 ₽ и 499 ₽/месяц — нельзя трактовать как действующие платёжные условия. Реального эквайринга в MVP нет.

## Обновлённый банк матриц v4

Новая ревизия содержит **строго 25 вопросов и 6 графических ответов на каждый**. До первого задания пользователь проходит два шага — пол (включая «Не хочу указывать») и возрастная группа. Два шага не включаются в счёт и таймер теста. Возраст/пол **не превращают** сырой результат в нормированный IQ.

Банк неизменяем: seeder проверяет содержание каждого опубликованного вопроса и все шесть значений вариантов/ключей при **каждом запуске**. Изменять существующий банк в БД нельзя: публикуйте новую ревизию, сохраняя предыдущие попытки. Перед миграцией на Dokploy создайте и проверьте резервную копию volume SQLite. Не применяйте destructive migrations к production.

Для предметного QA и научных ограничений см. [аудит заданий v4](psychometric-audit-v4.md). Автоматические проверки графики:

```powershell
php artisan test
vendor\bin\pint --test
node scripts/svg-visual-qa.mjs
node scripts/browser-e2e.mjs
```

Для изображения всех 25 вопросов скрипт визуальной проверки создаёт контакт-листы под `.incoming/svg-qa/` на пяти разрешениях. На Windows, где PHP не входит в PATH, можно указать `IQ_TEST_PHP_BINARY`.

Агрегированная диагностика после появления реальных прохождений:

```powershell
php artisan iq:pilot-report --revision=4
```

Только чтение БД. Вывод включает частоты A–F, процент решивших каждое задание и, когда достаточно записей, предварительную корреляцию с результатом остальных 24 вопросов. Вывод не содержит индивидуальных ответов, не строит нормативы и не вычисляет IQ. При интерпретации учитывайте самоотбор и повторные прохождения.

## Quiz timing and completion

The quiz has no elapsed-time deadline. The browser counts visible active time using a monotonic clock, pauses when hidden or after 15 minutes without interaction, and ignores long scheduler gaps. It retains unsaved timing locally per attempt. Server checkpoints are saved together with answer navigation; no background HTTP polling is required. Across browsers/devices only the last server checkpoint is guaranteed. The recorded duration is telemetry, not a scoring input; malformed values never block answers. The counter is capped at 120:00+ without limiting completion. Legacy unfinished attempts with no duration are estimated from short intervals between stored answers; long gaps are omitted. The original completed records are not rewritten. Admin and account history are sorted by completion or latest activity, not creation ID.

Regression coverage: tests/Feature/QuizFlowTest.php and scripts/quiz-timer-qa.mjs (included in quality gates). Use an isolated database for all browser and feature tests.
