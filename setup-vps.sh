#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# WALHI Jawa Barat — Production VPS Auto-Provisioning Script
# Target: Ubuntu 22.04 / 24.04 LTS or Debian 12 (2 vCPU / 8 GB RAM / 100 GB NVMe)
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

echo "==================================================================="
echo "  🌿 WALHI Jawa Barat — Production VPS Setup"
echo "  Target: 2 vCPU / 8 GB RAM / 100 GB NVMe"
echo "==================================================================="

# 1. Ensure script is run as root
if [ "$(id -u)" -ne 0 ]; then
    echo "❌ Error: Skrip ini wajib dijalankan dengan hak akses root (gunakan sudo)."
    exit 1
fi

# 2. System updates
echo "📦 [1/7] Memperbarui repositori paket Ubuntu/Debian..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y
DEBIAN_FRONTEND=noninteractive apt-get install -y curl git ufw htop ca-certificates gnupg lsb-release

# 3. Setup 4 GB Swap file (Rekomendasi standar untuk 8 GB RAM / 100 GB NVMe)
echo "💾 [2/7] Memeriksa dan mengonfigurasi 4 GB Swap File..."
if [ ! -f /swapfile ]; then
    fallocate -l 4G /swapfile || dd if=/dev/zero of=/swapfile bs=1M count=4096
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    if ! grep -q '/swapfile' /etc/fstab; then
        echo '/swapfile none swap sw 0 0' >> /etc/fstab
    fi
    echo "✅ Swap 4 GB berhasil dibuat dan diaktifkan."
else
    echo "ℹ️  Swap file sudah ada, melewati pembuatan swap."
fi

# Optimasi swappiness (prioritaskan RAM, swap hanya untuk darurat)
sysctl -w vm.swappiness=10
sysctl -w vm.vfs_cache_pressure=50
if ! grep -q 'vm.swappiness=10' /etc/sysctl.conf; then
    echo "vm.swappiness=10" >> /etc/sysctl.conf
    echo "vm.vfs_cache_pressure=50" >> /etc/sysctl.conf
fi

# 4. Install Docker Engine & Docker Compose Plugin (Official)
echo "🐳 [3/7] Memeriksa instalasi Docker Engine..."
if ! command -v docker &> /dev/null; then
    echo "       Mengunduh dan menginstal Docker resmi..."
    curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
    sh /tmp/get-docker.sh
    rm -f /tmp/get-docker.sh
    systemctl enable docker
    systemctl start docker
    echo "✅ Docker Engine berhasil diinstal."
else
    echo "ℹ️  Docker sudah terinstal ($(docker --version))."
fi

# 5. Konfigurasi Firewall (UFW)
echo "🛡️  [4/7] Mengonfigurasi firewall UFW..."
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp comment 'SSH Port'
ufw allow 80/tcp comment 'HTTP Web'
ufw allow 443/tcp comment 'HTTPS Web'
ufw --force enable
echo "✅ Firewall aktif (Port 22, 80, 443 terbuka)."

# 6. Direktori kerja & backup
echo "📁 [5/7] Menyiapkan direktori produksi /var/www dan /var/backups/walhi..."
mkdir -p /var/www
install -d -m 700 /var/backups/walhi

# Backup harian memakai docker-backup.sh dari repo (satu mekanisme untuk cron & deploy).
# Skrip lama /usr/local/bin/walhi-backup.sh dihapus bila ada.
echo "⏰ [6/7] Menyiapkan backup harian (database + file unggahan)..."
rm -f /usr/local/bin/walhi-backup.sh
CRON_JOB="0 2 * * * cd /var/www/walhi_app && ./docker-backup.sh >> /var/log/walhi_backup.log 2>&1"
(crontab -l 2>/dev/null | grep -Fv "walhi-backup.sh" | grep -Fv "docker-backup.sh" ; echo "$CRON_JOB") | crontab -
echo "ℹ️  Atur OFFSITE_BACKUP_TARGET (rclone/S3) dan BACKUP_ALERT_WEBHOOK di .env agar backup juga tersimpan di luar server."

# 7. Hardening dasar
echo "🔐 [7/7] Hardening SSH & proteksi brute-force..."
DEBIAN_FRONTEND=noninteractive apt-get install -y fail2ban unattended-upgrades
cat > /etc/fail2ban/jail.d/walhi-sshd.local <<'JAIL'
[sshd]
enabled = true
maxretry = 5
findtime = 10m
bantime = 1h
JAIL
systemctl enable --now fail2ban
dpkg-reconfigure -f noninteractive unattended-upgrades || true

# Docker log rotation. (Setting "userland-proxy": false keeps IPv4 client IPs exact but
# drops IPv6 access unless Docker IPv6 is configured — see DEPLOYMENT.md, not done here.)
if [ ! -f /etc/docker/daemon.json ]; then
    echo '{ "log-driver": "json-file", "log-opts": { "max-size": "20m", "max-file": "5" } }' > /etc/docker/daemon.json
    systemctl restart docker
fi

# Password login for SSH is disabled only after confirming a working key exists,
# so the operator cannot lock themselves out.
if [ -s /root/.ssh/authorized_keys ] || ls /home/*/.ssh/authorized_keys >/dev/null 2>&1; then
    read -r -p "Nonaktifkan login SSH dengan password (wajib pakai SSH key)? [y/N] " disable_pw
    if [ "${disable_pw:-N}" = "y" ] || [ "${disable_pw:-N}" = "Y" ]; then
        cat > /etc/ssh/sshd_config.d/99-walhi-hardening.conf <<'SSHD'
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin prohibit-password
SSHD
        if sshd -t; then
            systemctl reload ssh 2>/dev/null || systemctl reload sshd
            echo "✅ Login SSH password dinonaktifkan."
        else
            rm -f /etc/ssh/sshd_config.d/99-walhi-hardening.conf
            echo "❌ Konfigurasi SSH tidak valid; perubahan dibatalkan."
        fi
    fi
else
    echo "⚠️  Belum ada SSH key terpasang; login password TIDAK dinonaktifkan. Pasang key lalu jalankan ulang skrip ini."
fi

echo ""
echo "⚠️  Catatan firewall: port yang dipublish Docker (80/443) melewati aturan UFW."
echo "   Untuk membatasi sumber (mis. hanya IP Cloudflare), gunakan chain DOCKER-USER"
echo "   atau firewall penyedia cloud. Lihat DEPLOYMENT.md."

echo ""
echo "==================================================================="
echo "  🎉 SETUP VPS SELESAI! Server Anda sudah 100% siap untuk deploy."
echo "==================================================================="
echo ""
echo "Langkah selanjutnya untuk menjalankan website WALHI:"
echo ""
echo "1. Clone repository ke /var/www/walhi_app (jika belum):"
echo "   cd /var/www"
echo "   git clone https://github.com/walhijabarhub/WalhiJBRWeb.git walhi_app"
echo "   cd /var/www/walhi_app"
echo ""
echo "2. Buat file .env produksi:"
echo "   cp .env.production.example .env"
echo "   nano .env"
echo "   (Setel APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD, REDIS_PASSWORD, ADMIN_PASSWORD, APP_URL, NGINX_SERVER_NAME)"
echo ""
echo "3. Deploy (build image, migrasi, health check):"
echo "   ./deploy.sh production"
echo ""
echo "4. Aktifkan HTTPS setelah DNS mengarah ke server:"
echo "   sudo ./setup-ssl.sh walhijabar.co.id"
echo ""
echo "5. Buat Akun Administrator (Pilih salah satu):"
echo "   a) Interaktif via terminal:"
echo "      docker compose -f docker-compose.prod.yml exec app php artisan walhi:create-admin"
echo "   b) Atau copy admins.local.json dari laptop ke folder database/seeders/, lalu jalankan:"
echo "      docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=AdminUserSeeder"
echo "==================================================================="
