#!/usr/bin/env bash
# WALHI Jawa Barat — Restore a backup made by docker-backup.sh.
# Usage: ./docker-restore.sh <mysql_backup.sql.gz> [files_backup.tar.gz]
# Fail closed: errors never reopen the site or restart background processing.
set -euo pipefail

DB_BACKUP="${1:?Gunakan: $0 <mysql_backup.sql.gz> [files_backup.tar.gz]}"
FILES_BACKUP="${2:-}"
DB_CONTAINER="${DB_CONTAINER:-walhi_prod_db}"
APP_CONTAINER="${APP_CONTAINER:-walhi_prod_app}"
WEB_CONTAINER="${WEB_CONTAINER:-walhi_prod_nginx}"
WORKER_CONTAINER="${WORKER_CONTAINER:-walhi_prod_worker}"
SCHEDULER_CONTAINER="${SCHEDULER_CONTAINER:-walhi_prod_scheduler}"

gzip -t "$DB_BACKUP"
[ -z "$FILES_BACKUP" ] || gzip -t "$FILES_BACKUP"

read -r -p "Restore $DB_BACKUP ke database produksi? Data saat ini akan DITIMPA. Ketik 'restore': " answer
[ "$answer" = "restore" ] || { echo "Dibatalkan."; exit 1; }

STEP="memeriksa keadaan awal"
MAINTENANCE_ATTEMPTED=false
RUNNING_CONTAINERS=()

# shellcheck disable=SC2329 # invoked by the EXIT trap
on_exit() {
    local status=$?
    trap - EXIT
    if [ "$status" -ne 0 ]; then
        echo "❌ Restore gagal ($status), tahap: $STEP. Tidak ada pembukaan situs otomatis." >&2
        if [ "$MAINTENANCE_ATTEMPTED" = true ]; then
            # cache:clear can remove Redis maintenance state; up can partially succeed.
            # Keep ingress and background writers stopped even if reasserting down fails.
            local container
            for container in ${RUNNING_CONTAINERS[@]+"${RUNNING_CONTAINERS[@]}"}; do
                docker stop --time 60 "$container" >/dev/null 2>&1 \
                    || echo "⚠️ Gagal menghentikan $container; isolasi trafik/proses secara manual!" >&2
            done
            docker exec "$APP_CONTAINER" php artisan down --render="errors::503" \
                || echo "⚠️ Maintenance tidak dapat dipastikan; jangan jalankan nginx/worker/scheduler!" >&2
        fi
        echo "Periksa log Docker dan backup; database/berkas mungkin dipulihkan sebagian. Jangan jalankan artisan up sebelum data diperbaiki dan health check lulus." >&2
        echo "Pemulihan manual: ikuti docs/operations.md bagian 4 (Backup & Restore). Container yang dihentikan tidak dimulai ulang otomatis." >&2
    fi
    exit "$status"
}
trap on_exit EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

maintenance_state() {
    docker exec "$APP_CONTAINER" php -r '
        // restore-maintenance-state: bootstrap the configured driver, including cached config.
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        echo $app->isDownForMaintenance() ? "down" : "up";
    '
}

INITIAL_MAINTENANCE="$(maintenance_state)"
case "$INITIAL_MAINTENANCE" in
    down|up) ;;
    *) echo "Status maintenance tidak valid; restore dibatalkan." >&2; exit 1 ;;
esac

# Snapshot before stopping anything; never start a container that was already stopped.
for container in "$WEB_CONTAINER" "$SCHEDULER_CONTAINER" "$WORKER_CONTAINER"; do
    running="$(docker inspect -f '{{.State.Running}}' "$container")"
    case "$running" in
        true) RUNNING_CONTAINERS+=("$container") ;;
        false) ;;
        *) echo "Status container $container tidak valid." >&2; exit 1 ;;
    esac
done

STEP="mengaktifkan maintenance"
MAINTENANCE_ATTEMPTED=true
docker exec "$APP_CONTAINER" php artisan down --render="errors::503"
CURRENT_MAINTENANCE="$(maintenance_state)"
[ "$CURRENT_MAINTENANCE" = down ]

STEP="menghentikan nginx, scheduler, dan worker"
# The + expansion also supports empty arrays with nounset on macOS Bash 3.2.
for container in ${RUNNING_CONTAINERS[@]+"${RUNNING_CONTAINERS[@]}"}; do
    docker stop --time 60 "$container" >/dev/null
    running="$(docker inspect -f '{{.State.Running}}' "$container")"
    [ "$running" = false ]
done

STEP="impor database"
echo "🗄️  Memulihkan database..."
gunzip -c "$DB_BACKUP" | docker exec -i "$DB_CONTAINER" sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysql -u"$MYSQL_USER" "$MYSQL_DATABASE"'

if [ -n "$FILES_BACKUP" ]; then
    STEP="ekstraksi storage/app"
    echo "📁 Memulihkan storage/app..."
    docker exec -i "$APP_CONTAINER" tar -C /var/www/html/storage/app -xzf - < "$FILES_BACKUP"
    STEP="izin storage/app"
    docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/html/storage/app
fi

STEP="membersihkan cache"
# Ingress stays stopped: Redis cache flushing can also erase the maintenance flag.
docker exec "$APP_CONTAINER" php artisan cache:clear
STEP="menegakkan ulang maintenance setelah cache clear"
docker exec "$APP_CONTAINER" php artisan down --render="errors::503"
CURRENT_MAINTENANCE="$(maintenance_state)"
[ "$CURRENT_MAINTENANCE" = down ]

STEP="health check aplikasi/database/cache"
# Exercise the real /up HTTP kernel (exempt from maintenance), including DiagnosingHealth.
# No public bypass token and no need to reopen nginx for this check.
docker exec "$APP_CONTAINER" php -r '
    // restore-health-check
    define("LARAVEL_START", microtime(true));
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create(rtrim(config("app.url"), "/")."/up");
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    if ($response->getStatusCode() !== 200) {
        fwrite(STDERR, "Health /up gagal: HTTP ".$response->getStatusCode().PHP_EOL);
        exit(1);
    }
'

if [ "$INITIAL_MAINTENANCE" = up ]; then
    STEP="menonaktifkan maintenance"
    docker exec "$APP_CONTAINER" php artisan up
    CURRENT_MAINTENANCE="$(maintenance_state)"
    [ "$CURRENT_MAINTENANCE" = up ]
    STEP="memulai ulang background processing"
    for container in ${RUNNING_CONTAINERS[@]+"${RUNNING_CONTAINERS[@]}"}; do
        [ "$container" = "$WEB_CONTAINER" ] || docker start "$container" >/dev/null
    done
else
    echo "ℹ️ Situs sudah maintenance sebelum restore: tetap maintenance; worker/scheduler tetap dihentikan."
fi

# Reopen ingress last, only after every restore/check command has succeeded.
STEP="memulai ulang nginx"
for container in ${RUNNING_CONTAINERS[@]+"${RUNNING_CONTAINERS[@]}"}; do
    if [ "$container" = "$WEB_CONTAINER" ]; then
        docker start "$container" >/dev/null
    fi
done
echo "✅ Restore selesai; health check aplikasi/database/cache lulus. Verifikasi HTTPS /up dan data sesuai runbook."
