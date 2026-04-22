#!/bin/sh
set -e

# Wait for the database to be ready (up to 60 seconds)
echo "Waiting for database..."
until php artisan db:show --json > /dev/null 2>&1; do
    sleep 2
done
echo "Database is ready."

# Run migrations
php artisan migrate --force

# Cache configuration, routes, and views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in the background
php-fpm -D

# Start nginx in the foreground (keeps the container alive)
exec nginx -g "daemon off;"
