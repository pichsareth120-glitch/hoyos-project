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

# Ensure public storage symlink exists
php artisan storage:link --force || true

# Opt-in database migration check for environments without Shell access (e.g. Render Free)
if [ "$RUN_MIGRATIONS_ON_START" = "true" ]; then
    echo "[HOYOS] RUN_MIGRATIONS_ON_START is 'true'. Running database migrations..."
    if php artisan migrate --force; then
        echo "[HOYOS] Database migrations completed successfully."
    else
        echo "[HOYOS] ERROR: Database migration failed! Halting container startup." >&2
        exit 1
    fi
else
    echo "[HOYOS] RUN_MIGRATIONS_ON_START is not 'true' (value: '${RUN_MIGRATIONS_ON_START:-unset}'). Skipping automatic database migrations."
fi

# If APP_KEY is provided, warm Laravel caches
if [ -n "$APP_KEY" ]; then
    echo "[HOYOS] Warming Laravel configuration and route caches..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "[HOYOS] Launching PHP-FPM and Nginx via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
