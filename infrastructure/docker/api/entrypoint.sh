#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# Rebuild the package manifest after the production autoloader was generated.
php artisan package:discover --ansi || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    attempts=0
    until php artisan migrate --force; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            echo "Database migrations failed after ${attempts} attempts." >&2
            exit 1
        fi
        echo "Database not ready; retrying in 2s (${attempts}/30)…"
        sleep 2
    done
fi

if [ "${SEED_DEMO:-false}" = "true" ] && [ ! -f storage/app/.demo-seeded ]; then
    php artisan db:seed --force
    touch storage/app/.demo-seeded
fi

if [ "${SEARCH_DRIVER:-database}" = "meilisearch" ]; then
    php artisan search:index || true
fi

if [ ! -L public/storage ] && [ "${FILESYSTEM_DISK:-public}" = "public" ]; then
    php artisan storage:link || true
fi

exec "$@"
