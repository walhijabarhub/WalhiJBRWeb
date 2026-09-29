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

Setelah perintah selesai, 3 container akan otomatis berjalan:
- `walhi_prod_app` (Aplikasi Laravel 12 / PHP 8.4)
- `walhi_prod_db` (Basis data MySQL 8.0)
- `walhi_prod_nginx` (Web Server Nginx & Reverse Proxy)

## 🔒 Konfigurasi Domain & SSL (HTTPS)

### Otomatis Menggunakan Skrip `setup-ssl.sh`:
```bash
cd /var/www/walhi_app
chmod +x setup-ssl.sh
./setup-ssl.sh
```
Skrip ini otomatis:
1. Menginstal certbot.
2. Menerbitkan sertifikat SSL resmi Let's Encrypt untuk `walhijabar.co.id` & `www.walhijabar.co.id`.
3. Mengonfigurasi volume SSL di `docker-compose.prod.yml`.
4. Mengonfigurasi Nginx untuk HTTPS & auto-redirect HTTP ke HTTPS.
5. Memperbarui `APP_URL=https://walhijabar.co.id` dan `SESSION_SECURE_COOKIE=true` di `.env`.
6. Menyetel auto-renewal sertifikat secara berkala via crontab.


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
- **`walhi_prod_app` (PHP-FPM 18 workers + Laravel 12)**: Limit **3.072 MB (3 GB)** (Reservasi: 768 MB)
- **`walhi_prod_db` (MySQL 8.0 tuned 1.5 GB buffer pool)**: Limit **2.560 MB (2.5 GB)** (Reservasi: 1.024 MB)
- **`walhi_prod_nginx` (Nginx Alpine Reverse Proxy)**: Limit **512 MB** (Reservasi: 128 MB)
- **Headroom OS, Page Cache & Linux Buffers**: **~2.048 MB (2 GB)** + Swap 4 GB NVMe

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
