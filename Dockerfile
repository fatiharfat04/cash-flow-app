# syntax=docker/dockerfile:1
#
# Cash Flow — image aplikasi (PHP-FPM) + aset front-end.
# Dipakai oleh docker-compose.yml bersama service nginx dan mysql.

##############################################################################
# Tahap 1: aset front-end (Vite + Tailwind). Chart.js sengaja TIDAK ikut di
# sini — sesuai §2 PROJECT.md dia dimuat via CDN, bukan npm install.
##############################################################################
FROM node:22-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

##############################################################################
# Tahap 2: aplikasi PHP 8.3-FPM
##############################################################################
FROM php:8.3-fpm

# Ekstensi PHP: pdo_mysql (wajib), intl (lokasi id), mbstring, zip, opcache,
# gd (wajib — phpoffice/phpspreadsheet / fitur export Excel)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        pkg-config \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl mbstring zip gd opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Berkas dependensi dulu supaya layer composer cache-able
WORKDIR /opt/app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --no-interaction \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

# Aset hasil build tahap 1
COPY --from=assets /app/public/build ./public/build

RUN php artisan package:discover --ansi \
    && php artisan storage:link --ansi

COPY docker/entrypoint.sh /usr/local/bin/cash-flow-entrypoint
RUN chmod +x /usr/local/bin/cash-flow-entrypoint

EXPOSE 9000

ENTRYPOINT ["cash-flow-entrypoint"]
CMD ["php-fpm"]
