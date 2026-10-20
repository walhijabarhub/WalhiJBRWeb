# ⚙️ Referensi Konfigurasi (`.env`)

Semua pengaturan aplikasi dan server dibaca dari file `.env`.

- **Lokal:** salin `.env.example`.
- **Produksi:** salin `.env.production.example` ke `/var/www/walhi_app/.env`. File ini dibaca Docker Compose (substitusi `${...}`) dan diteruskan ke container `app`, `worker`, dan `scheduler` (`env_file: .env`). Skrip `deploy.sh`, `docker-backup.sh`, dan `setup-ssl.sh` juga membacanya.

Setelah mengubah `.env` di server, jalankan `./deploy.sh production` (atau `docker compose -f docker-compose.prod.yml up -d`) agar container dibuat ulang. Konfigurasi di-cache saat container start, jadi `restart` saja tidak cukup untuk variabel yang dibaca Compose.

> ⚠️ Jangan pernah meng-commit `.env`, menempelkannya ke chat/issue, atau mengirimnya lewat email. Kolom "Rahasia" di bawah menandai nilai yang harus diperlakukan seperti password.

---

## Aplikasi

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `APP_NAME` | `WALHI Jawa Barat` | | Nama situs (judul, email). |
| `APP_ENV` | `production` | | `local` di lokal. Route mock pembayaran hanya ada di `local`/`testing`. |
| `APP_KEY` | — | ✅ | **Wajib.** Buat sekali (`php artisan key:generate --show`), lalu **jangan pernah diganti**: dipakai untuk mengenkripsi sesi dan secret 2FA. Container menolak start bila kosong. |
| `APP_DEBUG` | `false` | | Harus `false` di produksi. |
| `APP_URL` | `https://walhijabar.or.id` | | URL publik. Dipakai untuk tautan email, sitemap, signed URL. |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` / `APP_FAKER_LOCALE` | `id` / `en` / `id_ID` | | |
| `APP_MAINTENANCE_DRIVER` / `APP_MAINTENANCE_STORE` | `cache` / `redis` (produksi) | | Maintenance mode lewat Redis agar berlaku di semua container dan bertahan saat container dibuat ulang. Lokal: `file`. |
| `BCRYPT_ROUNDS` | `12` | | |

## Basis Data

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `DB_CONNECTION` | `mysql` (prod), `sqlite` (lokal) | | |
| `DB_HOST` / `DB_PORT` | `db` / `3306` | | Nama service di Compose. |
| `DB_DATABASE` / `DB_USERNAME` | `walhi_prod` / `walhi_user` | | |
| `DB_PASSWORD` | — | ✅ | Wajib, minimal 24 karakter acak (`openssl rand -base64 24`). |
| `DB_ROOT_PASSWORD` | — | ✅ | Wajib, root MySQL (container `db`). |

## Redis, Session, Cache & Queue

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `REDIS_PASSWORD` | — | ✅ | Wajib di produksi (`openssl rand -hex 24`). Redis tidak dipublish ke luar. |
| `SESSION_DRIVER` | `redis` | | Lokal: `database`/`file`. |
| `SESSION_LIFETIME` | `120` | | Menit. |
| `SESSION_ENCRYPT` | `true` | | |
| `SESSION_SECURE_COOKIE` | `true` | | `false` hanya saat uji tanpa HTTPS. |
| `CACHE_STORE` / `CACHE_PREFIX` | `redis` / `walhi_cache_` | | |
| `QUEUE_CONNECTION` | `redis` | | Diproses container `worker`. Lokal: `database` atau `sync`. |
| `FILESYSTEM_DISK` | `public` | | Dokumen tetap disimpan ke disk `local` (privat) oleh kode. |

## Logging

| Variabel | Default | Keterangan |
| :--- | :--- | :--- |
| `LOG_CHANNEL` | `daily` | |
| `LOG_LEVEL` | `warning` | Level log aplikasi. Channel `audit` selalu mencatat `info` apa pun nilainya. |
| `LOG_DAILY_DAYS` | `30` | Retensi `laravel-*.log`. |
| `LOG_AUDIT_DAYS` | `365` | Retensi `audit-*.log`. |

## Email (SMTP)

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `MAIL_MAILER` | `smtp` | | `log` = email hanya ditulis ke log (tidak terkirim). **Dengan `log`, reset password dan konfirmasi newsletter tidak sampai ke pengguna.** |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_ENCRYPTION` | `smtp.gmail.com` / `587` / `tls` | | |
| `MAIL_USERNAME` | — | | Akun pengirim. |
| `MAIL_PASSWORD` | — | ✅ | Untuk Google Workspace/Gmail: *App Password* 16 digit (butuh 2-Step Verification di akun Google). |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | — / `WALHI Jawa Barat` | | Sebaiknya sama dengan `MAIL_USERNAME` agar tidak ditolak/masuk spam. |

Cara mengaktifkan ada di [operations.md](operations.md#6-mengaktifkan-email-smtp).

## Autentikasi & Akun

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `ADMIN_LOGIN_PATH` | `portal-jabar` | | Path halaman login (tanpa `/`). `/login` selalu 404. |
| `AUTH_2FA_ENFORCED` | `true` | | Wajibkan 2FA TOTP untuk admin/editor. Jangan dimatikan di produksi. |
| `ADMIN_EMAIL` / `EDITOR_EMAIL` | — | | Akun yang dibuat `AdminUserSeeder`. |
| `ADMIN_PASSWORD` / `EDITOR_PASSWORD` | kosong | ✅ | Hanya dipakai saat akun **baru** dibuat oleh seeder. Lebih aman dibiarkan kosong: seeder membuat password acak + mengirim tautan reset. Hapus nilainya setelah seeding. |
| `FORCE_ADMIN_PASSWORD_RESET` | `false` | | `true` = seeder menimpa password akun yang sudah ada. Kembalikan ke `false` setelah dipakai. |

## Midtrans

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `MIDTRANS_SERVER_KEY` | — | ✅ | Server key (produksi: `Mid-server-…`, sandbox: `SB-Mid-server-…`). |
| `MIDTRANS_CLIENT_KEY` | — | | Client key untuk Snap.js. |
| `MIDTRANS_IS_PRODUCTION` | `false` | | `true` = endpoint produksi Midtrans. |
| `MIDTRANS_VERIFY_WEBHOOK_STATUS` | `true` | | Konfirmasi status webhook ke Status API. Biarkan `true`. |
| `MIDTRANS_WEBHOOK_IP_WHITELIST` | `false` | | Aktifkan allowlist IP sumber webhook. |
| `MIDTRANS_WEBHOOK_IPS` | daftar bawaan | | CIDR dipisah koma; hanya dipakai bila allowlist aktif. |
| `MIDTRANS_TIMEOUT` / `MIDTRANS_CONNECT_TIMEOUT` | `10` / `5` | | Detik. |
| `MIDTRANS_RECONCILE_AFTER_MINUTES` | `15` | | Donasi `pending` lebih tua dari ini dicek ulang. |
| `MIDTRANS_EXPIRE_AFTER_HOURS` | `24` | | Donasi `pending` lebih tua dari ini dikedaluwarsakan. |

URL notifikasi yang didaftarkan di dashboard Midtrans: `https://walhijabar.or.id/donasi/webhook`.

## CAPTCHA (Cloudflare Turnstile)

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `TURNSTILE_SITE_KEY` | kosong | | Kunci publik widget. |
| `TURNSTILE_SECRET_KEY` | kosong | ✅ | Kunci verifikasi server. |

Turnstile aktif hanya bila **keduanya** terisi. Bila kosong, form komentar & newsletter tetap memakai honeypot + rate limit.

## Privasi & Retensi Data

| Variabel | Default | Keterangan |
| :--- | :--- | :--- |
| `PRIVACY_RETENTION_MONTHS` | `24` | Umur data donatur & komentator sebelum dianonimkan. |
| `PRIVACY_UNCONFIRMED_SUBSCRIBER_DAYS` | `30` | Subscriber yang tidak mengonfirmasi dihapus setelah N hari. |
| `PRIVACY_DELETED_SUBSCRIBER_DAYS` | `30` | Subscriber yang dihapus admin dihapus permanen setelah N hari. |
| `PRIVACY_AUDIT_LOG_DAYS` | `730` | Baris `audit_logs` (berisi email, IP, user agent) dihapus setelah N hari oleh `model:prune` harian. Jangan lebih pendek dari `LOG_AUDIT_DAYS`. |

## Container & Nginx

| Variabel | Default | Keterangan |
| :--- | :--- | :--- |
| `APP_IMAGE` | `walhi_larafel-app` | Repo image. Di server live: `ghcr.io/walhijabarhub/walhijbrweb` (ditarik dari GHCR). Diperbarui otomatis oleh `deploy.sh`. |
| `IMAGE_TAG` | `latest` | Tag image (12 karakter commit). Diperbarui otomatis oleh `deploy.sh`; jangan diubah manual kecuali untuk rollback. |
| `SEED_ON_START` | `false` | **Harus `false` di produksi.** |
| `MIGRATE_ON_START` | `false` | Migrasi dijalankan `deploy.sh`, bukan saat container start. |
| `CLEAR_CACHE_ON_START` | `false` | |
| `HTTP_PORT` / `HTTPS_PORT` | `80` / `443` | Port yang dipublish container `web`. |
| `NGINX_CONF` | `http` | `http` sebelum sertifikat ada, `https` setelah `setup-ssl.sh`. |
| `NGINX_SERVER_NAME` | `walhijabar.or.id` | Domain sertifikat (tanpa `https://`). |
| `NGINX_REALIP` | `none` | `cloudflare` bila situs di-proxy Cloudflare. |
| `TRUSTED_PROXIES` | kosong | Isi hanya bila ada proxy/load balancer lain **di depan** nginx. |
| `DEPLOY_MAINTENANCE_KEY` | acak per deploy | Opsional: secret bypass maintenance mode saat deploy. |

## Backup

| Variabel | Default | Rahasia | Keterangan |
| :--- | :--- | :---: | :--- |
| `BACKUP_DIR` | `/var/backups/walhi` | | Lokasi backup lokal. |
| `BACKUP_RETENTION_DAYS` | `14` | | Backup rutin; snapshot pra-deploy disimpan 30 hari. |
| `OFFSITE_BACKUP_TARGET` | kosong | | Remote `rclone` (mis. `r2:walhi-backups`) atau `s3://bucket/path`. **Sangat disarankan**: tanpa ini, backup hanya ada di VPS yang sama. |
| `BACKUP_ALERT_WEBHOOK` | kosong | ✅ | URL (Slack/Discord) yang menerima pesan saat backup gagal. |

---

## Secrets GitHub Actions

Disimpan di *Settings → Secrets and variables → Actions*, bukan di `.env`:

| Secret | Isi |
| :--- | :--- |
| `VPS_HOST` | IP/domain VPS |
| `VPS_USERNAME` | User SSH yang punya akses docker |
| `VPS_SSH_KEY` | Private key deploy |
| `VPS_SSH_PORT` | Port SSH (22) |
| `VPS_SSH_FINGERPRINT` | Fingerprint host key **ECDSA** (`SHA256:...`) |

Login ke GHCR memakai `GITHUB_TOKEN` otomatis; tidak perlu secret tambahan.
