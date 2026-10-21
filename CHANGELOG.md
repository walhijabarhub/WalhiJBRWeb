# Changelog

Semua perubahan penting pada platform web WALHI Jawa Barat. Setiap merge ke `production` langsung dideploy, jadi entri dikelompokkan per tanggal rilis, bukan per nomor versi. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [Belum dirilis]

### Optimasi Oktober 2026 (#130–#152)
Hasil analisis optimasi repo setelah audit produksi 19 Oktober 2026. Setiap PR berdiri sendiri, kecuali #140 (di atas #139) dan #141 (di atas #134).

#### Performa
- Cache 1 tahun `immutable` hanya untuk `/build/` (berkas Vite ber-hash); aset statis lain 7 hari agar penggantian logo/gambar segera terlihat (#130).
- Google Fonts tidak lagi dimuat dua kali (`@import` di `app.css` dihapus) (#131).
- `/regulasi`: enam `COUNT` menjadi satu query agregat ber-cache (#135).
- Halaman detail konten: sidebar & rekomendasi dari cache, tanpa `ORDER BY RAND()` (#136).
- Lazy-load `<img>` dan background di bawah layar pertama; `call-to-action-bg.jpg` 739 KB → 110 KB (#138).
- Ikon Lucide dibundel dari npm (49 ikon, 3,8 KB gzip) menggantikan UMD lengkap dari unpkg; unpkg dihapus dari CSP (#140).
- 534 berkas template tak terpakai (35,6 MB) dihapus dari `public/assets` (#141).
- Gambar unggahan admin diperkecil ke maks. 1920 px dengan orientasi EXIF dibetulkan (#146).

#### Diperbaiki
- Gambar isu kritis & banner fallback beranda 404 (#134).
- Ikon isu kritis & statistik di admin 404; `<img src="">` di form admin (#142).
- Soft-delete konten tidak lagi menghapus berkas lampiran; berkas dihapus saat force delete (AUD-12) (#144).
- Sumbu grafik donasi admin tidak lagi negatif saat belum ada data (#145, test regresi #149).
- `favicon.ico` berukuran 0 byte (#133).
- Berkas yang dihapus dari `public/` tidak lagi tertinggal di volume nginx (`docker-entrypoint.sh` memakai `cp -ru` tanpa penghapusan) (#150); sinkronisasi dipicu sidik isi `public/`, bukan mtime `build/` (#151).
- Build image tidak lagi bergantung pada pecl.php.net: phpredis 6.2.0 dipasang dari tarball rilis GitHub ber-checksum SHA-256 (#152).

#### Ditambahkan
- `/up` memeriksa database dan cache/Redis (500 bila salah satunya gagal) (#143).
- Pruning harian `audit_logs` (`PRIVACY_AUDIT_LOG_DAYS`, default 730 hari), `failed_jobs`, `job_batches`, dan token reset password (#132); jumlah audit log yang dihapus dicatat sebagai `AUDIT_LOGS_PRUNED` (#148).
- Smoke test seluruh halaman publik dan test regresi untuk tiap perubahan di atas (total 299 test).

#### Diubah
- Aset `<head>` 15 halaman publik disatukan di `partials/head-assets` (#139).
- Statistik donasi admin disatukan di `Donation::monthlySuccessChart()` / scope `successful()`; query dashboard hanya memuat kolom yang dipakai (#137).
- Dependensi `@tailwindcss/vite` (tidak dipakai) dan folder duplikat `iqon/` di root dihapus (#133).

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
