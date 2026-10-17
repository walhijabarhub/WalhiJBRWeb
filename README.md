<div align="center">

# 🌿 WALHI Jawa Barat — Official Web Platform

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)
[![License: CC BY-NC 4.0](https://img.shields.io/badge/License-CC_BY--NC_4.0-lightgrey.svg?style=for-the-badge)](LICENSE)

<br />

<img src="./docs/images/banner.png" alt="WALHI Jawa Barat Web Platform" width="100%" />

<br />

**Portal Informasi, Advokasi Lingkungan, Publikasi Kebijakan, dan Gerakan Rakyat WALHI Jawa Barat**

[Fitur Utama](#-fitur-utama) • [Panduan Cepat](#-panduan-menjalankan-aplikasi) • [Struktur Sistem](#-arsitektur--struktur-direktori) • [Panduan AI Agents](AGENTS.md) • [Lisensi](#-lisensi)

</div>

---

## 📖 Tentang Proyek

Website resmi **Wahana Lingkungan Hidup Indonesia (WALHI) Jawa Barat** dirancang untuk mendokumentasikan, mengadvokasi, dan menggalang solidaritas publik demi kelestarian ekosistem dan keadilan ruang hidup rakyat di Jawa Barat.

Mengusung gaya desain **Neo-Brutalisme Hijau Ekologis**, platform ini memberikan pengalaman pengguna yang lugas, responsif, berbobot, serta mudah diakses oleh publik, pegiat lingkungan, jurnalis, maupun donatur.

---

## ✨ Fitur Utama

- 📰 **Portal Publikasi & Advokasi:**
  - Artikel Berita & Blog Lingkungan (pencarian FULLTEXT di MySQL)
  - Siaran Pers Resmi & Rilis Sikap Organisasi
  - Infografis, Kertas Posisi & Catatan Kritis
  - Laporan Tahunan & Database Regulasi Lingkungan (dokumen diunduh lewat endpoint terotorisasi)
- 💚 **Gerakan Dukungan & Donasi:**
  - Donasi online lewat Midtrans Snap (status dikonfirmasi ke Status API Midtrans, rekonsiliasi otomatis tiap 15 menit)
  - Halaman Transparansi Dana
- 👥 **Profil Organisasi & Jaringan:**
  - Direktori Lembaga Anggota di Kabupaten/Kota Jawa Barat
  - Visi, Misi, Nilai Perjuangan, dan Program Strategis
  - Garis Sejarah Gerakan sejak 1980 & Struktur Kepengurusan
- 🎪 **Pekan Rakyat Lingkungan Hidup & Kampanye Darurat**
- 💬 **Interaksi Publik:**
  - Komentar artikel dengan moderasi, honeypot, rate limit, dan CAPTCHA Cloudflare Turnstile (opsional)
  - Newsletter dengan *double opt-in* dan tautan berhenti berlangganan
  - Halaman Pengaduan, Kontak & Kebijakan Privasi
- 🔐 **Backoffice CMS & Panel Admin:**
  - Manajemen Konten per kategori (draft → published → archived), editor Quill
  - Role `admin` / `editor` dengan **2FA (TOTP) wajib**
  - Moderasi komentar, ekspor subscriber (CSV aman dari formula injection)
  - Jejak audit (*audit trail*) di tabel `audit_logs` + file `audit.log`

---

## 🚀 Panduan Menjalankan Aplikasi

Anda dapat menjalankan project ini dengan dua metode:

### 🐳 Metode 1: Menggunakan Docker (Direkomendasikan)

Metode ini paling praktis karena seluruh dependensi (PHP 8.4, Composer, Node.js build, SQLite database, dan permissions) dikonfigurasi secara otomatis di dalam container.

1. **Clone repository dan masuk ke direktori:**
   ```bash
   git clone https://github.com/walhijabarhub/WalhiJBRWeb.git
   cd WalhiJBRWeb
   ```

2. **Jalankan container:**
   ```bash
   docker compose up -d --build
   ```

3. **Buat akun admin lokal** (password diminta lewat prompt tersembunyi):
   ```bash
   docker compose exec app php artisan walhi:create-admin --name="Admin Lokal" --email=admin@walhijabar.or.id
   ```

4. **Buka aplikasi di browser:**
   - 🌐 **Website Publik:** [http://localhost:8000](http://localhost:8000)
   - 🔑 **Login Admin:** [http://localhost:8000/portal-jabar](http://localhost:8000/portal-jabar) *(dapat disesuaikan via `ADMIN_LOGIN_PATH` di `.env`)*
   - 📊 **Panel Admin:** [http://localhost:8000/admin](http://localhost:8000/admin) *(memerlukan login + 2FA)*

5. **Perintah Docker yang Berguna:**
   ```bash
   # Melihat log container secara real-time
   docker compose logs -f

   # Menghentikan container
   docker compose down

   # Menjalankan perintah artisan di dalam container
   docker compose exec app php artisan migrate:status
   ```

> `docker-compose.yml` hanya untuk pengembangan lokal. Server produksi memakai `docker-compose.prod.yml`; lihat [DEPLOYMENT.md](DEPLOYMENT.md).

---

### 💻 Metode 2: Menjalankan Secara Manual (Lokal)

Jika Anda ingin menjalankan langsung di lingkungan host (memerlukan PHP 8.3+ / 8.4 rekomendasi, Composer, dan Node.js 20+):

1. **Install dependensi PHP & Node.js:**
   ```bash
   composer install
   npm install
   ```

2. **Setup environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Jalankan migrasi database & seeder:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   php artisan walhi:create-admin
   ```

4. **Jalankan server pengembangan** (server, queue worker, log & Vite sekaligus):
   ```bash
   composer run dev
   ```
   Atau secara terpisah: `npm run build` lalu `php artisan serve`. Email (konfirmasi newsletter, reset password) masuk ke `storage/logs/laravel.log` karena `MAIL_MAILER=log`.

---

## 🔑 Akun Admin Lokal

Tidak ada lagi password bawaan. Seeder `AdminUserSeeder` memakai `ADMIN_EMAIL`/`ADMIN_PASSWORD` dari `.env`; bila `ADMIN_PASSWORD` kosong, akun dibuat dengan password acak yang **tidak ditampilkan** dan tautan reset dikirim ke email (lokal: tercatat di log). Cara termudah membuat akun lokal adalah `php artisan walhi:create-admin` seperti di atas.

Login pertama akan langsung diarahkan ke halaman aktivasi **2FA** (pindai QR dengan Google Authenticator/Aegis/1Password, simpan *recovery codes*). Untuk pengembangan lokal saja, 2FA bisa dimatikan dengan `AUTH_2FA_ENFORCED=false` di `.env`. Panduan lengkap: [docs/admin-guide.md](docs/admin-guide.md).

---

## 🏗️ Arsitektur & Struktur Direktori

Untuk penjelasan mendalam mengenai pola arsitektur, diagram aliran data, dan skema database, silakan pelajari dokumen:
👉 **[Dokumen Arsitektur Lengkap (ARCHITECTURE.md)](ARCHITECTURE.md)**

```
WalhiJBRWeb/
├── app/                   # Controllers, Models, Services, Policies, Rules, Commands
├── database/              # Migrations & Seeders
├── resources/
│   ├── css/               # Tailwind CSS styling
│   ├── js/                # Alpine.js (build CSP) & csp-actions.js
│   └── views/             # Blade templates & components
├── public/                # Static assets (images, icons, build)
├── docker/                # Konfigurasi nginx, PHP-FPM, MySQL untuk container
├── docs/                  # Dokumentasi teknis & operasional
├── deploy.sh              # Deploy produksi (dipanggil GitHub Actions)
├── docker-backup.sh       # Backup DB + file
├── docker-restore.sh      # Restore dari backup
├── docker-compose.yml     # Stack pengembangan lokal
└── docker-compose.prod.yml # Stack produksi (app, worker, scheduler, db, redis, nginx)
```

---

## 📚 Peta Dokumentasi

| Dokumen | Isi |
| :--- | :--- |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Arsitektur sistem, alur request, komponen produksi |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Setup VPS, SSL, CI/CD, backup & restore |
| [docs/operations.md](docs/operations.md) | Runbook operasional harian & penanganan insiden |
| [docs/configuration.md](docs/configuration.md) | Referensi seluruh variabel `.env` |
| [docs/admin-guide.md](docs/admin-guide.md) | Panduan pengelola konten: login, 2FA, konten, komentar |
| [docs/domain-rules.md](docs/domain-rules.md) | Aturan bisnis: konten, dokumen, donasi, newsletter, privasi |
| [docs/database.md](docs/database.md) | Skema tabel & relasi |
| [docs/frontend.md](docs/frontend.md) | Sistem desain, Blade, Alpine, aturan CSP |
| [docs/testing.md](docs/testing.md) | Test suite & CI |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Alur branch (`production` = patokan), commit, PR |
| [SECURITY.md](SECURITY.md) | Pelaporan celah keamanan & kontrol keamanan |
| [AGENTS.md](AGENTS.md) | Panduan untuk AI coding agent |
| [CHANGELOG.md](CHANGELOG.md) | Riwayat perubahan |

---

## 🤝 Panduan Kolaborasi Tim

Kami menyambut kontribusi dari seluruh anggota tim dan komunitas! Sebelum mulai berkontribusi, harap membaca panduan berikut:

- 📋 **[Panduan Kontribusi (CONTRIBUTING.md)](CONTRIBUTING.md)**: Alur kerja git branch, konvensi commit pesan, dan langkah Pull Request. Branch `production` adalah patokan dan langsung dideploy; `main` adalah cerminnya.
- 📜 **[Kode Etik (CODE_OF_CONDUCT.md)](CODE_OF_CONDUCT.md)**: Standar komunikasi dan etika kerja komunitas.
- 🔒 **[Kebijakan Keamanan (SECURITY.md)](SECURITY.md)**: Prosedur pelaporan celah keamanan.

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah **[Creative Commons Attribution-NonCommercial 4.0 International (CC BY-NC 4.0)](LICENSE)**.

> 📢 **Ketentuan Penggunaan:** Boleh digunakan dan diadaptasi untuk keperluan edukasi, sesama organisasi non-pemerintah (NGO/CSO), atau komunitas lingkungan hidup. **DILARANG KERAS digunakan untuk tujuan komersial korporasi atau industri ekstraktif.**

Hak Cipta © 2026 **Wahana Lingkungan Hidup Indonesia — Jawa Barat (WALHI Jawa Barat)**.
