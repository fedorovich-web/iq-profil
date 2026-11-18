FROM node:24-alpine AS assets
WORKDIR /app
COPY package*.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci --no-audit --no-fund && npm run build

FROM php:8.5-fpm-alpine AS app
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j$(nproc) pdo_sqlite intl mbstring zip opcache
WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-ansi --no-progress --prefer-dist --no-scripts --optimize-autoloader
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs database \
    && chown -R www-data:www-data storage database bootstrap/cache
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]

FROM nginx:1.29-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build
EXPOSE 80
