#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Restore test for a backup made by docker-backup.sh
#
# Usage:
#   ./docker-restore-test.sh <mysql_backup.sql.gz> [files_backup.tar.gz]
#
# Proves that a backup can be restored, WITHOUT touching the running site:
#   1. verifies <file>.sha256 (when present) and gzip integrity;
#   2. imports the dump into a throwaway MySQL 8.0 container with no network
#      (never walhi_prod_db, never a production volume);
#   3. reports tables, row counts and the latest migration;
#   4. extracts the files archive into a temporary directory and checks that every
#      document referenced by `contents.image_url` is present in it.
# Everything is removed afterwards. Exit codes: 0 = restorable and complete,
# 1 = restore failed, 2 = restored but referenced documents are missing.
#
# Run it on a staging server, or on the VPS at a quiet time (the test container is
# capped at 1 GB RAM). Download off-site copies first, e.g.:
#   rclone copy r2:walhi-backups/walhi_prod_mysql_YYYYMMDD_HHMMSS.sql.gz .
# ─────────────────────────────────────────────────────────────
set -euo pipefail

DB_BACKUP="${1:?Gunakan: $0 <mysql_backup.sql.gz> [files_backup.tar.gz]}"
FILES_BACKUP="${2:-}"
TEST_CONTAINER="walhi_restore_test_$$"
WORK_DIR="$(mktemp -d)"
DB_NAME="restore_test"
ROOT_PW="$(openssl rand -hex 16)"

# shellcheck disable=SC2329 # invoked by the EXIT trap
cleanup() {
    docker rm -f "$TEST_CONTAINER" >/dev/null 2>&1 || true
    rm -rf "$WORK_DIR"
}
trap cleanup EXIT

# sha256sum on Linux, shasum on macOS (same output and --check format).
sha256_check() {
    if command -v sha256sum >/dev/null 2>&1; then sha256sum -c --quiet "$1"; else shasum -a 256 -c --quiet "$1"; fi
}

verify_archive() {
    local file="$1"
    [ -f "$file" ] || { echo "❌ Berkas tidak ditemukan: $file" >&2; exit 1; }
    if [ -f "$file.sha256" ]; then
        (cd "$(dirname "$file")" && sha256_check "$(basename "$file").sha256") \
            || { echo "❌ Checksum tidak cocok: $file" >&2; exit 1; }
        echo "✅ Checksum cocok: $(basename "$file")"
    else
        echo "⚠️  Tanpa $file.sha256 (backup lama) — hanya integritas gzip yang diperiksa."
    fi
    gzip -t "$file"
}

echo "🧪 Uji restore — $(date +'%Y-%m-%d %H:%M:%S')"
verify_archive "$DB_BACKUP"
[ -z "$FILES_BACKUP" ] || verify_archive "$FILES_BACKUP"

# 1. Throwaway database: no network, no published port, no named volume.
echo "🐳 Menjalankan MySQL sementara ($TEST_CONTAINER, tanpa jaringan)..."
docker run -d --rm --name "$TEST_CONTAINER" --network none --memory 1g \
    -e MYSQL_ROOT_PASSWORD="$ROOT_PW" -e MYSQL_DATABASE="$DB_NAME" \
    mysql:8.0 --innodb-buffer-pool-size=128M >/dev/null

sql() {
    docker exec -i -e MYSQL_PWD="$ROOT_PW" "$TEST_CONTAINER" mysql -uroot --batch --skip-column-names "$DB_NAME" "$@"
}

for _ in $(seq 1 60); do
    if docker exec -e MYSQL_PWD="$ROOT_PW" "$TEST_CONTAINER" mysql -uroot -e "SELECT 1" "$DB_NAME" >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

START=$(date +%s)
echo "🗄️  Mengimpor dump..."
if ! gunzip -c "$DB_BACKUP" | sql; then
    echo "❌ Impor database gagal." >&2
    exit 1
fi
echo "✅ Impor selesai dalam $(( $(date +%s) - START )) detik."

echo ""
echo "── Isi database ──"
echo "Jumlah tabel     : $(sql -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME'")"
for table in contents users donations comments subscribers audit_logs; do
    count=$(sql -e "SELECT COUNT(*) FROM \`$table\`" 2>/dev/null || echo "tabel tidak ada")
    printf '%-17s: %s\n' "$table" "$count"
done
echo "Migrasi terakhir : $(sql -e "SELECT migration FROM migrations ORDER BY id DESC LIMIT 1" 2>/dev/null || echo '-')"

STATUS=0
if [ -n "$FILES_BACKUP" ]; then
    echo ""
    echo "── Berkas ──"
    mkdir -p "$WORK_DIR/files"
    tar -C "$WORK_DIR/files" -xzf "$FILES_BACKUP"
    echo "Dokumen privat   : $(find "$WORK_DIR/files/private/documents" -type f 2>/dev/null | wc -l | tr -d ' ')"
    echo "Unggahan publik  : $(find "$WORK_DIR/files/public/uploads" -type f 2>/dev/null | wc -l | tr -d ' ')"

    # Same lookup as the download endpoint: documents/<basename>, private disk first.
    missing=0
    while IFS=$'\t' read -r id slug path; do
        name="$(basename "$path")"
        if [ ! -f "$WORK_DIR/files/private/documents/$name" ] && [ ! -f "$WORK_DIR/files/public/documents/$name" ]; then
            [ "$missing" -eq 0 ] && echo "❌ Dokumen dirujuk database tetapi tidak ada di arsip:"
            echo "   #$id $slug → $path"
            missing=$((missing + 1))
        fi
    done < <(sql -e "SELECT id, slug, image_url FROM contents WHERE deleted_at IS NULL AND image_url IS NOT NULL AND image_url NOT LIKE 'http%' AND image_url NOT LIKE '%uploads/%' AND (image_url LIKE '%documents/%' OR image_url REGEXP '\\\\.(pdf|docx?|xlsx?|odt|rtf|csv|txt)\$')")
    if [ "$missing" -eq 0 ]; then
        echo "✅ Semua dokumen yang dirujuk database ada di arsip."
    else
        echo "   Total hilang: $missing"
        STATUS=2
    fi
fi

echo ""
if [ "$STATUS" -eq 0 ]; then
    echo "🎉 Backup dapat dipulihkan dan lengkap. Catat tanggal backup (RPO) dan durasi uji ini (RTO)."
else
    echo "⚠️  Database dapat dipulihkan, tetapi backup TIDAK lengkap (dokumen hilang)."
fi
exit "$STATUS"
