#!/bin/bash
# ShopSync container startup. Runs as root, as php-fpm and nginx need to
# bind/drop privileges themselves; workers run as www-data.
#
# Deliberately does NOT run migrations: schema changes to the existing
# database are applied manually with `php artisan migrate --force` (README).
set -euo pipefail

PORT="${PORT:-8080}"
APP_DIR="/var/www/html"

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Provide the existing application key as an environment variable." >&2
    exit 1
fi

# Bake Render's dynamic port into the nginx vhost.
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/shopsync.conf

cd "$APP_DIR"

# Belt-and-braces for bind mounts in local testing; the image already
# contains these directories with correct ownership.
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/testing \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# public/storage → storage/app/public symlink for uploaded files
# (product images). Idempotent.
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

# Cache the configuration from the container's real environment. Route
# caching is intentionally skipped: routes/web.php uses closures, which
# route caching does not support.
php artisan config:cache

# php-fpm and nginx in the foreground; the container exits if either dies.
php-fpm -F &
FPM_PID=$!
nginx -g 'daemon off;' &
NGINX_PID=$!

on_term() {
    kill -TERM "$FPM_PID" "$NGINX_PID" 2>/dev/null || true
}
trap on_term TERM INT

STATUS=0
# Exit as soon as either process stops, taking the other with it.
wait -n "$FPM_PID" "$NGINX_PID" || STATUS=$?

kill -TERM "$FPM_PID" "$NGINX_PID" 2>/dev/null || true
wait "$FPM_PID" "$NGINX_PID" 2>/dev/null || true

exit "$STATUS"
