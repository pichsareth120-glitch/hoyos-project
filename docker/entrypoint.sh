#!/bin/sh
set -e

# Default PORT to 10000 if not provided by runtime
PORT="${PORT:-10000}"
echo "[HOYOS] Starting on port ${PORT}..."

# Replace placeholder in Nginx config with dynamic runtime PORT
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/default.conf

# Ensure writable runtime directories exist with proper permissions
mkdir -p /var/www/html/storage/app/public \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear stale caches
echo "[HOYOS] Clearing cached configuration, routes, and views..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
echo "[HOYOS] Running database migrations..."
php artisan migrate --force

# Seed database tables
echo "[HOYOS] Running database seeding..."
php artisan db:seed --force || true

# Ensure public storage symlink exists
echo "[HOYOS] Ensuring public storage symlink exists..."
php artisan storage:link || true

# If APP_KEY is provided, warm Laravel caches
if [ -n "$APP_KEY" ]; then
    echo "[HOYOS] Warming Laravel configuration, route, and view caches..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "[HOYOS] Launching PHP-FPM and Nginx via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
