#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Production deployment (Docker Compose, single VPS)
#
# Usage: ./deploy.sh [branch] [commit]
#   branch  default: production
#   commit  default: tip of origin/<branch> (CI passes the exact commit it tested)
#
# Flow:
#   1. Pre-flight checks (.env, required variables, disk space)
#   2. Database + file backup (a failed backup aborts the deploy)
#   3. Check out the commit and obtain its image, tagged with the commit SHA
#      (pulled from GHCR when APP_IMAGE points to a registry, built locally otherwise)
#   4. Maintenance mode (shared through Redis, so it survives container recreation)
#   5. Migrations run with the NEW image before any container is swapped
#   6. Swap app/worker/scheduler/web to the new image
#   7. Health check through nginx; on failure roll back to the previous image
#
# Database safety: only `migrate --force` runs. Never migrate:fresh/refresh/reset,
# never seeders. Schema changes are not rolled back automatically; restore from
# the pre-deploy backup if a migration must be undone (see docker-restore.sh).
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# `git reset --hard` below may rewrite this file while bash is still reading it,
# so run from a private copy.
if [ -z "${WALHI_DEPLOY_COPY:-}" ]; then
    copy="$(mktemp /tmp/walhi-deploy.XXXXXX)"
    cp "$0" "$copy"
    WALHI_DEPLOY_COPY="$copy" exec bash "$copy" "$@"
fi
trap 'rm -f "$WALHI_DEPLOY_COPY"' EXIT

TARGET_BRANCH="${1:-production}"
TARGET_COMMIT="${2:-}"
APP_DIR="${APP_DIR:-/var/www/walhi_app}"
COMPOSE_FILE="docker-compose.prod.yml"
# Extra compose files (space-separated) and container names can be overridden for staging/testing.
COMPOSE_FILES="${COMPOSE_FILES:-$COMPOSE_FILE}"
export APP_CONTAINER="${APP_CONTAINER:-walhi_prod_app}"
export DB_CONTAINER="${DB_CONTAINER:-walhi_prod_db}"
WEB_CONTAINER="${WEB_CONTAINER:-walhi_prod_nginx}"
DEPLOY_LOG="${DEPLOY_LOG:-/var/log/walhi_deploy.log}"
STATE_DIR="$APP_DIR/.deploy"

log() {
    local msg="[$(date +'%Y-%m-%d %H:%M:%S')] $1"
    echo "$msg"
    echo "$msg" >> "$DEPLOY_LOG" 2>/dev/null || true
}

dc() {
    local args=() file
    for file in $COMPOSE_FILES; do args+=(-f "$file"); done
    docker compose "${args[@]}" "$@"
}

# Set KEY=VALUE in .env (replace when present, append otherwise). Portable (no sed -i).
set_env() {
    local tmp
    tmp="$(mktemp)"
    if grep -q "^$1=" .env; then
        awk -v k="$1" -v v="$2" 'index($0, k "=") == 1 { print k "=" v; next } { print }' .env > "$tmp"
    else
        cat .env > "$tmp"
        echo "$1=$2" >> "$tmp"
    fi
    cat "$tmp" > .env
    rm -f "$tmp"
}

env_value() {
    grep -E "^$1=" .env | tail -n 1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' || true
}

MAINTENANCE_ON=false
maintenance_off() {
    if [ "$MAINTENANCE_ON" = true ]; then
        dc exec -T app php artisan up >/dev/null 2>&1 || true
        MAINTENANCE_ON=false
    fi
}

fail() {
    log "❌ $1"
    maintenance_off
    exit 1
}

# Any unexpected error must not leave the site in maintenance mode.
trap 'log "❌ Error tak terduga di baris $LINENO"; maintenance_off' ERR

cd "$APP_DIR"
mkdir -p "$STATE_DIR"

echo "═══════════════════════════════════════════════════════════════"
echo "  🌿 WALHI Jawa Barat — Deploy (branch: $TARGET_BRANCH)"
echo "═══════════════════════════════════════════════════════════════"

# ── 1. Pre-flight ─────────────────────────────────────────────────────────────
log "🔍 [1/7] Pre-flight checks..."
[ -f "$COMPOSE_FILE" ] || fail "$COMPOSE_FILE tidak ditemukan di $APP_DIR."
[ -f .env ] || fail ".env produksi tidak ditemukan di $APP_DIR."
docker compose version >/dev/null 2>&1 || fail "Docker Compose v2 tidak tersedia."

for var in APP_KEY DB_PASSWORD DB_ROOT_PASSWORD REDIS_PASSWORD; do
    [ -n "$(env_value "$var")" ] || fail "Variabel $var wajib diisi di .env (lihat DEPLOYMENT.md → Upgrade)."
done

# A server that already has a certificate must keep serving HTTPS.
SERVER_NAME="$(env_value NGINX_SERVER_NAME)"
if [ -n "$SERVER_NAME" ] && [ -f "/etc/letsencrypt/live/$SERVER_NAME/fullchain.pem" ] && [ "$(env_value NGINX_CONF)" != "https" ]; then
    log "   🔒 Sertifikat untuk $SERVER_NAME ditemukan; NGINX_CONF diset ke https."
    set_env NGINX_CONF https
fi

AVAILABLE_KB=$(df -k . | awk 'NR==2 {print $4}')
[ "$AVAILABLE_KB" -ge 2097152 ] || fail "Ruang disk kurang dari 2 GB (${AVAILABLE_KB} KB)."

# ── 2. Backup ─────────────────────────────────────────────────────────────────
log "💾 [2/7] Backup database & file sebelum deploy..."
if docker ps --format '{{.Names}}' | grep -q "^${DB_CONTAINER}$"; then
    if [ "${SKIP_BACKUP:-false}" = "true" ]; then
        log "   ⚠️  SKIP_BACKUP=true — backup dilewati atas permintaan operator."
    else
        ./docker-backup.sh --pre-deploy || fail "Backup gagal; deploy dibatalkan agar data tetap aman."
    fi
else
    log "   ℹ️  Database belum berjalan (deploy pertama) — backup dilewati."
fi

# ── 3. Code & image ───────────────────────────────────────────────────────────
log "📥 [3/7] Mengambil kode $TARGET_BRANCH..."
git fetch origin "$TARGET_BRANCH"
git reset --hard "${TARGET_COMMIT:-origin/$TARGET_BRANCH}"
# Exactly 12 characters, matching the tag CI pushes (git --short may return more).
NEW_TAG="$(git rev-parse HEAD | cut -c1-12)"
NEW_COMMIT="$(git rev-parse HEAD)"

# Last healthy release: "<image> <tag> <commit>" (older state files hold only a tag).
PREVIOUS_IMAGE="" PREVIOUS_TAG="" PREVIOUS_COMMIT=""
if [ -f "$STATE_DIR/last_good" ]; then
    read -r PREVIOUS_IMAGE PREVIOUS_TAG PREVIOUS_COMMIT < "$STATE_DIR/last_good" || true
fi
PREVIOUS_IMAGE="${PREVIOUS_IMAGE:-$(env_value APP_IMAGE)}"
PREVIOUS_IMAGE="${PREVIOUS_IMAGE:-walhi_larafel-app}"
PREVIOUS_TAG="${PREVIOUS_TAG:-$(cat "$STATE_DIR/last_good_tag" 2>/dev/null || env_value IMAGE_TAG)}"
PREVIOUS_TAG="${PREVIOUS_TAG:-latest}"
PREVIOUS_COMMIT="${PREVIOUS_COMMIT:-}"

APP_IMAGE="${APP_IMAGE:-$(env_value APP_IMAGE)}"
APP_IMAGE="${APP_IMAGE:-walhi_larafel-app}"
log "   Versi: ${PREVIOUS_IMAGE}:${PREVIOUS_TAG} ➔ ${APP_IMAGE}:${NEW_TAG}"

export APP_IMAGE IMAGE_TAG="$NEW_TAG"
if [[ "$APP_IMAGE" == */* ]]; then
    log "🐳 Menarik image $APP_IMAGE:$NEW_TAG dari registry..."
    docker pull "$APP_IMAGE:$NEW_TAG" || fail "Image $APP_IMAGE:$NEW_TAG tidak ditemukan di registry."
else
    log "🐳 Membangun image $APP_IMAGE:$NEW_TAG di server..."
    dc build app || fail "Build image gagal."
fi

# ── 4. Maintenance mode ───────────────────────────────────────────────────────
log "🚧 [4/7] Mengaktifkan maintenance mode..."
DEPLOY_SECRET="${DEPLOY_MAINTENANCE_KEY:-$(openssl rand -hex 16)}"
if dc ps --status running --services 2>/dev/null | grep -q '^app$'; then
    if dc exec -T app php artisan down --secret="$DEPLOY_SECRET" --retry=60 --render="errors::503" >/dev/null; then
        MAINTENANCE_ON=true
        log "   Bypass selama deploy: https://<domain>/$DEPLOY_SECRET (tidak dicatat di repo)"
    fi
fi

# ── 5. Migrations with the new image, before swapping ─────────────────────────
log "🗄️  [5/7] Migrasi database (non-destruktif) dengan image baru..."
dc up -d --no-build --wait db redis || fail "Database/Redis tidak sehat; deploy dibatalkan."
if ! dc run --rm --no-deps -e CONTAINER_ROLE=migrate app php artisan migrate --force; then
    fail "Migrasi gagal. Versi lama tetap berjalan; periksa log lalu pulihkan dari backup pra-deploy jika skema berubah sebagian."
fi

# ── 6/7 helpers ──────────────────────────────────────────────────────────────
wait_healthy() {
    for _ in $(seq 1 40); do
        local app web
        app=$(docker inspect -f '{{.State.Health.Status}}' "$APP_CONTAINER" 2>/dev/null || echo none)
        web=$(docker inspect -f '{{.State.Health.Status}}' "$WEB_CONTAINER" 2>/dev/null || echo none)
        [ "$app" = "healthy" ] && [ "$web" = "healthy" ] && return 0
        sleep 3
    done
    return 1
}

http_check() {
    local port host
    if [ "$(env_value NGINX_CONF)" = "https" ]; then
        port="$(env_value HTTPS_PORT)"; port="${port:-443}"; host="$(env_value NGINX_SERVER_NAME)"
        curl -fsS -o /dev/null --max-time 10 --resolve "$host:$port:127.0.0.1" "https://$host:$port/up"
    else
        port="$(env_value HTTP_PORT)"; port="${port:-80}"
        curl -fsS -o /dev/null --max-time 10 -H "Host: localhost" "http://127.0.0.1:$port/up"
    fi
}

# Start a release: nginx config comes from bind-mounted templates that git may have
# changed, so web is restarted to re-render them (compose does not recreate it).
start_release() {
    set_env APP_IMAGE "$1"
    set_env IMAGE_TAG "$2"
    APP_IMAGE="$1" IMAGE_TAG="$2" dc up -d --no-build --remove-orphans && dc restart web
}

rollback() {
    log "❌ $1 — rollback ke ${PREVIOUS_IMAGE}:${PREVIOUS_TAG}..."
    trap - ERR
    # Restore the previous commit too: compose and nginx templates are bind-mounted from it.
    if [ -n "$PREVIOUS_COMMIT" ]; then
        git reset --hard "$PREVIOUS_COMMIT" >/dev/null || true
    fi
    start_release "$PREVIOUS_IMAGE" "$PREVIOUS_TAG" || true
    maintenance_off
    if wait_healthy && http_check; then
        log "   Rollback selesai; ${PREVIOUS_TAG} kembali melayani. Migrasi yang sudah berjalan TIDAK dibatalkan otomatis."
    else
        log "   ⚠️  Rollback dijalankan tetapi health check masih gagal — periksa server segera."
    fi
    exit 1
}

# ── 6. Swap containers ────────────────────────────────────────────────────────
log "🔄 [6/7] Menjalankan container versi $NEW_TAG..."
start_release "$APP_IMAGE" "$NEW_TAG" || rollback "Gagal menjalankan container $NEW_TAG"

# ── 7. Health check & rollback ────────────────────────────────────────────────
log "🩺 [7/7] Health check..."
HEALTHY=false
if wait_healthy; then
    maintenance_off
    for _ in 1 2 3 4 5; do
        if http_check; then HEALTHY=true; break; fi
        sleep 3
    done
fi

[ "$HEALTHY" = true ] || rollback "Health check gagal untuk $NEW_TAG"

echo "$APP_IMAGE $NEW_TAG $NEW_COMMIT" > "$STATE_DIR/last_good"
echo "$NEW_TAG" > "$STATE_DIR/last_good_tag"
log "✅ Deploy $NEW_TAG berhasil dan sehat."

# Keep the current and previous image for rollback; remove older ones.
for tag in $(docker images "$APP_IMAGE" --format '{{.Tag}}'); do
    case "$tag" in
        "$NEW_TAG"|"$PREVIOUS_TAG"|latest) ;;
        *) docker rmi "$APP_IMAGE:$tag" >/dev/null 2>&1 || true ;;
    esac
done
docker image prune -f >/dev/null 2>&1 || true

log "═══════════════════════════════════════════════════════════════"
log "  🎉 DEPLOY BERHASIL: $TARGET_BRANCH @ $NEW_TAG"
log "═══════════════════════════════════════════════════════════════"
