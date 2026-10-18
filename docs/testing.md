# 🧪 Panduan Pengujian Otomatis (Testing Guide)

Dokumen ini memandu pengembang dan AI coding agents dalam menjalankan, memelihara, dan menambahkan pengujian otomatis pada platform **WALHI Jawa Barat**.

---

## 1. Ikhtisar Test Suite

- **Framework:** PHPUnit 12 (`php artisan test`)
- **Total:** **214 test, 965 assertion**, semuanya lulus (per Oktober 2026)
- **Cakupan:** autentikasi & 2FA, RBAC, manajemen konten, keamanan konten & CSP, dokumen privat, donasi & Midtrans, audit log, cache & pencarian, newsletter, retensi data, CAPTCHA, proxy tepercaya, SEO.

Perbarui angka di atas (dan di `AGENTS.md`) bila jumlah test berubah signifikan.

---

## 2. Menjalankan Pengujian

```bash
# Seluruh test suite (SQLite in-memory, cache array, mail array — lihat phpunit.xml)
php artisan test

# Satu file / class
php artisan test tests/Feature/DocumentStorageTest.php
php artisan test --filter=DocumentStorageTest

# Satu method
php artisan test --filter=test_public_can_download_published_document

# Cek format kode
vendor/bin/pint --test

# Di dalam container lokal
docker compose exec app php artisan test
```

### Menjalankan terhadap MySQL (seperti CI)
Beberapa perilaku hanya ada di MySQL (FULLTEXT, `CONCAT`). Untuk mengulang job MySQL CI secara lokal:

```bash
docker run -d --name walhi-test-mysql -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=walhi_test -p 33060:3306 mysql:8.0
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=33060 DB_DATABASE=walhi_test DB_USERNAME=root DB_PASSWORD=root php artisan test
docker rm -f walhi-test-mysql
```

---

## 3. Peta Test Suite per Domain

| Test File | Skenario Utama | Jumlah |
| :--- | :--- | :---: |
| `tests/Feature/SecurityTest.php` | Allowlist URL & penolakan `//` (SEC-006/012), ekstensi berbahaya & MIME spoofing (SEC-011), CSRF, nonce CSP (SEC-005), rate limit, HTMLPurifier. | 30 |
| `tests/Feature/LoggingPerformanceDataTest.php` | Audit log ke DB & channel `audit`, pencarian FULLTEXT/LIKE, cache beranda & sitemap (termasuk store yang men-serialize), buffer view, newsletter double opt-in, ekspor terkonfirmasi, prune data pribadi. | 21 |
| `tests/Feature/Auth/AuthHardeningTest.php` | Login eksak/username, throttle per akun, 2FA (enrol, challenge, replay, recovery code, reset CLI), seeder aman, reset password tanpa enumerasi, admin terakhir. | 20 |
| `tests/Feature/QaAuditRegressionTest.php` | Regresi QA: halaman kontak/pengaduan/privasi, unduhan regulasi & laporan, navigasi, typo editorial, domain lama. | 17 |
| `tests/Feature/ContentSecurityHardeningTest.php` | `SafeUrl`, sanitasi body saat disimpan, payload modal edit, CSP tanpa `unsafe-inline`, tidak ada handler inline, editor dilarang di kategori sensitif. | 16 |
| `tests/Feature/AdminTest.php` | Dashboard admin, listing, filter, toggle status, hapus konten. | 14 |
| `tests/Feature/DonationGatewayHardeningTest.php` | `fraud_status`, konfirmasi Status API, retry Snap, rekonsiliasi & kedaluwarsa, allowlist IP. | 13 |
| `tests/Feature/DocumentStorageTest.php` | Dokumen privat (SEC-008), otorisasi unduhan, anti open-redirect, injeksi header, file legacy, command migrasi. | 12 |
| `tests/Feature/RbacMatrixTest.php` | Matriks izin Admin/Editor/Subscriber, kategori sensitif, aksi hapus, kategori arbitrer → 404 (SEC-007/010). | 11 |
| `tests/Feature/SEOAndResponsiveTest.php` | Halaman statis, meta OpenGraph/Twitter, `sitemap.xml`, `robots.txt`. | 11 |
| `tests/Feature/TurnstileCaptchaTest.php` | Form tetap jalan tanpa key, token wajib & diverifikasi server, fail-closed, widget & CSP. | 7 |
| `tests/Feature/DonationTest.php` | State machine donasi, idempotensi, amount mismatch, locking (SEC-009/013). | 5 |
| `tests/Feature/ProfileTest.php` | Profil, verifikasi email, hapus akun. | 5 |
| `tests/Feature/BannerHeroTest.php` | Banner beranda (fallback, published vs draft, simpan/ubah). | 4 |
| `tests/Feature/AdminUserCommandTest.php` | `walhi:create-admin`, prompt tersembunyi, password lemah ditolak (SEC-003/004). | 3 |
| `tests/Feature/TrustedProxyTest.php` | Header `X-Forwarded-*` dari klien diabaikan tanpa proxy tepercaya. | 2 |
| `tests/Feature/Auth/*` (lainnya) | Login `/portal-jabar`, reset & konfirmasi password, update password, verifikasi email, registrasi nonaktif. | 21 |
| `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` | Smoke test. | 2 |

---

## 4. Konvensi Pembuatan Test Baru

### A. `RefreshDatabase`
Setiap feature test yang menyentuh database memakai trait `RefreshDatabase`.

### B. Vite dinonaktifkan secara global
`tests/TestCase.php` memanggil `$this->withoutVite()`, sehingga test yang merender Blade tidak butuh `public/build/manifest.json` (job test di CI tidak membangun aset). Jangan menghapusnya.

### C. Isolasi Storage
```php
Storage::fake('local');
Storage::fake('public');
```

### D. HTTP eksternal selalu di-fake
Midtrans dan Cloudflare Turnstile tidak boleh dipanggil sungguhan dari test:
```php
Http::fake(['api.sandbox.midtrans.com/*' => Http::response(['transaction_status' => 'settlement', 'gross_amount' => '50000.00'])]);
Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
```
Untuk Turnstile, gunakan *test keys* resmi Cloudflare (`1x00000000000000000000AA` / `1x0000000000000000000000000000000AA`).

### E. Email & antrean
```php
Notification::fake();   // newsletter, reset password
```

### F. Model & autentikasi
```php
$admin = User::factory()->create(['role' => 'admin']);
$this->actingAs($admin)->post('/admin/blog', [...])->assertSessionHasNoErrors();
```
`Content` tidak punya factory; buat dengan `Content::create([...])`.

`phpunit.xml` menyetel `AUTH_2FA_ENFORCED=false`, jadi admin di test tidak perlu 2FA. Test yang menguji 2FA menyalakannya sendiri dengan `config(['auth.two_factor.enforced' => true])`.

### G. Cache
Bila menguji fitur yang menyimpan data ke cache, uji juga dengan store yang men-serialize (`file`/`redis`), bukan hanya `array`, karena objek Eloquent ditolak saat unserialize di Laravel 13 (lihat `test_cached_pages_render_from_a_serializing_cache_store`).

### H. Regresi audit
Test perbaikan audit diberi docblock yang menyebut nomor issue (misal `#72`). Pertahankan pola ini agar asal-usul test mudah dilacak.

---

## 5. Continuous Integration

| Workflow | Kapan | Isi |
| :--- | :--- | :--- |
| `ci.yml` | Push & PR ke `production`/`main` | Test SQLite, test MySQL 8.0 (service container, migrasi dulu), `npm ci && npm run build`, build image Docker, validasi `docker-compose.prod.yml`. |
| `security.yml` | Push, PR, tiap Senin, manual | gitleaks (seluruh riwayat), `composer audit`, `npm audit`. |
| `deploy.yml` | Push ke `production` | Test ulang sebagai gerbang sebelum build & deploy. |

PR hanya boleh di-merge bila semua check hijau.
