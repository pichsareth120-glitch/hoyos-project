# ==============================================================================
# Stage 1: Build Frontend Assets (Vite, Tailwind CSS, Alpine.js)
# ==============================================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package manifests first for Docker caching
COPY package.json package-lock.json ./
RUN npm ci --no-audit --prefer-offline

# Copy frontend source files
COPY resources/ resources/
COPY vite.config.js tailwind.config.js ./

# Build production assets into public/build
RUN npm run build

# ==============================================================================
# Stage 2: Production PHP-FPM + Nginx Application Container
# ==============================================================================
FROM php:8.2-fpm-bookworm

LABEL maintainer="HOYOS Team" \
      description="HOYOS E-Commerce Laravel 12 on Render"

WORKDIR /var/www/html

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

# Copy application code (filtered by .dockerignore)
COPY . /var/www/html

# Copy compiled frontend assets from frontend-builder stage
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Install production PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

# Copy server and daemon configurations
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-hoyos.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Ensure storage directories exist with appropriate permissions
RUN mkdir -p \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Render injects $PORT at runtime (defaults to 10000)
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
