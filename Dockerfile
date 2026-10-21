# ---- 1) Build the Tailwind CSS ----------------------------------------------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY tailwind.config.js ./
COPY resources ./resources
COPY public/js ./public/js
RUN npm run build

# ---- 2) PHP app (nginx + php-fpm) --------------------------------------------
FROM serversideup/php:8.4-fpm-nginx

ENV AUTORUN_ENABLED=true \
    PHP_OPCACHE_ENABLE=1

USER root
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/css/app.css ./public/css/app.css

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data