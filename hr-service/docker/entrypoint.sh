#!/bin/bash
set -e

echo "Waiting for PostgreSQL..."
until nc -z "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; do
    sleep 1
done
echo "PostgreSQL is ready."

echo "Waiting for RabbitMQ..."
until nc -z "$RABBITMQ_HOST" "${RABBITMQ_PORT:-5672}" 2>/dev/null; do
    sleep 1
done
echo "RabbitMQ is ready."

cd /var/www

php artisan key:generate --force --no-interaction 2>/dev/null || true
php artisan migrate --force --no-interaction

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "HR Service is ready. Starting services..."

# Start nginx in background
nginx -g "daemon off;" &

# Start php-fpm in foreground
exec php-fpm
