# ─── Stage 1: Node.js Asset Build ───────────────────────────
# Base images are pinned to a minor version so rebuilds are reproducible.
FROM node:26.12-alpine AS frontend
WORKDIR /app
COPY package*.json vite.config.js tailwind.config.js postcss.config.js ./
RUN npm ci --ignore-scripts
COPY resources ./resources
COPY public ./public
RUN npm run build

# ─── Stage 2: PHP Application Container (PHP-FPM) ─────────────
FROM php:8.4-fpm-alpine3.22

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    oniguruma-dev \
    icu-dev \
    sed \
    su-exec \
    fcgi \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis-6.2.0 \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source
COPY . .

# Copy built assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Complete composer autoloader
RUN composer dump-autoload --optimize --no-dev

# Copy custom PHP-FPM and OPcache configurations (8 GB RAM / 2 vCPU VPS tuning)
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Ensure all Laravel storage framework directories exist in the image
RUN mkdir -p storage/framework/views \
             storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/testing \
             storage/logs \
             storage/app/public \
             bootstrap/cache

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["php-fpm"]
