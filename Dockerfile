# ShopSync production image for Render (Docker runtime).
#
# Multi-stage build:
#   1. "assets"  — installs npm dependencies and produces the Vite build.
#   2. runtime   — PHP 8.3-FPM + nginx, with Composer dependencies installed
#                  from composer-production.json (platform-pinned to PHP 8.3,
#                  no dev packages).
#
# The container serves Laravel's public/ directory and listens on Render's
# dynamically assigned $PORT (default 8080 for local runs).

# ---------------------------------------------------------------- Stage 1 ---
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN npm ci

# Placeholder Reverb values: Vite bakes import.meta.env at build time and
# resources/js/echo.js is imported unconditionally by app.js, so a build
# without these would break the entire browser bundle. Real-time chat via
# Reverb is optional — the UI falls back to refresh-to-see-messages (see
# README's deployment section).
ENV VITE_REVERB_APP_KEY=build-placeholder \
    VITE_REVERB_HOST=localhost \
    VITE_REVERB_PORT=8080 \
    VITE_REVERB_SCHEME=http

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources/ ./resources/
RUN npm run build

# ---------------------------------------------------------------- Stage 2 ---
FROM php:8.3-fpm

# nginx-light: the core HTTP server (gzip, rewrite, FastCGI) without the
# rarely-used extra modules. curl is for the container health check, unzip
# for Composer's dist package downloads.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx-light \
        curl \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# pdo_mysql is required for the TiDB Cloud (MySQL-compatible) database;
# opcache for production performance. Everything else Laravel needs
# (ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, session,
# tokenizer) is already compiled into the official PHP image.
RUN docker-php-ext-install pdo_mysql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Application source. .dockerignore keeps .env, vendor/, node_modules/,
# storage/ uploads and the dev composer files out of the build context.
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY public/ public/
COPY resources/ resources/
COPY routes/ routes/
COPY storage/app/public/ storage/app/public/
COPY artisan artisan
# composer.json is required at runtime: Laravel's package manifest
# (artisan package:discover) reads it from the app root.
COPY composer.json composer-production.json composer-production.lock ./

# Vite output built in stage 1.
COPY --from=assets /app/public/build public/build

# Install production dependencies. The lock file was resolved with the
# "platform.php": "8.3.0" pin, so every package is PHP 8.3-compatible.
RUN COMPOSER=composer-production.json composer install \
        --no-dev --prefer-dist --no-interaction --no-progress \
        --optimize-autoloader \
    && rm -rf /root/.composer/cache

# Laravel needs these directories even though their contents are not shipped
# (storage/ is excluded from the build context so uploaded files and logs
# never enter an image layer).
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs

# Precompile Blade views and the event discovery cache. config:cache is
# intentionally NOT run here: it must happen at container start, where the
# real environment variables (Render dashboard) are available.
RUN php artisan view:cache \
    && php artisan event:cache

# PHP production settings (opcache, logging to stderr, upload limits).
COPY docker/php-prod.ini /usr/local/etc/php/conf.d/zz-shopsync.ini

# PHP-FPM pool override: socket listener, per-process limits and — critically
# for Laravel — clear_env=no so the container's environment variables reach
# PHP workers.
COPY docker/php-fpm-zz.conf /usr/local/etc/php-fpm.d/zz-shopsync.conf

# nginx vhost template with a __PORT__ placeholder; the startup script
# rewrites it with Render's $PORT before nginx starts.
COPY docker/nginx.conf /etc/nginx/conf.d/shopsync.conf
RUN rm -f /etc/nginx/sites-enabled/default /var/www/html/index.nginx-debian.html \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Startup script: permissions, storage link, config cache, then
# php-fpm + nginx under a small supervisor loop.
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Code stays root-owned (read-only for the app); only runtime-writable
# directories belong to www-data, the user nginx and php-fpm workers run as.
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS "http://127.0.0.1:${PORT:-8080}/up" > /dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/start.sh"]
