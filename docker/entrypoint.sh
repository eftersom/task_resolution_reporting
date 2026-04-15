#!/bin/sh
set -e

cd /var/www/html

touch .env

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --force

php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord -c /etc/supervisord.conf
