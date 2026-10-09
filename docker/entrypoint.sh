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

# Verify database connectivity with retry loop (handles Render database cold-starts)
echo "[HOYOS] Verifying database connectivity..."
MAX_RETRIES=25
RETRY_COUNT=0
DB_CONNECTED=false

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if php -r "
        require 'vendor/autoload.php';
        \$app = require 'bootstrap/app.php';
        \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            exit(0);
        } catch (\Throwable \$e) {
            exit(1);
        }
    " >/dev/null 2>&1; then
        DB_CONNECTED=true
        echo "[HOYOS] Database connection established successfully."
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT+1))
    echo "[HOYOS] Waiting for database to accept connections ($RETRY_COUNT/$MAX_RETRIES)..."
    sleep 2
done

if [ "$DB_CONNECTED" = "true" ]; then
    RUN_MIGRATIONS="${RUN_MIGRATIONS_ON_START:-true}"
    if [ "$RUN_MIGRATIONS" = "true" ]; then
        echo "[HOYOS] Running database migrations..."
        php artisan migrate --force || echo "[HOYOS] WARNING: Migration encountered an issue, proceeding with startup..."

        echo "[HOYOS] Running database seeding..."
        php artisan db:seed --force || true
    else
        echo "[HOYOS] RUN_MIGRATIONS_ON_START is false; skipping migrations."
    fi
else
    echo "[HOYOS] ======================================================================"
    echo "[HOYOS] ERROR: Could not connect to database after ${MAX_RETRIES} attempts!"
    echo "[HOYOS] DB_CONNECTION=${DB_CONNECTION:-pgsql}"
    if [ -n "$DATABASE_URL" ]; then
        echo "[HOYOS] DATABASE_URL is provided, but database is not reachable."
    else
        echo "[HOYOS] DATABASE_URL is NOT set in environment variables!"
        echo "[HOYOS] DB_HOST=${DB_HOST:-127.0.0.1}"
        if [ "${DB_HOST:-127.0.0.1}" = "127.0.0.1" ] || [ "${DB_HOST:-localhost}" = "localhost" ]; then
            echo "[HOYOS] >>> CRITICAL CONFIGURATION ISSUE <<<"
            echo "[HOYOS] DB_HOST is set to localhost/127.0.0.1."
            echo "[HOYOS] On Render, PostgreSQL runs on a separate service."
            echo "[HOYOS] In Render Dashboard -> Web Service -> Environment, link DATABASE_URL"
            echo "[HOYOS] or DB_HOST from your Render PostgreSQL instance."
        fi
    fi
    echo "[HOYOS] ======================================================================"
    exit 1
fi

# Ensure APP_URL and ASSET_URL default to HTTPS on Render
if [ -n "$RENDER_EXTERNAL_URL" ]; then
    export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"
    export ASSET_URL="${ASSET_URL:-$RENDER_EXTERNAL_URL}"
else
    export APP_URL="${APP_URL:-https://hoyos-ecommerce.onrender.com}"
    export ASSET_URL="${ASSET_URL:-https://hoyos-ecommerce.onrender.com}"
fi

# Ensure public storage symlink exists
echo "[HOYOS] Ensuring public storage symlink exists..."
php artisan storage:link --force || true

# If APP_KEY is provided, warm Laravel caches
if [ -n "$APP_KEY" ]; then
    echo "[HOYOS] Warming Laravel configuration, route, and view caches..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "[HOYOS] Launching PHP-FPM and Nginx via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
