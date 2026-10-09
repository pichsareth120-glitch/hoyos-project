#!/bin/sh
# ==============================================================================
# HOYOS E-COMMERCE CONTAINER ENTRYPOINT
# Orchestrates runtime ports, directory permissions, cache management,
# database connectivity/migrations, and launches Supervisord (Nginx + PHP-FPM).
# ==============================================================================

set -e

# ==============================================================================
# STAGE 1: Runtime Port & Web Server Configuration
# ==============================================================================
PORT="${PORT:-10000}"
echo "[HOYOS] Booting application container on port ${PORT}..."

# Inject dynamic runtime port into Nginx virtual host configuration
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/default.conf

# Configure base URLs for production reverse proxy / Render SSL termination
if [ -n "$RENDER_EXTERNAL_URL" ]; then
    export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"
    export ASSET_URL="${ASSET_URL:-$RENDER_EXTERNAL_URL}"
else
    export APP_URL="${APP_URL:-https://hoyos-ecommerce.onrender.com}"
    export ASSET_URL="${ASSET_URL:-https://hoyos-ecommerce.onrender.com}"
fi

# ==============================================================================
# STAGE 2: Storage Permissions & Runtime Directory Setup
# ==============================================================================
echo "[HOYOS] Preparing storage and cache directories..."

mkdir -p /var/www/html/storage/app/public \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure public storage symlink exists
php artisan storage:link --force 2>/dev/null || true

# ==============================================================================
# STAGE 3: Cache Invalidation (Prevent Stale Container State)
# ==============================================================================
echo "[HOYOS] Flushing stale bootstrap caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# ==============================================================================
# STAGE 4: Database Verification & Automated Migrations
# ==============================================================================
echo "[HOYOS] Checking database connectivity..."

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
        echo "[HOYOS] Database connection verified successfully."
        break
    fi

    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "[HOYOS] Waiting for database to accept connections ($RETRY_COUNT/$MAX_RETRIES)..."
    sleep 2
done

if [ "$DB_CONNECTED" = "true" ]; then
    RUN_MIGRATIONS="${RUN_MIGRATIONS_ON_START:-true}"
    if [ "$RUN_MIGRATIONS" = "true" ]; then
        echo "[HOYOS] Executing database migrations..."
        php artisan migrate --force || echo "[HOYOS] WARNING: Migration encountered an issue, proceeding with startup..."

        echo "[HOYOS] Seeding initial database records (if needed)..."
        php artisan db:seed --force || true
    else
        echo "[HOYOS] RUN_MIGRATIONS_ON_START is false; skipping automatic migrations."
    fi
else
    echo "======================================================================"
    echo "[HOYOS] WARNING: Database connection failed after ${MAX_RETRIES} attempts!"
    echo "[HOYOS] DB_CONNECTION=${DB_CONNECTION:-pgsql}"
    if [ -n "$DATABASE_URL" ]; then
        echo "[HOYOS] DATABASE_URL is set, but the database server is not reachable."
    else
        echo "[HOYOS] DATABASE_URL is NOT set in environment variables!"
        echo "[HOYOS] DB_HOST=${DB_HOST:-127.0.0.1}"
        if [ "${DB_HOST:-127.0.0.1}" = "127.0.0.1" ] || [ "${DB_HOST:-localhost}" = "localhost" ]; then
            echo "[HOYOS] >>> CONFIGURATION REMINDER <<<"
            echo "[HOYOS] DB_HOST is set to localhost/127.0.0.1."
            echo "[HOYOS] On Render, PostgreSQL runs on a separate managed instance."
            echo "[HOYOS] In Render Dashboard -> Web Service -> Environment, add DATABASE_URL"
            echo "[HOYOS] with your Render PostgreSQL Internal Database URL."
        fi
    fi
    echo "======================================================================"

    # Allow container to continue booting if FAIL_ON_DB_ERROR is false (default)
    # This prevents Render deploy crashes and keeps the web server accessible.
    FAIL_ON_DB_ERROR="${FAIL_ON_DB_ERROR:-false}"
    if [ "$FAIL_ON_DB_ERROR" = "true" ]; then
        echo "[HOYOS] FAIL_ON_DB_ERROR is true. Terminating container startup."
        exit 1
    else
        echo "[HOYOS] Proceeding with web server startup so health checks and HTTP traffic can be served."
    fi
fi

# ==============================================================================
# STAGE 5: Production Cache Warming
# ==============================================================================
if [ -n "$APP_KEY" ]; then
    echo "[HOYOS] Warming production route, view, and config caches..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# ==============================================================================
# STAGE 6: Launch Process Supervisor (Nginx + PHP-FPM)
# ==============================================================================
echo "[HOYOS] Startup sequence complete. Launching Nginx and PHP-FPM via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
