# Changelog

Semua perubahan penting pada platform web WALHI Jawa Barat. Setiap merge ke `production` langsung dideploy, jadi entri dikelompokkan per tanggal rilis, bukan per nomor versi. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [Belum dirilis]

### Dokumentasi
- Seluruh dokumentasi diperbarui sesuai keadaan sistem setelah audit Oktober 2026.
- Dokumen baru: `docs/configuration.md` (referensi `.env`), `docs/operations.md` (runbook), `docs/admin-guide.md` (panduan pengelola konten), dan `CHANGELOG.md`.
- Alur branch ditetapkan: `production` = patokan & live, `main` = cermin otomatis, `prod` = usang.

### Ditambahkan
- Workflow `sync-main.yml` yang mem-*fast-forward* `main` ke `production` setiap ada push.

### Diubah
- Domain bawaan pada contoh `.env` produksi, `setup-ssl.sh`, `setup-vps.sh`, dan `docker-compose.prod.yml` menjadi `walhijabar.or.id`.
- `.env.production.example` memakai maintenance mode berbasis Redis (`APP_MAINTENANCE_DRIVER=cache`, `APP_MAINTENANCE_STORE=redis`), sama dengan default compose.

## 2026-10-17 — Hardening hasil audit production-readiness

Menutup issue audit #60–#94.

### Keamanan
- **Konten & frontend (#95):** validasi URL `SafeUrl`, sanitasi body saat disimpan, CSP ketat tanpa `unsafe-inline` (Alpine build CSP + `csp-actions.js`), CDN di-pin per versi, editor dilarang membuka kategori sensitif.
- **Autentikasi (#101):** 2FA TOTP wajib untuk admin/editor beserta recovery code dan `walhi:reset-2fa`; login hanya email/username eksak; throttle per akun; seeder tidak lagi menimpa password; reset password tanpa enumerasi email; admin terakhir tidak bisa menghapus akunnya.
- **Donasi (#97):** pemetaan `fraud_status`, status webhook dikonfirmasi ke Status API Midtrans, timeout & retry HTTP, rekonsiliasi otomatis (`walhi:donations-reconcile`), allowlist IP webhook yang dapat dikonfigurasi.
- **CAPTCHA (#106):** Cloudflare Turnstile opsional untuk komentar & newsletter.

### Infrastruktur & Operasional
- **Docker/Nginx/SSL (#99):** volume persisten untuk seluruh `storage/app` (termasuk dokumen privat), Redis, container `worker` & `scheduler`, healthcheck, IP klien yang benar (`X-Forwarded-*` dari klien dibuang, real IP Cloudflare opsional), template nginx http/https, SSL metode webroot dengan reload otomatis.
- **Deploy & backup (#100):** image dibangun di CI dan disimpan di GHCR per commit, migrasi sebelum swap container, health check dengan rollback otomatis, backup DB + file yang diverifikasi (off-site & notifikasi opsional), `docker-restore.sh`, action GitHub di-pin ke SHA, gitleaks, test MySQL di CI, Dependabot, pembaruan dependensi rentan.
- **Data & performa (#98):** tabel `audit_logs` + channel log `audit`, pencarian FULLTEXT, cache beranda & sitemap, buffer hitungan view, newsletter *double opt-in*, retensi data pribadi (`walhi:prune-personal-data`).

### Perbaikan
- Beranda & sitemap error 500 karena objek Eloquent di cache (#102).
- Penyesuaian setelah migrasi repo ke `walhijabarhub/WalhiJBRWeb` dan deploy pertama, termasuk reload nginx setelah penerbitan sertifikat (#103).
- Test CI gagal karena Vite manifest tidak ada (#104).
- Deploy CI gagal menarik image GHCR private; kini memakai token sementara (#105).

## 2026-09 dan sebelumnya

- Perbaikan keamanan SEC-001 s.d. SEC-014, penyimpanan dokumen privat & unduhan terotorisasi (SEC-008).
- Perbaikan keamanan #48–#53: path traversal saat hapus gambar, kategori sensitif khusus admin, throttle reset password, cookie sesi aman, pembaruan dependensi, redirect HTTP → HTTPS.
- Slider banner beranda, kartu publikasi bergaya neobrutalis, perbaikan tampilan mobile & header, verifikasi Google Search Console.

Riwayat lengkap: `git log origin/production`.
