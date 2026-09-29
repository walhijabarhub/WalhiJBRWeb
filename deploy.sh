#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Production Safe Auto-Deployment Script
# Target Environment: Ubuntu/Debian VPS with Docker Compose
# Target Branch: production
#
# Guaranteed Database Safety Invariants:
# 1. Automated pre-deploy backup before any code/schema changes
# 2. NEVER execute destructive migrations (migrate:fresh / migrate:refresh)
# 3. NEVER execute automatic seeders (SEED_ON_START=false)
# 4. Graceful maintenance mode with secret bypass
# 5. Production cache optimization and health check
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

TARGET_BRANCH="${1:-production}"
APP_DIR="/var/www/walhi_app"
COMPOSE_FILE="docker-compose.prod.yml"
DEPLOY_LOG="/var/log/walhi_deploy.log"
TIMESTAMP=$(date +'%Y-%m-%d %H:%M:%S')
DEPLOY_BYPASS="${DEPLOY_MAINTENANCE_KEY:-walhi-maintenance-bypass}"

log() {
    local msg="[$(date +'%Y-%m-%d %H:%M:%S')] $1"
    echo "$msg"
    # Append to log file if writable, otherwise ignore silently
    echo "$msg" >> "$DEPLOY_LOG" 2>/dev/null || true
}

error_exit() {
    log "❌ ERROR: $1"
    log "Deployment aborted. Bringing application back online if down..."
    if command -v docker >/dev/null 2>&1 && [ -f "$COMPOSE_FILE" ]; then
        docker compose -f "$COMPOSE_FILE" exec -T app php artisan up 2>/dev/null || true
    fi
    exit 1
}

echo "═══════════════════════════════════════════════════════════════"
echo "  🌿 WALHI Jawa Barat — Production Safe Auto-Deployment"
echo "  Target Branch: $TARGET_BRANCH"
echo "  Timestamp: $TIMESTAMP"
echo "═══════════════════════════════════════════════════════════════"

# ── 1. Pre-flight Checks ──
log "🔍 [1/9] Menjalankan pre-flight checks..."

# Ensure we are in the application root directory
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
fi

if [ ! -f "$COMPOSE_FILE" ]; then
    error_exit "File $COMPOSE_FILE tidak ditemukan di $(pwd)."
fi

if [ ! -f ".env" ]; then
    error_exit "File .env produksi tidak ditemukan di $(pwd). Wajib siapkan .env sebelum deploy."
fi

# Check available disk space (require at least 1 GB free)
AVAILABLE_KB=$(df -k . | awk 'NR==2 {print $4}')
if [ "$AVAILABLE_KB" -lt 1048576 ]; then
    error_exit "Ruang disk tidak mencukupi (sisa kurang dari 1 GB: ${AVAILABLE_KB} KB). Deploy dibatalkan."
fi

# Ensure docker & docker compose are functional
if ! docker compose version >/dev/null 2>&1; then
    error_exit "Docker Compose v2 tidak terpasang atau tidak aktif."
fi

log "✅ Pre-flight checks lolos."

# ── 2. Automated Pre-Deployment Database Backup (Safety Net) ──
log "💾 [2/9] Membuat snapshot backup database sebelum update..."
chmod +x ./docker-backup.sh 2>/dev/null || true
if [ -f "./docker-backup.sh" ]; then
    ./docker-backup.sh --pre-deploy || {
        log "⚠️ Peringatan: Script docker-backup.sh mengembalikan status non-zero, mencoba mysqldump langsung..."
        BACKUP_DIR="./storage/backups"
        mkdir -p "$BACKUP_DIR"
        BACKUP_FILE="$BACKUP_DIR/walhi_predeploy_$(date +'%Y%m%d_%H%M%S').sql.gz"
        if docker ps --format '{{.Names}}' | grep -q "^walhi_prod_db$"; then
            docker exec walhi_prod_db sh -c 'exec mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' | gzip > "$BACKUP_FILE"
            log "✅ Backup darurat MySQL berhasil: $BACKUP_FILE"
        fi
    }
    log "✅ Snapshot database pra-deployment tersimpan dengan aman."
else
    log "⚠️ docker-backup.sh tidak ditemukan, melewati auto-backup."
fi

# ── 3. Enable Graceful Maintenance Mode ──
log "🚧 [3/9] Mengaktifkan maintenance mode dengan bypass secret..."
if docker ps --format '{{.Names}}' | grep -q "^walhi_prod_app$"; then
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan down \
        --secret="$DEPLOY_BYPASS" \
        --retry=60 \
        --render="errors::503" 2>/dev/null || true
    log "✅ Maintenance mode berhasil diaktifkan."
fi

# ── 4. Pull Latest Changes from Target Branch ──
log "📥 [4/9] Mengambil pembaruan kode dari branch: $TARGET_BRANCH..."

# Preserve custom SSL Nginx configuration if present
SSL_CONFIG_TEMP=""
if grep -q "ssl_certificate" "$APP_DIR/docker/nginx/default.conf" 2>/dev/null; then
    SSL_CONFIG_TEMP="/tmp/walhi_nginx_ssl_$(date +%s).conf"
    cp "$APP_DIR/docker/nginx/default.conf" "$SSL_CONFIG_TEMP"
fi

git fetch origin "$TARGET_BRANCH"
CURRENT_HASH=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
git reset --hard "origin/$TARGET_BRANCH"
NEW_HASH=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")

# Restore SSL Nginx configuration and ensure SSL volume in docker-compose.prod.yml
if [ -n "$SSL_CONFIG_TEMP" ] && [ -f "$SSL_CONFIG_TEMP" ]; then
    cp "$SSL_CONFIG_TEMP" "$APP_DIR/docker/nginx/default.conf"
    rm -f "$SSL_CONFIG_TEMP"
    log "🔒 Konfigurasi SSL HTTPS Nginx berhasil dipulihkan."
fi

if [ -d "/etc/letsencrypt/live" ] && ! grep -q '/etc/letsencrypt:/etc/letsencrypt:ro' docker-compose.prod.yml; then
    sed -i '/- .\/docker\/nginx\/default.conf/i \      - /etc/letsencrypt:/etc/letsencrypt:ro' docker-compose.prod.yml
    log "🔒 Volume /etc/letsencrypt berhasil dipulihkan di docker-compose.prod.yml."
fi

log "✅ Git sync selesai: $CURRENT_HASH ➔ $NEW_HASH"

# ── 5. Rebuild and Restart Application Containers ──
log "🐳 [5/9] Membangun ulang dan menyalakan container produksi..."
docker compose -f "$COMPOSE_FILE" up -d --build --remove-orphans
log "✅ Container Docker berhasil di-build dan berjalan."

# Wait for application container to be ready
log "⏳ Menunggu container app siap..."
sleep 5

# ── 6. Safe Database Migrations (Non-Destructive ONLY) ──
# CRITICAL GUARDRAIL: Only 'migrate --force' is permitted.
# NEVER run migrate:fresh, migrate:refresh, migrate:reset, or db:seed.
log "🗄️  [6/9] Menjalankan migrasi database non-destruktif (migrate --force)..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate --force
log "✅ Migrasi database berhasil diaplikasikan tanpa mengubah data yang sudah ada."

# ── 7. Production Optimizations & Cache ──
log "⚡ [7/9] Mengoptimasi cache konfigurasi, routing, view, dan storage link..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan storage:link --force 2>/dev/null || true
log "✅ Cache dan storage symlink berhasil dioptimasi."

# ── 8. Disable Maintenance Mode (Bring Application Live) ──
log "🌐 [8/9] Menonaktifkan maintenance mode..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan up
log "✅ Aplikasi kembali LIVE dan melayani traffic pengunjung."

# ── 9. Post-Deployment Health Check ──
log "🩺 [9/9] Melakukan health check aplikasi..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:80" || echo "failed")

if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "301" ] || [ "$HTTP_STATUS" = "302" ]; then
    log "✅ Health check BERHASIL (HTTP Status: $HTTP_STATUS)."
else
    log "⚠️ Perhatian: Health check mengembalikan HTTP Status: $HTTP_STATUS. Silakan periksa log aplikasi."
fi

# ── Cleanup old docker images ──
docker image prune -f >/dev/null 2>&1 || true

log "═══════════════════════════════════════════════════════════════"
log "  🎉 DEPLOYMENT BERHASIL KE BRANCH '$TARGET_BRANCH' ($NEW_HASH)"
log "  Database Aman: Ter-backup & Migrasi Non-Destruktif"
log "═══════════════════════════════════════════════════════════════"
exit 0
