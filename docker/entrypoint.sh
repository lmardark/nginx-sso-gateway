#!/bin/sh
set -e

cd /var/www

# Ensure storage directories exist
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/app/public/logos
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# Create SQLite database if it doesn't exist
if [ ! -f storage/database.sqlite ]; then
    touch storage/database.sqlite
    chown www-data:www-data storage/database.sqlite
fi

# Run migrations
php artisan migrate --force

# Start PHP-FPM
exec php-fpm
