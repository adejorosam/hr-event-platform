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

echo "Waiting for Redis..."
until nc -z "$REDIS_HOST" "${REDIS_PORT:-6379}" 2>/dev/null; do
    sleep 1
done
echo "Redis is ready."

cd /var/www

php artisan key:generate --force --no-interaction 2>/dev/null || true
php artisan migrate --force --no-interaction

# Fix storage permissions so both root (consumer) and www-data (fpm) can write
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
touch /var/www/storage/logs/laravel.log
chmod 666 /var/www/storage/logs/laravel.log

echo "HubService is ready. Starting services..."

# Start nginx in background
nginx -g "daemon off;" &

# Start RabbitMQ consumer in background
php /var/www/artisan rabbitmq:consume &

# Start php-fpm in foreground
exec php-fpm
