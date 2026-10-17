# 🚀 Panduan Deployment & Operasional Produksi (Production Deployment Guide)

Dokumen ini berisi panduan langkah-demi-langkah untuk mendeploy dan mengoperasikan aplikasi web **WALHI Jawa Barat** di server produksi (VPS Ubuntu / Debian / Cloud Server).

---

## 📋 Daftar Isi
1. [Spesifikasi Server Minimum](#-spesifikasi-server-rekomendasi)
2. [Langkah Deployment Produksi (Docker Compose)](#-langkah-deployment-cepat-turnkey-vps-setup)
3. [Konfigurasi Domain & SSL (HTTPS)](#-konfigurasi-domain--ssl-https)
4. [Backup & Restore Database Otomatis](#-backup--restore-database-otomatis)
5. [Monitoring & Manajemen Log](#-monitoring-alokasi-ram--benchmark)
6. [Otomasi Deployment (CI/CD) GitHub Actions (Branch: production)](#-otomasi-deployment-cicd-github-actions-branch-production)
7. [Integritas & Keamanan Database VPS (Anti-Reset & Anti-Data Loss)](#-integritas--keamanan-database-vps-anti-reset)
8. [Panduan Load Testing](#-panduan-load-testing-staging--production)

---

## 💻 Spesifikasi Server Rekomendasi

- **OS:** Ubuntu 22.04 LTS / 24.04 LTS atau Debian 12
- **CPU:** 2 vCPU core
- **RAM:** 8 GB (Telah di-tuning untuk alokasi MySQL 2.5 GB + PHP-FPM 3.0 GB + OS margin 2.5 GB)
- **Disk:** 100 GB NVMe / SSD
- **Bandwidth:** 8 TB / bulan
- **Software Terpasang:** Docker Engine & Docker Compose v2 (`docker-compose-plugin`)

---

## 🐳 Langkah Deployment Cepat (Turnkey VPS Setup)

### 1. Masuk ke Server VPS Baru Anda
```bash
ssh root@IP_SERVER_VPS_ANDA
```

### 2. Jalankan Skrip Auto-Setup
Skrip ini akan secara otomatis memperbarui sistem, memasang Swap 2 GB (mencegah crash OOM pada 1 vCPU), mengonfigurasi firewall UFW, menginstal Docker Engine resmi, dan menyiapkan cron backup harian:

```bash
curl -fsSL https://raw.githubusercontent.com/Irs622/walhi_larafel/main/setup-vps.sh -o setup-vps.sh
chmod +x setup-vps.sh
sudo ./setup-vps.sh
```

### 3. Clone Repository ke Direktori Produksi (Branch: production)
```bash
cd /var/www
git clone -b production https://github.com/Irs622/walhi_larafel.git walhi_app
cd /var/www/walhi_app
```

### 4. Siapkan File `.env` Produksi
Salin template produksi dan sesuaikan nilai-nilainya:
```bash
cp .env.production.example .env
nano .env
```

**Konfigurasi Kunci yang Wajib Diisi:**
- `APP_URL=https://walhijabar.co.id`
- `DB_PASSWORD` & `DB_ROOT_PASSWORD` (Gunakan password acak kuat minimal 24 karakter — **DILARANG** menggunakan string default contoh)
- `MIDTRANS_SERVER_KEY` & `MIDTRANS_CLIENT_KEY` (Kunci produksi akun Midtrans resmi)
- `ADMIN_PASSWORD` (Password akun Super Admin)
- `APP_KEY` (wajib; container menolak start tanpa ini). Buat sekali dengan `docker run --rm walhi_larafel-app php artisan key:generate --show` atau `php artisan key:generate --show` di mesin lokal, lalu **jangan pernah diubah** (mengubahnya membuat sesi & secret 2FA tidak bisa dibaca).
- `REDIS_PASSWORD` (`openssl rand -hex 24`)
- `NGINX_SERVER_NAME` (domain tanpa `https://`), `NGINX_CONF=http` untuk start pertama

> [!IMPORTANT]
> **Checklist Keamanan Kredensial Database:**
> 1. Pastikan password database `DB_PASSWORD` dan `DB_ROOT_PASSWORD` bukan string contoh historis (`walhi_secret_pass` / `walhi_root_secret`).
> 2. Generate password baru dengan entropi tinggi: `openssl rand -base64 24`.
> 3. Jika melakukan rotasi password pada database MySQL yang sedang berjalan:
>    ```bash
>    docker exec -it walhi_prod_db mysql -u root -p -e "ALTER USER 'walhi_user'@'%' IDENTIFIED BY 'PASSWORD_BARU_ANDA'; FLUSH PRIVILEGES;"
>    ```
>    Lalu perbarui variabel `DB_PASSWORD` di `.env` dan restart container aplikasi (`docker compose -f docker-compose.prod.yml restart app`).

### 5. Jalankan Stack Produksi
```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Setelah perintah selesai, container berikut berjalan (semua dengan healthcheck — cek dengan `docker compose -f docker-compose.prod.yml ps`):
- `walhi_prod_app` — Laravel 13 / PHP-FPM 8.4
- `walhi_prod_worker` — `queue:work` (email konfirmasi newsletter, notifikasi)
- `walhi_prod_scheduler` — `schedule:work` (rekonsiliasi donasi, flush views, retensi data)
- `walhi_prod_db` — MySQL 8.0
- `walhi_prod_redis` — Redis 7 (session, cache, rate limit, queue; tidak dipublish ke luar)
- `walhi_prod_nginx` — Nginx (server terdepan & terminasi TLS)

### ⬆️ Upgrade Server yang Sudah Berjalan (wajib sebelum deploy versi ini)
Versi ini menambah Redis, worker, scheduler dan template nginx. Lakukan **sebelum** `git pull` / deploy berikutnya, atau deploy akan gagal / HTTPS mati:
1. Tambahkan ke `.env` server:
   ```bash
   REDIS_PASSWORD=$(openssl rand -hex 24)   # isi nilai hasil perintah ini
   NGINX_SERVER_NAME=walhijabar.co.id        # domain sertifikat yang sudah ada
   NGINX_CONF=https                          # karena sertifikat sudah terpasang
   SESSION_DRIVER=redis
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   ```
   Pastikan `APP_KEY` sudah terisi (wajib).
2. Salin dokumen privat dari container lama (lihat peringatan di bawah).
3. Deploy, lalu jalankan `sudo ./setup-ssl.sh walhijabar.co.id` sekali untuk memindahkan perpanjangan sertifikat ke metode webroot.
4. Periksa `docker compose -f docker-compose.prod.yml ps` — semua container harus `healthy`/`running`.

### Penyimpanan Persisten
| Volume | Isi |
| :--- | :--- |
| `walhi_prod_app_storage` | `storage/app` — termasuk **dokumen privat** `storage/app/private/documents` |
| `walhi_prod_storage` | `storage/app/public` — unggahan gambar publik (volume lama, tetap dipakai) |
| `walhi_prod_db` | Data MySQL |
| `walhi_prod_redis` | Data Redis (AOF) |
| `walhi_prod_logs` | `storage/logs` (termasuk `audit.log` & `php-fpm-slow.log`) |

> [!WARNING]
> **Upgrade dari versi sebelum volume `walhi_prod_app_storage`:** dulu `storage/app/private` tidak persisten, sehingga dokumen yang diunggah sejak deploy terakhir hanya ada di container yang sedang berjalan. **Sebelum** deploy versi ini, salin dokumen tersebut:
> ```bash
> docker cp walhi_prod_app:/var/www/html/storage/app/private ./backup-private-$(date +%F)
> # setelah deploy:
> docker compose -f docker-compose.prod.yml cp ./backup-private-YYYY-MM-DD/. app:/var/www/html/storage/app/private/
> docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage/app/private
> ```

## 🔒 Konfigurasi Domain & SSL (HTTPS)

### Otomatis Menggunakan Skrip `setup-ssl.sh`:
```bash
cd /var/www/walhi_app
chmod +x setup-ssl.sh
./setup-ssl.sh
```
Skrip ini otomatis:
1. Menginstal certbot.
2. Menerbitkan sertifikat Let's Encrypt untuk `<domain>` & `www.<domain>` dengan **metode webroot** — nginx tetap menyala dan melayani `/.well-known/acme-challenge/` dari `docker/certbot/www`.
3. Memasang hook `/etc/letsencrypt/renewal-hooks/deploy/reload-walhi-nginx.sh` sehingga nginx di-reload setiap kali sertifikat diperpanjang (oleh timer systemd certbot).
4. Mengubah `.env`: `NGINX_CONF=https`, `NGINX_SERVER_NAME`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, lalu menjalankan ulang stack.
5. Menguji perpanjangan dengan `certbot renew --dry-run`.

Konfigurasi nginx kini **versioned di repo** (`docker/nginx/http`, `docker/nginx/https`, `docker/nginx/snippets`) dan dipilih lewat `NGINX_CONF` — tidak ada lagi file yang ditulis ulang di server.

Sertifikat lama yang diterbitkan dengan metode *standalone* otomatis diterbitkan ulang sekali dengan webroot saat skrip dijalankan, sehingga perpanjangan berikutnya tidak lagi membutuhkan port 80 kosong.

### Cloudflare (opsional, disarankan untuk CDN)
1. Aktifkan proxy (awan oranye) untuk domain, mode SSL **Full (strict)** — sertifikat Let's Encrypt di origin tetap diperlukan.
2. Setel `NGINX_REALIP=cloudflare` di `.env` lalu `docker compose -f docker-compose.prod.yml up -d web`. Nginx akan memulihkan IP pengunjung asli dari `CF-Connecting-IP` **hanya** untuk koneksi dari rentang IP Cloudflare (`docker/nginx/realip/cloudflare/cloudflare.conf`).
3. Batasi port 80/443 di firewall VPS hanya untuk rentang IP Cloudflare agar origin tidak bisa diakses langsung. Ingat: port yang dipublish Docker **melewati aturan UFW**; gunakan chain `DOCKER-USER` atau firewall penyedia cloud.
4. Aturan cache Cloudflare untuk `/build/*`, `/assets/*`, `/storage/*` mengurangi beban VPS secara signifikan.

### Alamat IP Klien
Nginx adalah server terdepan; IP klien diteruskan ke PHP sebagai `REMOTE_ADDR`, dan header `X-Forwarded-*` dari klien dibuang. `TRUSTED_PROXIES` dibiarkan kosong kecuali ada reverse proxy/load balancer lain di depan nginx. Bila Docker daemon memakai *userland-proxy* (mis. klien IPv6 tanpa `ipv6: true` di daemon), IP yang terlihat adalah gateway Docker — setel `"userland-proxy": false` di `/etc/docker/daemon.json`.


---

## 💾 Backup & Restore Database Otomatis

### Menjalankan Backup Manual:
```bash
./docker-backup.sh
```
File backup terkompresi (`.sql.gz`) akan tersimpan di folder `storage/backups/`.

### Mengatur Backup Otomatis Harian (Cron Job):
Buka crontab server:
```bash
crontab -e
```
Tambahkan baris berikut untuk backup otomatis setiap jam 02.00 dini hari:
```cron
0 2 * * * cd /var/www/walhi_app && ./docker-backup.sh >> /var/log/walhi_backup.log 2>&1
```

### Melakukan Restore Database dari File Backup:
```bash
gunzip < storage/backups/walhi_prod_mysql_YYYYMMDD_HHMMSS.sql.gz | docker exec -i walhi_prod_db mysql -u <DB_USERNAME> -p<DB_PASSWORD> <DB_DATABASE>
```

---

## 📊 Monitoring, Alokasi RAM & Benchmark

### Anggaran Memori pada VPS 8 GB RAM:
Container produksi dibatasi secara optimal (*resource limits*) untuk memaksimalkan throughput sekaligus mencegah crash akibat *Out of Memory (OOM)*:
- **`walhi_prod_app` (PHP-FPM 18 workers)**: Limit **2.048 MB** (Reservasi: 512 MB)
- **`walhi_prod_worker` (queue worker)**: Limit **384 MB**
- **`walhi_prod_scheduler` (schedule:work)**: Limit **256 MB**
- **`walhi_prod_db` (MySQL 8.0, buffer pool 1.5 GB)**: Limit **2.560 MB** (Reservasi: 1.024 MB)
- **`walhi_prod_redis`**: Limit **256 MB** (`maxmemory` 200 MB)
- **`walhi_prod_nginx`**: Limit **256 MB**
- **Headroom OS, Page Cache & Linux Buffers**: **~2.2 GB** + Swap

### Healthcheck & Pemantauan
- `docker compose -f docker-compose.prod.yml ps` menampilkan status `healthy` tiap container (PHP-FPM via `/ping`, nginx via `/nginx-health`, MySQL, Redis).
- Endpoint aplikasi `/up` cocok untuk uptime monitor eksternal (UptimeRobot, Better Stack, dll.). Pasang alert untuk `/up`, tanggal kedaluwarsa sertifikat, dan ruang disk.
- Request PHP yang lebih lambat dari 5 detik tercatat di `storage/logs/php-fpm-slow.log`.

### Memeriksa Penggunaan CPU & RAM Realtime:
```bash
docker stats
```
> **Target Normal:** Total konsumsi RAM server berada di kisaran 50% – 70%. Jika beban puncak (*peak*) mencapai 75% – 85%, server masih memiliki toleransi aman tanpa risiko *kernel panic*.

### Melihat Log Realtime:
```bash
# Log seluruh stack produksi
docker compose -f docker-compose.prod.yml logs -f

# Log Laravel application saja
docker compose -f docker-compose.prod.yml logs -f app
```

---

## 🚀 Otomasi Deployment (CI/CD) GitHub Actions (Branch: production)

Aplikasi web WALHI Jawa Barat telah dikonfigurasi dengan pipeline CI/CD otomatis menggunakan **GitHub Actions** dan **SSH Deploy Key**.

Ketika Anda melakukan `git push origin production`:
1. **Gatekeeper (Testing & Security):** GitHub Actions secara otomatis menjalankan seluruh 133 tes otomatis (`php artisan test`), kompilasi aset frontend (`npm run build`), dan audit keamanan dependensi (`composer audit`).
2. **Fail-Closed Protection:** Jika ada satupun tes yang gagal atau ada error sintaks, proses deploy **langsung dibatalkan**. Server live VPS Anda **tidak akan disentuh sama sekali oleh kode rusak**.
3. **Automated SSH Execution:** Jika seluruh pengujian lulus 100%, GitHub Actions menghubungkan ke VPS melalui SSH dan mengeksekusi skrip rilis aman `./deploy.sh production`.

```mermaid
flowchart LR
    A["Developer<br>(git push origin production)"] --> B["GitHub Actions CI<br>(133 Tests + Security Audit)"]
    B -->|"Lulus 100%"| C["SSH ke VPS<br>(ed25519 Key)"]
    B -->|"Gagal"| D["❌ Batal Otomatis<br>(VPS Aman)"]
    C --> E["deploy.sh<br>(Auto-Backup + Migrate + Zero-Downtime)"]
    E --> F["✅ Live Website Updated"]
```

---

### 🔑 Langkah Setup Kunci SSH & GitHub Repository Secrets (Sekali Saja)

Agar GitHub Actions dapat terhubung ke VPS secara aman tanpa password:

#### 1. Buat Kunci SSH Khusus Deployment di VPS Anda:
Masuk ke terminal server VPS dan jalankan:
```bash
# Generate key pair tipe ed25519 khusus deploy
ssh-keygen -t ed25519 -C "github-actions-walhi-deploy" -f ~/.ssh/id_ed25519_deploy -N ""

# Tambahkan public key ke daftar authorized_keys VPS:
cat ~/.ssh/id_ed25519_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Tampilkan Private Key untuk disalin ke GitHub:
cat ~/.ssh/id_ed25519_deploy
```
*(Salin seluruh teks private key yang dimulai dari `-----BEGIN OPENSSH PRIVATE KEY-----` sampai `-----END OPENSSH PRIVATE KEY-----`).*

#### 2. Daftarkan 4 Secret di GitHub Repository:
Buka repositori GitHub Anda: **Settings** ➔ **Secrets and variables** ➔ **Actions** ➔ **New repository secret**:

| Nama Secret | Nilai / Deskripsi |
| :--- | :--- |
| `VPS_HOST` | IP Publik VPS Anda (contoh: `103.175.xxx.xxx` atau domain server) |
| `VPS_USERNAME` | Username SSH VPS (contoh: `root` atau user dengan akses docker) |
| `VPS_SSH_KEY` | Seluruh isi private key `~/.ssh/id_ed25519_deploy` yang tadi disalin |
| `VPS_SSH_PORT` | Port SSH server Anda (default: `22`) |

Setelah 4 secret di atas disimpan, setiap kali Anda melakukan `git push origin production`, server VPS akan otomatis menarik dan menerapkan perubahan secara instan!

---

### 🛠️ Prosedur Manual Pembaruan Aplikasi (Jika Ingin Deploy dari Terminal VPS)

Jika Anda sedang berada di dalam server VPS dan ingin melakukan pembaruan secara manual:

```bash
# Cukup jalankan satu perintah ini:
cd /var/www/walhi_app
./deploy.sh production
```

Skrip `./deploy.sh` secara otomatis menjalankan:
1. Validasi pre-flight (ruang disk dan status docker).
2. **Snapshot backup otomatis database** ke `storage/backups/walhi_predeploy_*.sql.gz`.
3. Maintenance mode anggun (`php artisan down --secret=...`).
4. `git pull origin production` secara deterministik.
5. Rebuild container Docker (`docker compose -f docker-compose.prod.yml up -d --build`).
6. Migrasi database non-destruktif (`php artisan migrate --force`).
7. Optimasi cache produksi (`config:cache`, `route:cache`, `view:cache`).
8. Menonaktifkan maintenance mode (`php artisan up`).
9. Health check HTTP status ke server lokal.

---

## 🗄️ Integritas & Keamanan Database VPS (Anti-Reset & Anti-Data Loss)

### Mengapa Database di VPS Sempat Berubah/Ter-reset di Masa Lalu?
Berdasarkan investigasi menyeluruh, data database di VPS sempat ter-reset karena:
1. **Flag `SEED_ON_START=true`**: Menjalankan `docker compose up` tanpa `-f docker-compose.prod.yml` memicu pembacaan `docker-compose.yml` bawaan lokal yang memuat `SEED_ON_START=true`, sehingga seeder menimpa artikel nyata dengan dummy data seeder.
2. **Perintah `migrate:fresh`**: Menghapus seluruh tabel dan membuat ulang dari kosong.
3. **Volume Loss (`docker compose down -v`)**: Flag `-v` menghapus persistent volume database MySQL/SQLite.

### 🛡️ 6 Aturan Emas Perlindungan Database yang Kini Terpasang Permanen:
1. **Hanya Migrasi Non-Destruktif:** Skrip deployment **hanya** menjalankan `php artisan migrate --force`. Dilarang keras menggunakan `migrate:fresh`, `migrate:refresh`, atau `db:seed`.
2. **Kunci `SEED_ON_START=false`:** Di seluruh konfigurasi (`docker-compose.prod.yml`, `docker-compose.yml`, dan `.env.production`), seeder otomatis telah dinonaktifkan permanen.
3. **Otomatis Pre-Deploy Backup:** Setiap deployment otomatis membuat file backup terkompresi `.sql.gz` di folder `storage/backups/`.
4. **Isolasi Volume Docker:** Volume MySQL `walhi_prod_db` terpisah dari image kode. Memperbarui kode aplikasi tidak pernah menyentuh volume data MySQL.
5. **Git Ignore Database:** Seluruh format file SQLite (`*.sqlite`, `*.sqlite-shm`, `*.sqlite-wal`) diabaikan oleh git agar tidak tertimpa saat pull.
6. **Maintenance Mode Graceful:** Pengunjung dicegah menulis data saat proses migrasi beberapa detik berlangsung.

### 🔄 Prosedur Rollback Darurat Database (Disaster Recovery):
Jika terjadi kesalahan skema dan Anda ingin mengembalikan database ke titik tepat sebelum deploy terakhir:

```bash
# 1. Cari file backup pra-deploy terbaru di folder backups:
ls -la /var/www/walhi_app/storage/backups/walhi_predeploy_*

# 2. Restore database MySQL dari file backup:
gunzip < /var/www/walhi_app/storage/backups/NAMA_FILE_BACKUP.sql.gz | docker exec -i walhi_prod_db mysql -u walhi_user -p$DB_PASSWORD walhi_prod

# 3. Bersihkan cache:
docker compose -f docker-compose.prod.yml exec app php artisan optimize:clear
```

---

## 🧪 Panduan Load Testing Staging / Production

Sebelum promosi traffic masif, lakukan pengujian konkurensi bertahap dari workstation pengembang atau server staging:

```bash
# Menggunakan ApacheBench (ab) atau k6 / wrk
# Uji coba 100 request dengan 10 concurrent users:
ab -n 100 -c 10 https://walhijabar.co.id/

# Uji coba 500 request dengan 25 concurrent users:
ab -n 500 -c 25 https://walhijabar.co.id/

# Uji coba 1000 request dengan 50 concurrent users:
ab -n 1000 -c 50 https://walhijabar.co.id/
```
Amati keluaran `docker stats`. Jika penggunaan RAM per PHP-FPM worker stabil dan antrean request rendah, nilai `pm.max_children` di `docker/php/zz-docker.conf` dapat dinaikkan bertahap (8 ➔ 10 ➔ 12) berdasarkan data aktual.
