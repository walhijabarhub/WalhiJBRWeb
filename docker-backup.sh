#!/bin/bash
# ─────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Automated Database Backup Script
# Usage:
#   ./docker-backup.sh               # Routine backup
#   ./docker-backup.sh --pre-deploy  # Pre-deployment snapshot
# ─────────────────────────────────────────────────────────────
set -e

BACKUP_DIR="${BACKUP_DIR:-./storage/backups}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
PREFIX="walhi_prod"

if [ "${1:-}" = "--pre-deploy" ]; then
    PREFIX="walhi_predeploy"
    echo "🛡️ Membuat snapshot database pra-deployment: $TIMESTAMP"
else
    echo "📦 Memulai backup rutin database WALHI: $TIMESTAMP"
fi

mkdir -p "$BACKUP_DIR"

# 1. Check if production MySQL container is running
if docker ps --format '{{.Names}}' | grep -q "^walhi_prod_db$"; then
    echo "💾 Menjalankan mysqldump dari container walhi_prod_db..."
    BACKUP_FILE="$BACKUP_DIR/${PREFIX}_mysql_$TIMESTAMP.sql.gz"
    
    docker exec walhi_prod_db sh -c 'exec mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' | gzip > "$BACKUP_FILE"
    echo "✅ Backup MySQL berhasil disimpan: $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"

# 2. Check if SQLite database exists
elif [ -f "./storage/app/database.sqlite" ]; then
    echo "💾 Mem-backup basis data SQLite..."
    BACKUP_FILE="$BACKUP_DIR/${PREFIX}_sqlite_$TIMESTAMP.sqlite.gz"
    gzip -c "./storage/app/database.sqlite" > "$BACKUP_FILE"
    echo "✅ Backup SQLite berhasil disimpan: $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"

else
    echo "⚠️ Tidak ada container database MySQL yang berjalan atau file database.sqlite yang ditemukan."
    exit 1
fi

# 3. Clean up routine backups older than 30 days (keep pre-deploy backups for at least 60 days)
find "$BACKUP_DIR" -type f -name "walhi_prod_*" -mtime +30 -exec rm {} \; 2>/dev/null || true
find "$BACKUP_DIR" -type f -name "walhi_predeploy_*" -mtime +60 -exec rm {} \; 2>/dev/null || true
echo "🧹 Pembersihan berkas backup lama selesai."

# 4. Optional: Sync to off-site cloud storage (AWS S3, Cloudflare R2, or Rclone)
if [ -n "${OFFSITE_BACKUP_BUCKET:-}" ]; then
    echo "☁️ Mengunggah backup ke off-site storage: $OFFSITE_BACKUP_BUCKET..."
    if command -v aws >/dev/null 2>&1; then
        aws s3 cp "$BACKUP_FILE" "s3://$OFFSITE_BACKUP_BUCKET/" --only-show-errors && echo "✅ Sinkronisasi S3 off-site selesai."
    elif command -v rclone >/dev/null 2>&1; then
        rclone copy "$BACKUP_FILE" "$OFFSITE_BACKUP_BUCKET" && echo "✅ Sinkronisasi Rclone off-site selesai."
    else
        echo "ℹ️ AWS CLI / Rclone tidak ditemukan di host. Backup tetap tersimpan aman di direktori lokal."
    fi
fi

echo "🎉 Backup database selesai dengan sukses!"
