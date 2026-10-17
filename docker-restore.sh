#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Restore a backup made by docker-backup.sh
#
# Usage:
#   ./docker-restore.sh <mysql_backup.sql.gz> [files_backup.tar.gz]
#
# Restores the database (and optionally storage/app) into the running stack.
# The site is put in maintenance mode during the restore.
# ─────────────────────────────────────────────────────────────
set -euo pipefail

DB_BACKUP="${1:?Gunakan: $0 <mysql_backup.sql.gz> [files_backup.tar.gz]}"
FILES_BACKUP="${2:-}"
DB_CONTAINER="${DB_CONTAINER:-walhi_prod_db}"
APP_CONTAINER="${APP_CONTAINER:-walhi_prod_app}"

gzip -t "$DB_BACKUP"
[ -z "$FILES_BACKUP" ] || gzip -t "$FILES_BACKUP"

read -r -p "Restore $DB_BACKUP ke database produksi? Data saat ini akan DITIMPA. Ketik 'restore': " answer
[ "$answer" = "restore" ] || { echo "Dibatalkan."; exit 1; }

docker exec "$APP_CONTAINER" php artisan down --render="errors::503" || true
trap 'docker exec "$APP_CONTAINER" php artisan up >/dev/null 2>&1 || true' EXIT

echo "🗄️  Memulihkan database..."
gunzip -c "$DB_BACKUP" | docker exec -i "$DB_CONTAINER" sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql -u"$MYSQL_USER" "$MYSQL_DATABASE"'

if [ -n "$FILES_BACKUP" ]; then
    echo "📁 Memulihkan storage/app..."
    docker exec -i "$APP_CONTAINER" tar -C /var/www/html/storage/app -xzf - < "$FILES_BACKUP"
    docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/html/storage/app
fi

docker exec "$APP_CONTAINER" php artisan cache:clear
echo "✅ Restore selesai."
