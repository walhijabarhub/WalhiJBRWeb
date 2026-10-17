# 🏗️ Arsitektur Sistem WALHI Jawa Barat

Dokumen ini menjelaskan struktur arsitektur teknis, pola desain, komponen produksi, dan modul-modul utama dalam aplikasi web **WALHI Jawa Barat**.

---

## 📐 Gambaran Umum (Overview)

Aplikasi dibangun sebagai **monolit Laravel 13** (PHP 8.4) dengan render server-side (Blade), interaktivitas ringan (Alpine.js build CSP & Tailwind CSS), MySQL 8 di produksi (SQLite untuk lokal/test), dan Redis untuk session, cache, rate limit, serta antrean.

### Topologi Produksi (`docker-compose.prod.yml`)

```text
                    Pengunjung
                        │ HTTPS (opsional lewat Cloudflare proxy)
                        ▼
┌──────────────────────────────────────────────────────────────────┐
│ web  — nginx:alpine (walhi_prod_nginx)                           │
│  - Terminasi TLS (Let's Encrypt, metode webroot)                 │
│  - Template dipilih lewat NGINX_CONF=http|https                  │
│  - Real IP Cloudflare opsional (NGINX_REALIP=cloudflare)         │
│  - Menimpa X-Forwarded-* dari klien; blok eksekusi di /storage   │
│  - Upstream app:9000 di-resolve dinamis (resolver 127.0.0.11)    │
└───────────────┬──────────────────────────────────────────────────┘
                │ FastCGI
                ▼
┌──────────────────────────────┐   ┌──────────────────────────────┐
│ app — PHP-FPM 8.4            │   │ worker — queue:work          │
│  Laravel 13 (image GHCR,     │   │ (email konfirmasi newsletter,│
│  tag = 12 karakter commit)   │   │  reset password, notifikasi) │
└───────┬───────────┬──────────┘   └──────────────────────────────┘
        │           │              ┌──────────────────────────────┐
        │           │              │ scheduler — schedule:work    │
        │           │              │ (reconcile donasi, flush     │
        │           │              │  views, prune data pribadi)  │
        ▼           ▼              └──────────────────────────────┘
┌──────────────┐ ┌──────────────────────────────────────────────┐
│ db — MySQL 8 │ │ redis — Redis 7 (password, tidak dipublish)  │
│ (volume      │ │ session · cache · rate limit · queue ·       │
│  walhi_prod_db)│ │ maintenance mode                           │
└──────────────┘ └──────────────────────────────────────────────┘

Volume persisten: walhi_prod_app_storage (storage/app, termasuk dokumen privat),
walhi_prod_storage (storage/app/public), walhi_prod_db, walhi_prod_redis, walhi_prod_logs.
```

`app`, `worker`, dan `scheduler` memakai image yang sama. Semua container punya healthcheck.

### Alur Request di dalam Laravel

```text
HTTP Request
    ↓
bootstrap/app.php — TrustProxies (TRUSTED_PROXIES, default kosong), CSRF (kecuali donasi/webhook)
    ↓
Global middleware: SecurityHeaders (CSP + nonce per request, HSTS, nosniff, X-Frame-Options)
    ↓
Routing (routes/web.php, routes/auth.php)
    ├─ {category} dibatasi regex dari enum ContentCategory (kategori lain → 404)
    └─ throttle:*, auth, verified, two-factor, role:admin,editor
    ↓
FormRequest (validasi, SafeUrl, Turnstile bila aktif)
    ↓
Controller → Policy ($this->authorize(...))
    ↓
Services (DonationService, MidtransService, HtmlSanitizer, ContentCache, ViewCounter, AuditLogService)
    ↓
Eloquent + DB::transaction (lockForUpdate untuk donasi)
    ↓
Blade view / unduhan dokumen ter-stream / redirect
```

---

## 📁 Struktur Direktori Utama

```text
WalhiJBRWeb/
├── app/
│   ├── Console/Commands/        # walhi:create-admin, walhi:reset-2fa, walhi:donations-reconcile,
│   │                            # walhi:flush-views, walhi:prune-personal-data, walhi:migrate-documents,
│   │                            # walhi:import-clean-data, import:wp-csv, walhi:fix-typos
│   ├── Enums/                   # ContentCategory (22), ContentStatus, DonationStatus, UserRole
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # ContentController, AdminCommentController, AdminSubscriberController, AdminController
│   │   │   ├── Auth/            # Login, reset password, TwoFactorController, TwoFactorChallengeController
│   │   │   ├── PublicContentController.php    # Beranda, blog, detail konten
│   │   │   ├── PageController.php             # Halaman publik lain, sitemap, robots
│   │   │   ├── DocumentDownloadController.php # Unduhan dokumen privat terotorisasi
│   │   │   ├── DonationController.php         # Checkout & webhook Midtrans
│   │   │   ├── CommentController.php          # Komentar publik
│   │   │   └── SubscriptionController.php     # Newsletter: daftar, konfirmasi, berhenti
│   │   ├── Middleware/          # SecurityHeaders, CheckRole (role:), EnsureTwoFactorEnabled (two-factor)
│   │   └── Requests/            # FormRequest per domain (Admin, Auth, Comment, Donation, Newsletter)
│   ├── Models/                  # Content, Comment, Donation, Subscriber, User, AuditLog
│   ├── Notifications/           # ConfirmNewsletterSubscription (queued)
│   ├── Observers/               # ContentObserver (invalidasi cache)
│   ├── Policies/                # ContentPolicy, CommentPolicy, DonationPolicy, SubscriberPolicy
│   ├── Rules/                   # SafeUrl, Turnstile
│   └── Services/
│       ├── Auth/TwoFactorService.php
│       ├── Content/             # HtmlSanitizer, ContentCache, ViewCounter, SlugService
│       ├── Donation/            # DonationService, MidtransService
│       ├── AuditLogService.php
│       └── CsvSanitizer.php
├── bootstrap/app.php            # Routing, middleware alias, trust proxies, CSRF, exception rendering
├── config/                      # + midtrans.php, privacy.php, services.php (turnstile)
├── database/
│   ├── migrations/
│   └── seeders/                 # AdminUserSeeder (aman diulang), ContentSeeder
├── docker/                      # nginx (http/https/snippets/realip), php (fpm pool), mysql
├── docs/                        # Dokumentasi teknis & operasional
├── resources/
│   ├── css/app.css
│   ├── js/app.js                # Alpine.js build CSP + komponen
│   ├── js/csp-actions.js        # Pengganti handler inline (data-on-click, data-confirm, …)
│   └── views/
├── routes/
│   ├── web.php                  # Publik, unduhan dokumen, donasi, newsletter, admin
│   ├── auth.php                 # Login (/portal-jabar), reset password, 2FA
│   └── console.php              # Jadwal tugas
├── deploy.sh · docker-backup.sh · docker-restore.sh · setup-vps.sh · setup-ssl.sh
└── .github/workflows/           # ci.yml, security.yml, deploy.yml, sync-main.yml
```

---

## 🗄️ Entitas Basis Data

Detail kolom ada di [docs/database.md](docs/database.md).

1. **`users` (`User`)**: akun dengan role `admin`, `editor`, `subscriber`, plus kolom 2FA (`two_factor_secret` terenkripsi, `two_factor_recovery_codes`, `two_factor_confirmed_at`).
2. **`contents` (`Content`)**: satu tabel untuk seluruh publikasi dan konfigurasi halaman, dibedakan oleh `category` (enum `ContentCategory`, 22 nilai) dan `status` (`draft`, `published`, `archived`). Indeks FULLTEXT `(title, body)` di MySQL.
3. **`comments` (`Comment`)**: komentar 1 level balasan dengan status `pending`, `approved`, `spam`.
4. **`donations` (`Donation`)**: transaksi Midtrans dengan state machine `pending → success | failed | expired`.
5. **`subscribers` (`Subscriber`)**: newsletter *double opt-in* (`confirmed_at`, `token` unik untuk berhenti berlangganan).
6. **`audit_logs` (`AuditLog`)**: jejak audit aksi administratif (juga ditulis ke channel log `audit`).

Pekan Rakyat, kontak organisasi, banner beranda, statistik, dan isu kritis juga disimpan di `contents` dengan kategori masing-masing.

---

## ⚙️ Proses Latar Belakang

| Proses | Dijalankan oleh | Jadwal |
| :--- | :--- | :--- |
| `walhi:donations-reconcile` — cek donasi `pending` > 15 menit ke Status API Midtrans, kedaluwarsakan setelah 24 jam | `scheduler` | Tiap 15 menit |
| `walhi:flush-views` — tulis hitungan view dari Redis ke kolom `contents.views` | `scheduler` | Tiap 5 menit |
| `walhi:prune-personal-data` — anonimkan donatur/komentator lama, hapus subscriber tak terkonfirmasi | `scheduler` | Tanggal 1 tiap bulan, 03:00 |
| Email konfirmasi newsletter & notifikasi lain | `worker` (`queue:work`, Redis) | Saat ada job |
| Backup DB + `storage/app` (`docker-backup.sh`) | cron host | Harian 02:00 |
| Perpanjangan sertifikat + reload nginx | timer certbot host | Otomatis |

---

## 🚚 Pipeline Rilis

```text
PR → production ──► CI (test SQLite + MySQL, build aset, build image) & Security (gitleaks, audit)
        │
        └─ merge ──► deploy.yml: test → build & push ghcr.io/walhijabarhub/walhijbrweb:<sha12>
                                 → SSH ke VPS (fingerprint ECDSA) → ./deploy.sh production <sha>
                                    backup → migrate (image baru) → swap → health check → rollback bila gagal
               └──► sync-main.yml: fast-forward main = production
```

Rincian ada di [DEPLOYMENT.md](DEPLOYMENT.md) dan [docs/operations.md](docs/operations.md).

---

## 🎨 Sistem Desain (Design System)

Platform ini mengadopsi bahasa visual **Neo-Brutalism Hijau Ekologis**:
- **Borders & Shadows**: Border hitam tebal `border-2 border-[#1D1D1D]` atau `border-4 border-[#1D1D1D]` dipadukan dengan drop shadow tajam `shadow-[4px_4px_0px_0px_#1D1D1D]`.
- **Warna Utama**:
  - `Forest Green (#256D4A)`: Warna identitas lingkungan & tombol aksi utama.
  - `Sage Green (#5C8D59)`: Warna aksen & hover state.
  - `Warm Cream (#F4F1EA)`: Warna latar belakang utama ramah di mata.
  - `Terracotta Red (#D95C3F)`: Warna aksen kampanye & donasi.
  - `Charcoal Dark (#1D1D1D)`: Warna teks, border, dan elemen struktural utama.
- **Tipografi**:
  - Headline/Display: Font `Aspekta` (uppercase, bold, letter-spacing tegas).
  - Body/Narrative: Font `Montserrat` (clean, modern, highly readable).

Detail di [docs/frontend.md](docs/frontend.md).

---

## 🔒 Keamanan & Kebijakan Akses

- **CSRF**: aktif pada seluruh form mutasi. Hanya `donasi/webhook` yang dikecualikan (dilindungi signature SHA-512 + konfirmasi Status API).
- **Autentikasi**: login hanya di path rahasia (`ADMIN_LOGIN_PATH`, default `/portal-jabar`); `/login` dan registrasi publik → 404. Login dengan email eksak (atau username pada domain resmi), throttle per akun + IP. **2FA TOTP wajib** untuk admin/editor (`AUTH_2FA_ENFORCED`).
- **RBAC**: middleware `role:` + Policy. Kategori sensitif (`donasi`, `kontak`, `kampanye-darurat`) dan aksi hapus hanya untuk Admin.
- **Isolasi dokumen**: dokumen di disk `local` (`storage/app/private/documents/`), diunduh hanya lewat `DocumentDownloadController` dengan `nosniff` dan `Content-Disposition: attachment`.
- **Validasi URL**: `App\Rules\SafeUrl` hanya menerima `http(s)://`, path relatif `/…`, dan `#…`.
- **XSS**: body disanitasi HTMLPurifier saat disimpan (`HtmlSanitizer::forStorage`) dan saat ditampilkan (`sanitized_body`).
- **CSP ketat**: `script-src` tanpa `unsafe-inline`/`unsafe-eval`, nonce per request, CDN di-pin ke path versi tertentu, `script-src-attr 'none'`.
- **Anti-spam**: honeypot, rate limit, dan Cloudflare Turnstile opsional pada komentar & newsletter.
- **Audit**: aksi administratif tercatat di `audit_logs` dan `storage/logs/audit-*.log` (retensi `LOG_AUDIT_DAYS`, default 365 hari).
- **Privasi**: retensi data pribadi otomatis (`config/privacy.php`).
- **Error**: `APP_DEBUG=false` di produksi; exception database tidak membocorkan skema ke UI.
