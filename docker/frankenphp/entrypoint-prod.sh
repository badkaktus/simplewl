#!/bin/bash
set -e

cd /var/www/html

# Ensure required storage directories exist (volume may be empty on first run)
mkdir -p storage/app/public \
         storage/database \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache

# Create public/storage symlink if missing
if [ ! -L public/storage ]; then
    php artisan storage:link --force
fi

# Run database migrations
php artisan migrate --force

# Cache routes, config, views for performance
php artisan optimize

exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
