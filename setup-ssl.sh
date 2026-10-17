#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Production SSL (HTTPS) Setup
#
# Issues a Let's Encrypt certificate with the WEBROOT method: the running nginx
# container serves /.well-known/acme-challenge/ from docker/certbot/www, so the
# web server never has to be stopped and automatic renewal keeps working.
#
# Usage: sudo ./setup-ssl.sh [domain] [email]
#   domain  default: walhijabar.co.id (certificate also covers www.<domain>)
#   email   default: $CERTBOT_EMAIL or the maintainer address below
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

DOMAIN="${1:-walhijabar.co.id}"
EMAIL="${2:-${CERTBOT_EMAIL:-irsalshydiq@gmail.com}}"
APP_DIR="${APP_DIR:-/var/www/walhi_app}"
COMPOSE="docker compose -f $APP_DIR/docker-compose.prod.yml"
WEBROOT="$APP_DIR/docker/certbot/www"
HOOK="/etc/letsencrypt/renewal-hooks/deploy/reload-walhi-nginx.sh"

echo "==================================================================="
echo "  🔒 WALHI Jawa Barat — Setup SSL HTTPS (Let's Encrypt, webroot)"
echo "  Domain: $DOMAIN & www.$DOMAIN"
echo "==================================================================="

if [ "$(id -u)" -ne 0 ]; then
    echo "❌ Skrip ini wajib dijalankan sebagai root."
    exit 1
fi

cd "$APP_DIR"

if [ ! -f .env ]; then
    echo "❌ File $APP_DIR/.env tidak ditemukan."
    exit 1
fi

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

# 1. DNS must already point at this server
echo "🔍 [1/6] Memeriksa DNS untuk $DOMAIN..."
RESOLVED_IP=$(getent ahostsv4 "$DOMAIN" 2>/dev/null | awk '{print $1}' | head -n 1 || true)
if [ -z "$RESOLVED_IP" ]; then
    echo "❌ Domain $DOMAIN belum mengarah ke server mana pun. Tunggu propagasi DNS lalu ulangi."
    exit 1
fi
echo "   ✅ DNS mengarah ke: $RESOLVED_IP"

# 2. Certbot
echo "📦 [2/6] Memastikan Certbot terpasang..."
command -v certbot >/dev/null 2>&1 || { apt-get update -y && apt-get install -y certbot; }

# 3. Serve the ACME challenge over plain HTTP (nginx stays up the whole time)
echo "🌐 [3/6] Menjalankan nginx mode HTTP untuk challenge ACME..."
mkdir -p "$WEBROOT"
set_env NGINX_SERVER_NAME "$DOMAIN"
if [ ! -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    set_env NGINX_CONF http
fi
$COMPOSE up -d web

# 4. Reload nginx after every issue/renewal (used by certbot's systemd timer). Installed
#    before issuing so a forced re-issue reloads nginx as well.
echo "🔁 [4/6] Memasang hook reload nginx setelah penerbitan/perpanjangan..."
mkdir -p "$(dirname "$HOOK")"
cat > "$HOOK" <<HOOK_EOF
#!/bin/sh
$COMPOSE exec -T web nginx -s reload
HOOK_EOF
chmod 755 "$HOOK"

# 5. Issue the certificate. An existing certificate that was issued with the old
#    standalone method is re-issued once so its renewal config switches to webroot.
echo "📜 [5/6] Menerbitkan sertifikat..."
EXTRA_ARGS=()
RENEWAL_CONF="/etc/letsencrypt/renewal/$DOMAIN.conf"
if [ -f "$RENEWAL_CONF" ] && grep -q "authenticator = standalone" "$RENEWAL_CONF"; then
    echo "   ℹ️  Sertifikat lama memakai metode standalone; diterbitkan ulang dengan webroot."
    EXTRA_ARGS+=(--force-renewal)
fi

certbot certonly --webroot -w "$WEBROOT" \
    --cert-name "$DOMAIN" \
    -d "$DOMAIN" -d "www.$DOMAIN" \
    --non-interactive --agree-tos -m "$EMAIL" \
    --keep-until-expiring ${EXTRA_ARGS[@]+"${EXTRA_ARGS[@]}"}

# Remove the old cron entry that stopped/restarted the web container.
(crontab -l 2>/dev/null | grep -Fv 'certbot renew' || true) | crontab -
# Certbot's packaged systemd timer renews twice a day; add a cron fallback if it is missing.
if ! systemctl list-timers 2>/dev/null | grep -q certbot; then
    (crontab -l 2>/dev/null; echo "17 3 * * * certbot renew --quiet") | crontab -
fi

# 6. Switch the stack to HTTPS
echo "⚙️  [6/6] Mengaktifkan HTTPS..."
set_env NGINX_CONF https
set_env APP_URL "https://$DOMAIN"
set_env SESSION_SECURE_COOKIE true
$COMPOSE up -d
# Make sure the running nginx serves the certificate that was just issued.
$COMPOSE restart web

echo "🧪 Menguji perpanjangan otomatis (dry run)..."
if certbot renew --dry-run --cert-name "$DOMAIN"; then
    echo "   ✅ Perpanjangan otomatis berfungsi."
else
    echo "   ⚠️  Dry run perpanjangan gagal — periksa /var/log/letsencrypt/letsencrypt.log"
fi

echo ""
echo "==================================================================="
echo "  🎉 HTTPS aktif: https://$DOMAIN"
echo "  Sertifikat diperpanjang otomatis (webroot) dan nginx di-reload."
echo "==================================================================="
