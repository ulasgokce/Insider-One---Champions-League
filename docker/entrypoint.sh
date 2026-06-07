#!/bin/sh
set -e

touch database/database.sqlite

php artisan config:clear
php artisan migrate --force

if [ "${SEED_ON_BOOT:-true}" = "true" ]; then
    php artisan db:seed --force || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
