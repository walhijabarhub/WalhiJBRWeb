#!/usr/bin/env bash
# Hermetic restore regression tests. No Docker daemon, containers, or PHP/vendor needed.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/walhi-restore-regression.XXXXXX")"
trap 'rm -rf "$WORK_DIR"' EXIT
BIN="$WORK_DIR/bin"
mkdir -p "$BIN"
# No inherited PATH inside the restore process: even an unexpected command cannot
# fall through to a real docker executable. The mock never delegates to Docker.
for tool in bash gzip gunzip cat; do
    ln -s "$(command -v "$tool")" "$BIN/$tool"
done
printf 'SELECT 1;\n' | gzip > "$WORK_DIR/database.sql.gz"
mkdir -p "$WORK_DIR/files"
printf 'fixture\n' > "$WORK_DIR/files/document.txt"
tar -C "$WORK_DIR/files" -czf "$WORK_DIR/files.tar.gz" .

cat > "$BIN/docker" <<'MOCK'
#!/bin/bash
set -euo pipefail
log() { printf '%s\n' "$1" >> "$MOCK_DIR/calls"; }
fail_at() { [ "$MOCK_FAIL" != "$1" ] || exit "$2"; }
case "$*" in
    *restore-maintenance-state*)
        log state
        fail_at state 40
        if [ "$MOCK_FAIL" = verify-state ] && [ -f "$MOCK_DIR/down-attempted" ]; then exit 42; fi
        cat "$MOCK_DIR/maintenance"
        ;;
    *restore-health-check*)
        log health
        # Health must run while traffic is stopped and maintenance is active.
        [ "$(cat "$MOCK_DIR/web")" = false ] || exit 98
        [ "$(cat "$MOCK_DIR/maintenance")" = down ] || exit 98
        fail_at health 36
        ;;
    'inspect -f {{.State.Running}} '*)
        container="${!#}"
        log "inspect:$container"
        cat "$MOCK_DIR/$container"
        ;;
    'stop --time 60 '*)
        container="${!#}"
        log "stop:$container"
        fail_at stop 37
        printf false > "$MOCK_DIR/$container"
        ;;
    'start '*)
        container="${!#}"
        log "start:$container"
        fail_at start 38
        printf true > "$MOCK_DIR/$container"
        ;;
    'exec app php artisan down --render=errors::503')
        log down
        fail_at down 31
        : > "$MOCK_DIR/down-attempted"
        if [ "$MOCK_FAIL" = false-down ]; then exit 0; fi
        if [ "$MOCK_FAIL" = redown ] && [ -f "$MOCK_DIR/cache-cleared" ]; then exit 39; fi
        printf down > "$MOCK_DIR/maintenance"
        ;;
    'exec app php artisan up')
        log up
        # Simulate partial success too: an up failure must reassert down.
        printf up > "$MOCK_DIR/maintenance"
        fail_at up 35
        ;;
    *' exec mysql '*)
        log import
        cat >/dev/null
        for container in web worker scheduler; do
            [ "$(cat "$MOCK_DIR/$container")" = false ] || exit 98
        done
        [ "$(cat "$MOCK_DIR/maintenance")" = down ] || exit 98
        fail_at import 32
        ;;
    'exec -i app tar -C /var/www/html/storage/app -xzf -')
        log extract
        cat >/dev/null
        fail_at extract 33
        ;;
    'exec app chown -R www-data:www-data /var/www/html/storage/app')
        log chown
        fail_at chown 41
        ;;
    'exec app php artisan cache:clear')
        log cache
        printf up > "$MOCK_DIR/maintenance"
        : > "$MOCK_DIR/cache-cleared"
        fail_at cache 34
        ;;
    *) printf 'Unexpected mock docker invocation: %s\n' "$*" >&2; exit 97 ;;
esac
MOCK
chmod +x "$BIN/docker"

assert_call() { grep -qx "$1" "$MOCK_DIR/calls" || { cat "$MOCK_DIR/output"; echo "Missing call: $1" >&2; exit 1; }; }
assert_no_call() { if grep -qx "$1" "$MOCK_DIR/calls"; then echo "Unexpected call: $1" >&2; exit 1; fi; }
assert_state() { [ "$(cat "$MOCK_DIR/$1")" = "$2" ] || { echo "Wrong $1 state (expected $2)" >&2; exit 1; }; }

run_case() {
    local failure="$1" expected="$2" initial="${3:-up}" stopped="${4:-}" files="${5:-yes}" status=0
    MOCK_DIR="$WORK_DIR/case-${failure:-success}-$initial-$stopped-$files"
    mkdir -p "$MOCK_DIR"
    : > "$MOCK_DIR/calls"
    printf '%s' "$initial" > "$MOCK_DIR/maintenance"
    for container in web worker scheduler; do
        if [ "$container" = "$stopped" ] || [ "$stopped" = all ]; then printf false; else printf true; fi > "$MOCK_DIR/$container"
    done
    local args=("$WORK_DIR/database.sql.gz")
    if [ "$files" = yes ]; then args+=("$WORK_DIR/files.tar.gz"); fi
    printf 'restore\n' | env -i PATH="$BIN" MOCK_DIR="$MOCK_DIR" MOCK_FAIL="$failure" \
        APP_CONTAINER=app DB_CONTAINER=db WEB_CONTAINER=web WORKER_CONTAINER=worker SCHEDULER_CONTAINER=scheduler \
        "$BIN/bash" "$ROOT/docker-restore.sh" "${args[@]}" > "$MOCK_DIR/output" 2>&1 || status=$?
    if [ "$status" -ne "$expected" ]; then
        cat "$MOCK_DIR/output" "$MOCK_DIR/calls"
        echo "FAIL: $failure returned $status, expected $expected" >&2
        exit 1
    fi
    if [ "$expected" -ne 0 ]; then
        grep -q 'docs/operations.md' "$MOCK_DIR/output"
        case "$failure" in
            up|start) ;;
            *) assert_no_call up ;;
        esac
        assert_no_call start:web
        if [ "$failure" != start ]; then
            assert_no_call start:worker
            assert_no_call start:scheduler
        fi
    fi
    echo "PASS: ${failure:-success} (initial=$initial, stopped=${stopped:-none}, files=$files)"
}

run_case down 31
assert_no_call import
assert_no_call extract
assert_no_call cache

for scenario in import:32 extract:33 chown:41 cache:34 health:36 up:35; do
    run_case "${scenario%:*}" "${scenario#*:}"
    assert_state maintenance down
    for container in web worker scheduler; do assert_state "$container" false; done
done

run_case state 40
assert_no_call down
assert_no_call import
run_case verify-state 42
assert_no_call import
run_case false-down 1
assert_no_call import
run_case stop 37
assert_no_call import
run_case redown 39
assert_no_call health
assert_state web false
run_case start 38
assert_state maintenance down
assert_state web false

run_case '' 0
assert_state maintenance up
for container in web worker scheduler; do assert_state "$container" true; done
# Assert the entire safety-critical order, not only the presence of artisan up.
cat > "$WORK_DIR/expected" <<'EXPECTED'
state
inspect:web
inspect:scheduler
inspect:worker
down
state
stop:web
inspect:web
stop:scheduler
inspect:scheduler
stop:worker
inspect:worker
import
extract
chown
cache
down
state
health
up
state
start:scheduler
start:worker
start:web
EXPECTED
diff -u "$WORK_DIR/expected" "$MOCK_DIR/calls"

run_case '' 0 down
assert_no_call up
assert_no_call start:worker
assert_no_call start:scheduler
assert_state maintenance down
assert_state web true

run_case '' 0 up worker
assert_no_call start:worker
assert_state worker false

run_case '' 0 up all
for container in web worker scheduler; do
    assert_no_call "start:$container"
    assert_state "$container" false
done

run_case cache 34 down
assert_state maintenance down

run_case '' 0 up '' no
assert_no_call extract
assert_no_call chown
assert_call up

echo 'All restore regression tests passed (mock Docker only).'
