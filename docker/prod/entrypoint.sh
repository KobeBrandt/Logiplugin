#!/bin/sh
set -e

# Environment variables are only available at runtime, so the caches are built here instead of in the image
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
