#!/bin/sh
#
# Entry point container "app".
# Semua berkas aplikasi dipindahkan ke volume bersama supaya container nginx
# bisa menyajikan file statis (public/build, storage, dst.) dari folder yang sama.

set -e

APP_DIR=/var/www/html

mkdir -p "$APP_DIR"
cp -a /opt/app/. "$APP_DIR/"
cd "$APP_DIR"

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# APP_KEY: dibuat sekali lalu disimpan di volume, supaya sesi/cookie tidak pecah
# setiap container restart. Kalau sudah diisi lewat env, dipakai apa adanya.
if [ -z "$APP_KEY" ]; then
    KEY_FILE="$APP_DIR/storage/app/.app_key"

    if [ -s "$KEY_FILE" ]; then
        APP_KEY="$(cat "$KEY_FILE")"
    else
        APP_KEY="base64:$(head -c 32 /dev/urandom | base64 | tr -d '\r\n')"
        printf '%s' "$APP_KEY" > "$KEY_FILE"
    fi

    export APP_KEY
fi

# Cache konfigurasi & view. Penting: php-fpm membuang environment worker
# (clear_env), jadi semua nilai env harus sudah ter- bake ke config cache.
php artisan config:cache --ansi
php artisan view:cache --ansi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
