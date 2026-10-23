# ─── Stage 1: Node.js Asset Build ───────────────────────────
# Node 24 LTS (Node 20 reached end of life in April 2026), pinned by tag and digest.
FROM node:24.22.1-alpine@sha256:06d7adf3e0ef61a13f1d28682726366f97a15aa1888c934d3ecec86c33918b53 AS frontend
WORKDIR /app
COPY package*.json vite.config.js tailwind.config.js postcss.config.js ./
RUN npm ci --ignore-scripts
COPY resources ./resources
COPY public ./public
RUN npm run build

# ─── Stage 2: PHP Application Container (PHP-FPM) ─────────────
# Pinned to an exact PHP patch release and image digest. A floating tag is not enough:
# php:8.4-fpm-alpine3.22 stopped receiving updates in June 2026 and stayed on 8.4.22,
# missing the fixes for CVE-2026-109618/109619 (8.4.27). CI refuses images older than
# MIN_PHP_VERSION. To update: pick the newest php:8.4.x-fpm-alpine3.y tag, copy its
# index digest (docker buildx imagetools inspect <tag>), and change both together.
FROM php:8.5.11-fpm-alpine3.24@sha256:fa01fb1645cd0fc566a5f146b099adace33b906571f972f71f2182a7c12d1cd7

# phpredis is built from its GitHub release tarball (checksum pinned) instead of
# "pecl install": pecl.php.net outages broke image builds and therefore deploys.
# Same source as the PECL package; lzf support stays off, as with pecl's defaults.
# Update the version and the SHA-256 of the GitHub source tarball together.
ARG PHPREDIS_VERSION=6.2.0
ARG PHPREDIS_SHA256=470333b27cbf9485d36b610b81300c06491a6575f22c6801a9cefc55285ed123

# Install PHP extensions. Only the shared libraries they link against stay in the
# runtime image; compilers, headers (-dev) and download tools are a virtual package
# removed in the same layer, which keeps them out of the attack surface.
RUN apk add --no-cache \
    bash \
    su-exec \
    fcgi \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    sqlite-libs \
    oniguruma \
    icu-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        sqlite-dev \
        oniguruma-dev \
        icu-dev \
    && curl -fsSL --retry 5 --retry-delay 3 -o /tmp/phpredis.tar.gz \
        https://github.com/phpredis/phpredis/archive/refs/tags/${PHPREDIS_VERSION}.tar.gz \
    && echo "${PHPREDIS_SHA256}  /tmp/phpredis.tar.gz" | sha256sum -c - \
    && mkdir -p /usr/src/php/ext/redis \
    && tar -xzf /tmp/phpredis.tar.gz -C /usr/src/php/ext/redis --strip-components=1 \
    && rm /tmp/phpredis.tar.gz \
    && docker-php-ext-install redis \
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
        opcache \
    && apk del .build-deps

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
# git/unzip are only needed while Composer fetches packages.
RUN apk add --no-cache --virtual .composer-deps git unzip \
    && composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    && apk del .composer-deps

# Copy application source
COPY . .

# Copy built assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Complete composer autoloader
RUN composer dump-autoload --optimize --no-dev

# Copy custom PHP-FPM and OPcache configurations (8 GB RAM / 2 vCPU VPS tuning)
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/security.ini /usr/local/etc/php/conf.d/zz-security.ini

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
