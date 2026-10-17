#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Backup database + uploaded files
#
# Usage:
#   ./docker-backup.sh               # routine backup (cron, daily)
#   ./docker-backup.sh --pre-deploy  # snapshot before a deployment
#
# Environment (optional, read from the shell or .env):
#   BACKUP_DIR               default /var/backups/walhi
#   OFFSITE_BACKUP_TARGET    rclone remote ("r2:walhi-backups") or s3://bucket/path
#   BACKUP_ALERT_WEBHOOK     URL that receives a JSON POST when a backup fails
#   BACKUP_RETENTION_DAYS    routine backups to keep (default 14)
#
# Exits non-zero on any failure so cron/deploy can react to it.
# ─────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$APP_DIR/.env" ]; then
    # Only pick up the backup-related settings from .env (values are not evaluated).
    while IFS='=' read -r key value; do
        value="${value%\"}"; value="${value#\"}"
        if [ -z "${!key:-}" ]; then export "$key=$value"; fi
    done < <(grep -E '^(BACKUP_DIR|OFFSITE_BACKUP_TARGET|BACKUP_ALERT_WEBHOOK|BACKUP_RETENTION_DAYS)=' "$APP_DIR/.env" || true)
fi

BACKUP_DIR="${BACKUP_DIR:-/var/backups/walhi}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
DB_CONTAINER="${DB_CONTAINER:-walhi_prod_db}"
APP_CONTAINER="${APP_CONTAINER:-walhi_prod_app}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
PREFIX="walhi_prod"
[ "${1:-}" = "--pre-deploy" ] && PREFIX="walhi_predeploy"

alert() {
    echo "❌ $1" >&2
    if [ -n "${BACKUP_ALERT_WEBHOOK:-}" ]; then
        curl -fsS -m 10 -H 'Content-Type: application/json' \
            -d "{\"text\":\"[WALHI backup] $1 ($(hostname), $TIMESTAMP)\"}" \
            "$BACKUP_ALERT_WEBHOOK" >/dev/null 2>&1 || true
    fi
}
trap 'alert "Backup gagal pada baris $LINENO"' ERR

umask 077
mkdir -p "$BACKUP_DIR"
echo "📦 Backup ${PREFIX} — $TIMESTAMP → $BACKUP_DIR"

# 1. Database (MySQL)
if ! docker ps --format '{{.Names}}' | grep -q "^${DB_CONTAINER}$"; then
    alert "Container database ${DB_CONTAINER} tidak berjalan"
    exit 1
fi

DB_FILE="$BACKUP_DIR/${PREFIX}_mysql_$TIMESTAMP.sql.gz"
docker exec "$DB_CONTAINER" sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --no-tablespaces --single-transaction --quick --routines --triggers -u"$MYSQL_USER" "$MYSQL_DATABASE"' \
    | gzip > "$DB_FILE"

# pipefail already stops on a failed dump; also reject truncated or empty output.
gzip -t "$DB_FILE"
if ! gzip -dc "$DB_FILE" | tail -n 1 | grep -q "Dump completed"; then
    alert "Dump database tidak lengkap: $DB_FILE"
    exit 1
fi
echo "✅ Database: $DB_FILE ($(du -h "$DB_FILE" | cut -f1))"

# 2. Uploaded files (storage/app: private documents + public uploads)
FILES_FILE="$BACKUP_DIR/${PREFIX}_files_$TIMESTAMP.tar.gz"
if docker ps --format '{{.Names}}' | grep -q "^${APP_CONTAINER}$"; then
    docker exec "$APP_CONTAINER" tar -C /var/www/html/storage/app -czf - . > "$FILES_FILE"
else
    # The app container may be down (e.g. after a failed deploy): read the named
    # volumes directly so a fix can still be deployed with a fresh backup.
    PROJECT=$(docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' "$DB_CONTAINER")
    echo "ℹ️  ${APP_CONTAINER} tidak berjalan; membaca volume proyek ${PROJECT} langsung."
    docker run --rm \
        -v "${PROJECT}_walhi_prod_app_storage:/data:ro" \
        -v "${PROJECT}_walhi_prod_storage:/data/public:ro" \
        alpine:3.22 tar -C /data -czf - . > "$FILES_FILE"
fi
gzip -t "$FILES_FILE"
echo "✅ File unggahan: $FILES_FILE ($(du -h "$FILES_FILE" | cut -f1))"

# 3. Off-site copy (strongly recommended: a local-only backup dies with the server)
if [ -n "${OFFSITE_BACKUP_TARGET:-}" ]; then
    echo "☁️  Mengunggah ke $OFFSITE_BACKUP_TARGET ..."
    if [[ "$OFFSITE_BACKUP_TARGET" == s3://* ]]; then
        aws s3 cp "$DB_FILE" "$OFFSITE_BACKUP_TARGET/" --only-show-errors
        aws s3 cp "$FILES_FILE" "$OFFSITE_BACKUP_TARGET/" --only-show-errors
    else
        rclone copy "$DB_FILE" "$OFFSITE_BACKUP_TARGET"
        rclone copy "$FILES_FILE" "$OFFSITE_BACKUP_TARGET"
    fi
    echo "✅ Salinan off-site selesai."
else
    echo "⚠️  OFFSITE_BACKUP_TARGET belum diatur — backup hanya tersimpan di server ini." >&2
fi

# 4. Retention (routine backups by RETENTION_DAYS, pre-deploy snapshots 30 days)
find "$BACKUP_DIR" -type f -name "walhi_prod_*" -mtime +"$RETENTION_DAYS" -delete
find "$BACKUP_DIR" -type f -name "walhi_predeploy_*" -mtime +30 -delete

echo "🎉 Backup selesai."
