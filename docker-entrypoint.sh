#!/bin/bash
set -e

echo "═══════════════════════════════════════════════════════════"
echo "  WALHI Jabar — Docker Entrypoint"
echo "═══════════════════════════════════════════════════════════"

# ── 1. Ensure all required Laravel storage directories exist ──
CONTAINER_ROLE="${CONTAINER_ROLE:-app}"
echo "[1/8] Checking storage directories (role: ${CONTAINER_ROLE})..."
mkdir -p storage/framework/views \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         storage/app/private/documents \
         bootstrap/cache

# ── 2. Minimal permission setup without expensive recursive scan ──
echo "[2/8] Setting runtime permissions..."
chown www-data:www-data storage storage/framework storage/framework/* storage/logs storage/app storage/app/public storage/app/private storage/app/private/documents bootstrap/cache 2>/dev/null || true
chmod 775 storage storage/framework storage/framework/* storage/logs storage/app storage/app/public storage/app/private storage/app/private/documents bootstrap/cache 2>/dev/null || true

# ── 3. Generate .env from Docker environment variables ──
# Always regenerate .env to ensure Docker env vars take precedence.
# This avoids sed/parsing issues and stale config.
echo "[3/7] Generating .env from environment variables..."

ADMIN_PASS_VAL="${ADMIN_PASSWORD:-}"
if [ -z "$ADMIN_PASS_VAL" ]; then
    ADMIN_PASS_VAL=$(tr -dc 'A-Za-z0-9' < /dev/urandom 2>/dev/null | head -c 16 || date +%s | sha256sum | head -c 16)
fi

SECURE_COOKIE_VAL="${SESSION_SECURE_COOKIE:-}"
if [ -z "$SECURE_COOKIE_VAL" ]; then
    if [ "${APP_ENV:-local}" = "production" ]; then
        SECURE_COOKIE_VAL="true"
    else
        SECURE_COOKIE_VAL="null"
    fi
fi

cat > .env <<ENVFILE
APP_NAME="${APP_NAME:-WALHI Jawa Barat}"
APP_ENV=${APP_ENV:-local}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost:8000}
APP_KEY=${APP_KEY:-}

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID
APP_MAINTENANCE_DRIVER=${APP_MAINTENANCE_DRIVER:-file}
APP_MAINTENANCE_STORE=${APP_MAINTENANCE_STORE:-database}

BCRYPT_ROUNDS=12

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=${LOG_LEVEL:-warning}
LOG_DAILY_DAYS=${LOG_DAILY_DAYS:-30}

DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-/var/www/html/storage/app/database.sqlite}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=${SECURE_COOKIE_VAL}

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=${FILESYSTEM_DISK:-public}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

CACHE_STORE=${CACHE_STORE:-file}
CACHE_PREFIX=${CACHE_PREFIX:-walhi_cache_}

REDIS_CLIENT=${REDIS_CLIENT:-phpredis}
REDIS_HOST=${REDIS_HOST:-127.0.0.1}
REDIS_PORT=${REDIS_PORT:-6379}
REDIS_PASSWORD=${REDIS_PASSWORD:-null}

TRUSTED_PROXIES=${TRUSTED_PROXIES:-}

MAIL_MAILER=${MAIL_MAILER:-log}
MAIL_HOST=${MAIL_HOST:-smtp.mailtrap.io}
MAIL_PORT=${MAIL_PORT:-2525}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-kontak@walhijabar.co.id}"
MAIL_FROM_NAME="${MAIL_FROM_NAME:-WALHI Jawa Barat}"

MIDTRANS_SERVER_KEY=${MIDTRANS_SERVER_KEY:-}
MIDTRANS_CLIENT_KEY=${MIDTRANS_CLIENT_KEY:-}
MIDTRANS_IS_PRODUCTION=${MIDTRANS_IS_PRODUCTION:-false}

VITE_APP_NAME="${APP_NAME:-WALHI Jawa Barat}"

ADMIN_EMAIL=${ADMIN_EMAIL:-admin@walhijabar.co.id}
ADMIN_PASSWORD=${ADMIN_PASS_VAL}
EDITOR_EMAIL=${EDITOR_EMAIL:-editor@walhijabar.co.id}
EDITOR_PASSWORD=${EDITOR_PASSWORD:-}
FORCE_ADMIN_PASSWORD_RESET=${FORCE_ADMIN_PASSWORD_RESET:-false}
ENVFILE
chown www-data:www-data .env 2>/dev/null || true
chmod 640 .env 2>/dev/null || chmod 600 .env 2>/dev/null || true

# ── 4. Ensure SQLite database file exists ──
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/storage/app/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    if [ ! -f "$DB_FILE" ]; then
        echo "[4/7] Creating SQLite database at $DB_FILE..."
        touch "$DB_FILE"
        chmod 666 "$DB_FILE"
    else
        echo "[4/7] SQLite database already exists at $DB_FILE"
    fi
fi

# ── 5. Generate APP_KEY if empty ──
CURRENT_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2- || true)
if [ -z "$CURRENT_KEY" ] && [ "${APP_ENV:-local}" = "production" ]; then
    # A key generated here would change on every restart and invalidate sessions and
    # encrypted data (2FA secrets, encrypted session payloads). Refuse to start instead.
    echo "❌ APP_KEY kosong di production. Setel APP_KEY di .env (php artisan key:generate --show)." >&2
    exit 1
elif [ -z "$CURRENT_KEY" ]; then
    echo "[5/7] Generating Application Key (non-production only)..."
    php artisan key:generate --force
else
    echo "[5/7] APP_KEY already set, skipping..."
fi

# ── 6. Create storage link if missing (web-facing app container only) ──
if [ "$CONTAINER_ROLE" = "app" ] && [ ! -L public/storage ]; then
    echo "[6/7] Creating storage symlink..."
    php artisan storage:link --force 2>/dev/null || true
else
    echo "[6/7] Storage link already exists, skipping..."
fi

# ── Sync public assets to shared public volume (for Nginx container) ──
if [ "$CONTAINER_ROLE" = "app" ] && [ -d "/var/www/html/public_shared" ]; then
    if [ ! -f "/var/www/html/public_shared/.assets_synced" ] || [ "/var/www/html/public/build" -nt "/var/www/html/public_shared/.assets_synced" ]; then
        echo "       Syncing updated public assets to shared Nginx volume..."
        cp -ru /var/www/html/public/. /var/www/html/public_shared/ 2>/dev/null || cp -r /var/www/html/public/. /var/www/html/public_shared/
        touch /var/www/html/public_shared/.assets_synced 2>/dev/null || true
    else
        echo "       Shared public assets already up-to-date, skipping sync..."
    fi
fi

# ── 7. Run Database Migrations & Seeding ──
if [ "${CLEAR_CACHE_ON_START:-false}" = "true" ]; then
    echo "       Clearing caches..."
    php artisan config:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
fi

if [ "$CONTAINER_ROLE" = "app" ] && [ "${MIGRATE_ON_START:-false}" = "true" ]; then
    echo "[7/8] Running database migrations (MIGRATE_ON_START=true)..."
    php artisan migrate --force
else
    echo "[7/8] Skipping automatic migrations (MIGRATE_ON_START=false)."
    echo "       Tip: Execute migrations explicitly during deploy:"
    echo "       docker compose -f docker-compose.prod.yml exec app php artisan migrate --force"
fi

if [ "$CONTAINER_ROLE" = "app" ] && [ "${SEED_ON_START:-false}" = "true" ]; then
    echo "       Seeding database..."
    php artisan db:seed --force
fi

# ── 8. Production Optimizations (if production & debug false) ──
if [ "${APP_ENV:-local}" = "production" ] && [ "${APP_DEBUG:-false}" = "false" ]; then
    echo "[8/8] Caching configuration, routes, and views for production..."
    # Fail loudly: a broken config or route file must stop the container, not be hidden.
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  ✅ WALHI Jabar application service is ready"
echo "  📧 Admin: ${ADMIN_EMAIL:-admin@walhi-jabar.org}"
echo "  🔑 Password: (set via ADMIN_PASSWORD env var)"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Execute the main command (php-fpm or passed CMD)
exec "$@"
