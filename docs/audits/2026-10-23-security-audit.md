# Audit Keamanan WALHI Jawa Barat — 23 Oktober 2026

Basis: `origin/production` @ `16d298fd40bf` (sudah termasuk PR #159).
Branch remediasi: `security/audit-hardening-2026-10`.
Cakupan: aplikasi Laravel, dependensi Composer/npm, Dockerfile, `docker-compose.prod.yml`, konfigurasi nginx/PHP, workflow GitHub Actions, riwayat Git (secret).

**Batasan.** Tidak ada akses SSH ke VPS dalam audit ini. Kondisi production hanya diverifikasi dari luar (HTTPS publik) dan dari log GitHub Actions. Versi nginx/MySQL/Redis yang *benar-benar* berjalan di VPS **belum diverifikasi** (`server_tokens off` menyembunyikan versi nginx).

## A. Ringkasan Eksekutif

Kontrol aplikasinya kuat: RBAC berbasis policy, 2FA wajib, CSP dengan nonce, HTMLPurifier, webhook Midtrans terverifikasi dengan state machine terkunci, dan dokumen privat diunduh lewat endpoint berotorisasi. Risiko terbesar ada di lapisan infrastruktur:

1. **nginx 1.27** (tag mengambang `nginx:1.27-alpine`): cabang ini sudah tidak menerima patch dan masuk rentang rentan banyak advisory nginx 2026. → diperbaiki ke **1.30.6**, dipin ke digest.
2. **MySQL 8.0 sudah EOL** (April 2026). → **belum diubah** dengan sengaja (lihat SA-10); perlu langkah operator terpisah.
3. **Node.js 20 (EOL)** di tahap build, serta alat build/kompiler di image runtime. → diperbaiki.

Dua celah aplikasi dari audit kode sebelumnya (komentar ke konten non-published, data donatur terlihat editor) sudah diperbaiki di PR #159 dan **sudah ter-deploy** (`production @ 16d298fd40bf`, deploy run 39988581931 sukses).

Perubahan di branch ini **belum ter-deploy**; deploy terjadi otomatis saat PR di-merge ke `production`.

## B. Register Temuan

| ID | Referensi | Komponen / versi | Severity · Confidence | Eksposur production | Status |
|---|---|---|---|---|---|
| SA-01 | CVE-2026-15176, -9256, -42945, -48142, -42934 (+ lainnya, lihat tabel nginx) | nginx 1.27 (`nginx:1.27-alpine`) | Sedang · Tinggi (versi rentan); eksploitasi bergantung direktif | Kemungkinan (alias + `return`, `set`, `charset` dipakai); versi live belum terverifikasi | **Fixed** di branch (1.30.6), menunggu deploy |
| SA-02 | CVE-2026-109618 (GHSA-w8hw-w9fx-j79w), CVE-2026-109619 (GHSA-6wmf-4rv5-p3rp) | PHP < 8.4.27 | Kritis · Tinggi | Ditutup oleh PR #155 | **Fixed** (image berisi PHP 8.4.27, `session.upload_progress.enabled=Off`, `expose_php=Off` — terverifikasi di image hasil build) |
| SA-03 | CVE-2026-115560 | PHP | — · Rendah | — | **Unverified**: tidak ditemukan di GitHub Advisory Database maupun bagian changelog php.net yang dibaca. Jangan dianggap ada sampai ada sumber resmi. |
| SA-04 | GHSA-f9x3-7vq9-cph6 (CVE-2026-114509), GHSA-5wcm-3v2w-8362 (CVE-2026-114508), GHSA-jh5r-qr3c-85q8, GHSA-crmm-hgp2-wgrp, GHSA-5vg9-5847-vvmq, GHSA-66hf-2p6w-jqfw | laravel/framework | Bervariasi | Terkunci **v13.35.0** ≥ semua versi patch (13.29.0 / 13.27.0 / 13.30.0 / 13.12.0 / 13.10.0; GHSA-66hf hanya ≤ 8.x) | **Not Applicable** |
| SA-05 | GHSA-9jrq-w75r-8gcw, GHSA-rq7h-c2jc-7f22, GHSA-698v/r825/587h (launch-editor) | vite 8.1.3 (dev server) | Tinggi (npm) · Tinggi | Tidak ada: production hanya memakai hasil `npm run build` | **Fixed** (vite 8.3.5) |
| SA-06 | GHSA-rj75-hqrm-r3gf | postcss-selector-parser 6.1.4 via tailwindcss 3.4.19 | Moderate · Tinggi | Tidak ada: DoS saat build, input hanya CSS milik repo | **Open / diterima** — perlu migrasi Tailwind 4 (breaking) |
| SA-07 | Internal | Node.js 20 di tahap build Dockerfile & CI | Rendah · Tinggi | Build-time | **Fixed** (node 24.22.1 LTS, dipin digest) |
| SA-08 | Internal | Kompiler, header `-dev`, git, curl paket di image runtime | Rendah · Tinggi | Memperbesar permukaan serangan pasca-RCE | **Fixed** (virtual package dihapus setelah build; `git`/`gcc`/`make`/`sqlite3` tidak ada di image) |
| SA-09 | Internal | Container tanpa `no-new-privileges` | Rendah · Tinggi | — | **Fixed** untuk app, worker, scheduler, redis, web; **db ditunda** (SA-10) |
| SA-10 | Internal | MySQL 8.0 (tag mengambang, EOL April 2026) | Sedang · Tinggi | Tidak ada patch keamanan baru | **Open (P1)** — lihat bagian F |
| SA-11 | Internal | Redis `redis:7.4-alpine` (tag mengambang) | Rendah · Sedang | Versi live tidak diketahui | **Fixed** (7.4.11, dipin digest). Advisory Redis tidak diverifikasi satu per satu. |
| SA-12 | Internal (audit kode) | Komentar ke konten draft/archived | Sedang · Tinggi | Ada | **Fixed & deployed** (PR #159) |
| SA-13 | Internal (audit kode) | Nama/nominal donatur terlihat editor di dashboard | Sedang · Tinggi | Ada | **Fixed & deployed** (PR #159) |
| SA-14 | Internal | `import:wp-csv --truncate` tanpa konfirmasi/penjaga production, FK dimatikan | Sedang · Tinggi | Hanya lewat CLI server | **Open (P2)** |
| SA-15 | Internal | Redis `volatile-lru` + key tanpa TTL (ViewCounter) | Rendah (ketersediaan) | Sesi bisa terbuang saat memori penuh | **Open (P2)** |
| SA-16 | Internal | Secret di repo & riwayat Git | — | — | **Not Applicable**: gitleaks v8.30.1, 322 commit, 0 temuan; `.env` tidak pernah di-commit; nilai di `.env.example` placeholder |

### Kesesuaian advisory nginx (sumber: nginx.org/en/security_advisories.html)

Semua ID dari laporan sebelumnya **ada** di daftar resmi. Versi aman: **1.30.6+** (stable) / **1.31.7+** (mainline).

| CVE | Masalah | Relevan untuk konfigurasi ini? |
|---|---|---|
| CVE-2026-15176 | Overflow, alias + rewrite | Mungkin: `location ^~ /storage/` memakai `alias` dengan `return` bertingkat |
| CVE-2026-93479 | Overflow, script evaluation | Tidak pasti (1.30.0–1.30.5; tidak memengaruhi 1.27) |
| CVE-2026-95101, CVE-2026-42530 | HTTP/3 | Tidak: tidak ada `listen … quic` |
| CVE-2026-97629 | TLS session tickets | Tidak: `ssl_session_tickets off` |
| CVE-2026-97205 | AIO | Tidak: `aio` tidak dikonfigurasi |
| CVE-2026-42533 | map + regex | Tidak: tidak ada `map` |
| CVE-2026-60005 | slice | Tidak dikonfigurasi |
| CVE-2026-56434 | SSI | Tidak dikonfigurasi |
| CVE-2026-9256, CVE-2026-42945 | rewrite module | Mungkin: `set` dan `return` dipakai |
| CVE-2026-48142, CVE-2026-42934 | charset (Low) | Ya: `charset utf-8` |

Modul HTTP/3, slice, dav, mp4, dan AIO memang dikompilasi di image resmi, tetapi tidak diaktifkan oleh konfigurasi; konfigurasi tidak diubah untuk mengaktifkannya.

## C. Perubahan (branch `security/audit-hardening-2026-10`)

| File | Alasan |
|---|---|
| `docker-compose.prod.yml` | nginx → `1.30.6-alpine3.24@sha256:47928bf6…`; redis → `7.4.11-alpine@sha256:57e4255c…`; `no-new-privileges` untuk app/worker/scheduler (anchor), redis, web. MySQL sengaja tidak diubah. |
| `Dockerfile` | Tahap build Node 24.22.1 LTS (dipin digest); alat build & header `-dev` dipindah ke virtual package yang dihapus; git/unzip hanya saat `composer install`. |
| `.github/workflows/ci.yml`, `security.yml` | `node-version: 24` (sama dengan Dockerfile). |
| `package.json`, `package-lock.json` | vite `^8.3.5` (perubahan lock hanya rantai vite/rolldown/lightningcss/postcss). |
| `tests/Feature/DockerfileTest.php` | 3 tes: runtime tanpa alat build, Node LTS terpin, nginx/redis terpin + `no-new-privileges`. Ketiganya terbukti gagal terhadap file lama. |
| `DEPLOYMENT.md`, `AGENTS.md`, `docs/testing.md`, `docs/frontend.md` | Versi image dan jumlah tes. |

## D. Hasil Pengujian

| Perintah | Hasil |
|---|---|
| `php artisan test` | Lulus — 306 tes, 1332 assertion |
| `vendor/bin/pint --test` | Lulus |
| `composer audit --locked` | Tidak ada advisory |
| `npm audit --omit=dev` | 0 kerentanan (`npm audit` penuh: 3 moderate, rantai Tailwind 3 — SA-06) |
| `npm run build` | Lulus |
| `docker build` (image aplikasi) | Lulus; PHP 8.4.27, semua ekstensi termuat, GD JPEG berfungsi, Laravel 13.35.0 |
| `nginx -t` (nginx 1.30.6; http/https × realip none/cloudflare) | 4/4 lulus |
| `docker compose -f docker-compose.prod.yml config --quiet` | Lulus |
| Uji integrasi stack penuh (compose production, secret sintetis, port 18080) | Semua service healthy; `/`=200, `/up`=200, `/.env`=403, `/.git/config`=403, `/storage/documents/*`=404, `/storage/*.php`=404, `/status`,`/ping`,`/login`=404, `/admin` tamu=302, webhook tanpa signature=400, POST tanpa CSRF=419, header keamanan & CSP ada, 0 error di log |
| gitleaks v8.30.1 (seluruh riwayat) | 0 temuan |

Tidak dijalankan: DAST (OWASP ZAP) — tidak ada staging terpisah; uji di VPS — tidak ada akses SSH.

## E. Verifikasi Deployment

- Terakhir ter-deploy: `16d298fd40bf` (PR #159), image `ghcr.io/walhijabarhub/walhijbrweb:16d298fd40bf`, digest `sha256:3dfe7f961e48242ab964f94cb27b8d9a8fe37c52c0600ee193b98cfd596c3182`, log: "DEPLOY BERHASIL".
- Pemeriksaan publik setelah deploy: `/` dan `/up` 200, bare domain 301 → www, `/.env` 403, `/storage/documents/*` 404, `/login` 404, HSTS/CSP/X-Frame-Options/nosniff ada, cookie sesi `secure; httponly; samesite=lax`.
- **Dampak deploy branch ini (perkiraan):** container `redis` dan `web` dibuat ulang (image baru), `app/worker/scheduler` dibuat ulang seperti biasa. `db` **tidak** disentuh. Redis dibuat ulang di `deploy.sh` langkah 5 saat mode maintenance aktif; data (sesi, antrean, flag maintenance) bertahan lewat AOF di volume, tetapi ada jeda beberapa detik saat Redis restart.
- **Rollback:** `deploy.sh` otomatis kembali ke image & commit sebelumnya bila health check gagal; Redis kembali ke tag `7.4-alpine` di cache VPS (format data sama dalam 7.4.x).

## F. Risiko Tersisa

1. **MySQL 8.0 EOL (SA-10).** Belum dipin/di-upgrade di PR ini karena perubahan image `db` membuat ulang database saat deploy, dan rollback otomatis akan menjalankan image 8.0 lama di atas data dictionary yang sudah di-upgrade.
2. **Versi runtime VPS belum terverifikasi.** Perlu dijalankan oleh operator (tanpa menampilkan isi `.env`):
   `docker compose -f docker-compose.prod.yml exec web nginx -v`, `… exec db mysqld --version`, `… exec redis redis-server --version`, `docker inspect --format '{{.Image}}' walhi_prod_nginx walhi_prod_db walhi_prod_redis`.
3. SA-06 (Tailwind 3), SA-14, SA-15, ditambah temuan kecil dari audit kode: celah retensi `walhi:prune-personal-data`, tautan konfirmasi newsletter yang bisa mengaktifkan ulang langganan, slug kosong untuk judul non-Latin, webhook dengan field array menghasilkan 500.

## G. Langkah Berikutnya

| Prioritas | Pekerjaan | Pemilik | Kriteria selesai |
|---|---|---|---|
| P0 | Review & merge branch ini; pantau deploy | Maintainer | Deploy run sukses; `nginx -v` di VPS = 1.30.6; situs 200 |
| P1 | Upgrade MySQL: cek versi live → backup terverifikasi (`docker-restore-test.sh`) → pin 8.0.46 → upgrade ke 8.4 LTS di jendela pemeliharaan, dengan rencana restore (bukan rollback image) | Operator VPS | `mysqld --version` 8.4.x, restore test lulus, CI MySQL disamakan |
| P1 | Verifikasi versi runtime VPS (perintah di F.2) dan catat di laporan ini | Operator VPS | Versi & digest tercatat |
| P2 | Penjaga `import:wp-csv --truncate` (konfirmasi + tolak di production) | Developer | Tes: production menolak tanpa `--force` |
| P2 | TTL untuk key ViewCounter / tinjau kebijakan Redis | Developer | Tidak ada key tanpa TTL selain antrean |
| P2 | Perbaiki celah retensi `walhi:prune-personal-data` | Developer | Tes untuk pelanggan unsubscribe & daftar ulang |
| P3 | Migrasi Tailwind 4 (menutup SA-06) | Developer | `npm audit` 0, tampilan tidak berubah |
| P3 | Slug kosong, tautan konfirmasi lama, webhook 500 | Developer | Tes regresi masing-masing |

Audit ini tidak menyatakan sistem bebas kerentanan; yang tercantum di atas adalah yang diuji, diperbaiki, dan yang masih belum terverifikasi.
