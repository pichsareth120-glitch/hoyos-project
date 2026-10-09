# ==============================================================================
# Stage 1: Build Frontend Assets (Vite, Tailwind CSS, Alpine.js)
# ==============================================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package manifests first for Docker caching
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --prefer-offline

# Copy frontend source files
COPY frontend/ ./

# Pre-create directory where vite writes production assets
RUN mkdir -p /backend/public

# Build production assets into /backend/public/build
RUN npm run build

# ==============================================================================
# Stage 2: Production PHP-FPM + Nginx Application Container
# ==============================================================================
FROM php:8.2-fpm-bookworm

LABEL maintainer="HOYOS Team" \
      description="HOYOS E-Commerce Laravel 12 on Render"

WORKDIR /var/www/html/backend

# Install required system packages and extension libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        bcmath \
        opcache \
        gd \
        xml \
        ctype \
        fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install official Composer binary
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy application directories into container
COPY backend/ /var/www/html/backend/
COPY frontend/views/ /var/www/html/frontend/views/

# Copy compiled frontend assets from frontend-builder stage into backend/public/build
COPY --from=frontend-builder /backend/public/build /var/www/html/backend/public/build

# Install production PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

# Copy server and daemon configurations
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-hoyos.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /var/www/html/backend/../docker/entrypoint.sh 2>/dev/null || true

# Ensure storage directories exist with appropriate permissions
RUN mkdir -p \
        /var/www/html/backend/storage/app/public \
        /var/www/html/backend/storage/framework/cache \
        /var/www/html/backend/storage/framework/sessions \
        /var/www/html/backend/storage/framework/views \
        /var/www/html/backend/storage/logs \
        /var/www/html/backend/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/backend/storage /var/www/html/backend/bootstrap/cache \
    && chmod -R 775 /var/www/html/backend/storage /var/www/html/backend/bootstrap/cache \
    && chmod -R 755 /var/www/html/backend/public

# Render injects $PORT at runtime (defaults to 10000)
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
