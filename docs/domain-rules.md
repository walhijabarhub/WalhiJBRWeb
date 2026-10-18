# 📜 Panduan Aturan Domain & Invariant Bisnis (Domain Rules)

Dokumen ini mendokumentasikan aturan bisnis, siklus hidup entitas, dan batasan operasional pada platform **WALHI Jawa Barat**. Aturan ini wajib dipatuhi oleh seluruh developer dan AI coding agents untuk menjaga konsistensi data dan integritas keamanan.

---

## 1. Domain Konten & Publikasi (`Content`)

Tabel `contents` adalah entitas pusat yang menampung seluruh artikel, siaran pers, dokumen resmi, profil organisasi, hingga kampanye.

### A. Siklus Hidup Status (`ContentStatus`)
```text
[Draf (draft)] ───(Publish)───► [Dipublikasikan (published)] ───(Arsipkan)───► [Diarsipkan (archived)]
      │                                       │                                       │
      └───────────────────────────────────────┴───────────────────────────────────────┘
                                              │
                                     (Toggle Status Action)
```

1. **Status yang Diizinkan:**
   - `published`: Konten aktif dan dapat diakses oleh publik secara bebas.
   - `draft`: Konten belum selesai atau menunggu peninjauan editorial.
   - `archived`: Konten historis yang dinonaktifkan dari listing publik.
2. **Aturan Akses Publik:**
   - Halaman publik (`/blog`, `/konten/{slug}`, `/regulasi`, `/publikasi/*`) **hanya** menampilkan konten berstatus `published`.
   - Permintaan publik/tamu untuk melihat konten `draft` atau `archived` via `/konten/{slug}` atau endpoint unduhan **wajib menghasilkan HTTP `404 Not Found`** untuk mencegah *resource enumeration*.
   - Pengguna dengan hak kelola (`canManageContent()`) dapat melihat pratinjau konten non-publik saat terautentikasi.

### B. Kategori Konten (`ContentCategory`)
Seluruh konten terikat pada 22 nilai Enum resmi (`App\Enums\ContentCategory`):

| Kelompok | Kategori Enum (`value`) | Karakteristik / Penanganan Khusus |
| :--- | :--- | :--- |
| **Publikasi Berita** | `blog`, `siaran-pers`, `infografis`, `kertas-posisi`, `catatan-kritis` | Mendukung lampiran PDF, gambar cover, tag, dan komentar publik. |
| **Dokumen Resmi** | `regulasi`, `laporan-tahunan` | Berkas PDF disimpan ke **private storage**. |
| **Terbitan Berkala** | `newsletter`, `buletin-bumi`, `jurnal` | Publikasi periodik organisasi. |
| **Profil Organisasi**| `sejarah`, `visi-misi`, `dewan-nasional`, `eksekutif-nasional`, `eksekutif-daerah`, `kontak` | Kategori `kontak` adalah **kategori sensitif** (khusus Admin). |
| **Kampanye & Event** | `donasi`, `pekan-rakyat`, `kampanye-darurat` | Kategori `donasi` dan `kampanye-darurat` adalah **kategori sensitif** (khusus Admin). |
| **Beranda** | `banner`, `statistik`, `isu-kritis` | Hero banner slider interaktif, angka capaian, dan isu strategis. |

### C. Format Khusus Kolom `tags`
Beberapa kategori meng-encode metadata struktural ke dalam kolom `tags`:
1. **Kategori `banner`:**
   - Format: `{btn1_text}|{btn1_url}|{btn2_text}|{btn2_url}`
   - Contoh: `Isu Strategis|#isu|Lihat Publikasi|/publikasi/siaran-pers`
   - Setiap URL tombol divalidasi `App\Rules\SafeUrl` (hanya `http(s)://`, `/…`, `#…`). URL yang tidak lolos dirender sebagai `#`.
2. **Kategori `isu-kritis`:**
   - Format: `{nama_icon}|{badge_text}`
   - Contoh: `Icon-4.svg|Isu Air & Pesisir`
   - Default jika kosong: `Icon-4.svg|Isu`
3. **Kategori `regulasi`:**
   - Format: `{kategori_regulasi}, {penerbit}, {status}`
   - Contoh: `undang-undang, Pemerintah RI, berlaku`
4. **Kategori lainnya:**
   - String tag teks biasa dipisahkan koma (contoh: `advokasi, cirebon, pltu`).

### D. Sanitasi Konten HTML (Rich Text)
- Input `body` dari editor Quill disanitasi **dua kali**:
  1. Saat disimpan: `App\Services\Content\HtmlSanitizer::forStorage()` dipanggil di `ContentController::store/update`.
  2. Saat ditampilkan: accessor `Content::getSanitizedBodyAttribute()` (konfigurasi HTMLPurifier yang sama).
- Scheme yang diizinkan dalam tag `<a>` dan `<img>`: `http`, `https`, `mailto`, `tel`.
- Modal edit admin memuat body lewat `quill.clipboard.dangerouslyPasteHTML()` dari data yang sudah disanitasi server, bukan `innerHTML`.
- Rendering pada view Blade:
  - **✅ BENAR:** `{!! $item->sanitized_body !!}`
  - **❌ SALAH:** `{!! $item->body !!}` (membuka celah XSS).

### E. Cache & Hitungan Tayang
- Beranda (`content:home-sections:v2`), sitemap (`content:sitemap:v2`), serta data global kontak & kampanye di-cache lewat `App\Services\Content\ContentCache::remember()`.
- **Jangan menyimpan objek Eloquent langsung ke cache.** Laravel 13 memakai `cache.serializable_classes=false`, sehingga objek model gagal di-unserialize (pernah menyebabkan 500 di beranda). `ContentCache` menyimpan array lalu meng-hydrate kembali menjadi model.
- Cache dibersihkan otomatis oleh `ContentObserver` setiap konten disimpan, dihapus, atau dipulihkan. Bila struktur data cache berubah, naikkan versi key (`:v3`).
- Hitungan `views` dicatat ke Redis oleh `ViewCounter::record()` (halaman detail & unduhan dokumen) lalu ditulis ke database oleh `walhi:flush-views` tiap 5 menit. Selisih beberapa menit pada angka tayang adalah perilaku normal.

---

## 2. Domain Penyimpanan Berkas & Dokumen (SEC-008)

Platform memisahkan media publik dan dokumen organisasi secara tegas guna mencegah kebocoran dokumen non-publik:

```text
Berkas Unggahan Masuk
       │
       ├─► Bertipe Dokumen (PDF, DOC, DOCX, XLS, XLSX)
       │         ↓
       │   Storage::disk('local') ──► storage/app/private/documents/
       │         ↓
       │   Dilayani via Endpoint Terotorisasi: GET /dokumen/{slug}/unduh
       │         ↓
       │   Headers: nosniff, Content-Disposition: attachment
       │
       └─► Bertipe Media Gambar (JPG, PNG, WebP, GIF)
                 ↓
           Storage::disk('public') ──► storage/app/public/uploads/
                 ↓
           Dilayani via Web Server Statis: /storage/uploads/...
```

### Invariant & Aturan Operasional Storage:
1. **Penyimpanan Dokumen Baru:**
   - Berkas dokumen (`pdf`, `doc`, `docx`, `xls`, `xlsx`) disimpan pada disk `'local'` (`storage/app/private/documents/`).
   - Berkas ini **tidak dapat diakses langsung** melalui URL statis web server `/storage/...`.
2. **Otorisasi Unduhan Dokumen:**
   - Route unduhan: `GET /dokumen/{content:slug}/unduh` via `DocumentDownloadController`.
   - Otorisasi dikontrol oleh `ContentPolicy@view`:
     - Konten `published`: Dapat diunduh tamu publik.
     - Konten `draft`/`archived`: Tamu mendapat `404`; user tanpa izin mendapat `403`.
3. **Tanpa Open-Redirect:**
   - Dokumen bertautan eksternal (`http://`, `https://`) **tidak boleh** dilayani via redirector controller. Tautan keluar dirender langsung di view melalui `$item->download_url`.
4. **Pencegahan Header Injection:**
   - Nama file unduhan dibersihkan dari karakter CRLF (`\r`, `\n`) dan tanda kutip. Ekstensi file ditentukan dari berkas fisik di server (`pathinfo`).
5. **Alat Migrasi Dokumen Legacy:**
   - Jalankan dulu `php artisan walhi:migrate-documents --dry-run` (tidak mengubah apa pun), lalu `php artisan walhi:migrate-documents` untuk menyalin file dokumen publik lama ke disk privat dan menormalkan path database. Tambahkan `--delete-legacy` untuk menghapus salinan publik.
   - File privat yang sudah ada tidak pernah ditimpa; salinan publik hanya dihapus setelah checksum SHA-256 kedua salinan cocok. Konflik dibiarkan utuh dan perintah keluar dengan status gagal.
   - Selama masih ada file lama di disk publik, nginx menolak `/storage/documents/*` (404) sehingga dokumen tetap hanya bisa diunduh lewat endpoint berotorisasi.
6. **Referensi Berkas:**
   - `image_url` yang diketik manual tidak boleh menunjuk dokumen privat entri lain (validasi `privateDocumentPathRule`); dokumen harus diunggah langsung pada entrinya.
   - Berkas lama hanya dihapus saat diganti/dihapus bila tidak ada entri lain (termasuk yang di-soft-delete) yang masih memakainya di `image_url` atau `body`.

---

## 3. Domain Donasi & Pembayaran Online (`Donation`)

Modul donasi memakai Midtrans Snap. Kredensial dan perilaku diatur di `config/midtrans.php` (lihat [configuration.md](configuration.md#midtrans)).

### A. State Machine Status Donasi (`DonationStatus`)
```text
           ┌───────────────► [Berhasil (success)]   (Terminal)
           │
[Menunggu (pending)] ──────► [Gagal (failed)]       (Terminal)
           │
           └───────────────► [Kedaluwarsa (expired)] (Terminal)
```

1. **Integritas Transisi Status:**
   - `pending` dapat berpindah ke `success`, `failed`, atau `expired`.
   - `success`, `failed`, dan `expired` adalah **status terminal**. Webhook terlambat atau tidak berurutan ditolak dan dicatat.
   - Replay webhook dengan status yang sama diperlakukan idempoten (tanpa mutasi ulang).
2. **Pemetaan status Midtrans** (`DonationStatus::fromMidtrans($transactionStatus, $fraudStatus)`):

   | `transaction_status` | `fraud_status` | Hasil |
   | :--- | :--- | :--- |
   | `settlement` | apa pun selain `deny` | `success` |
   | `capture` | `accept` atau kosong | `success` |
   | `capture` | `challenge` | `pending` (menunggu keputusan fraud) |
   | apa pun | `deny` | `failed` |
   | `deny`, `cancel`, `failure` | — | `failed` |
   | `expire` | — | `expired` |
   | lainnya (`pending`, ...) | — | `pending` |

3. **Konkurensi & Locking:** `DonationService::processWebhook()` berjalan dalam `DB::transaction()` dengan `lockForUpdate()` pada baris donasi.

### B. Verifikasi Webhook (`POST /donasi/webhook`)
Urutan pemeriksaan di `DonationController::webhook`:
1. **Allowlist IP (opsional):** hanya bila `MIDTRANS_WEBHOOK_IP_WHITELIST=true`, IP pengirim dicocokkan dengan `MIDTRANS_WEBHOOK_IPS` (CIDR, dipisah koma). Default **nonaktif** karena Midtrans tidak menjamin daftar IP tetap.
2. **Field wajib** (`order_id`, `status_code`, `gross_amount`, `signature_key`), bila tidak ada → `400`.
3. **Signature SHA-512** (wajib): `hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey)`, bila salah → `403`.
4. **Nominal** `gross_amount` harus sama dengan `donations.amount`, bila beda → `400`.
5. **Konfirmasi Status API** (`MIDTRANS_VERIFY_WEBHOOK_STATUS=true`, default): signature tidak mencakup `transaction_status`/`fraud_status`, jadi status final diambil dari `GET /v2/{order_id}/status`. Bila API tidak bisa dihubungi → `503` agar Midtrans mengulang notifikasi.

### C. Rekonsiliasi Otomatis
- `walhi:donations-reconcile` (tiap 15 menit) mengecek donasi `pending` yang lebih tua dari `MIDTRANS_RECONCILE_AFTER_MINUTES` (default 15) ke Status API, untuk menutup notifikasi yang hilang.
- Donasi yang masih `pending` setelah `MIDTRANS_EXPIRE_AFTER_HOURS` (default 24) ditandai `expired`.
- Panggilan HTTP ke Midtrans memakai timeout (`MIDTRANS_TIMEOUT`, `MIDTRANS_CONNECT_TIMEOUT`) dan retry terbatas.

### D. Simulasi Pembayaran (Mock Payment)
Route `/donasi/mock-payment-status` **hanya terdaftar** saat environment `local` atau `testing`. Route ini tidak ada di produksi.

---

## 4. Domain Pengguna, Autentikasi & Otorisasi (RBAC)

### A. Peran
Aplikasi menerapkan 3 peran pengguna (`App\Enums\UserRole`):

| Peran (`UserRole`) | Hak Akses Konten Standar | Hak Akses Kategori Sensitif | Hak Aksi Destruktif (Hapus) |
| :--- | :---: | :---: | :---: |
| **`admin`** | Buat, Ubah, Terbitkan | **Ya** (`kontak`, `donasi`, `kampanye-darurat`) | **Ya** (Konten, Komentar, Subscriber) |
| **`editor`** | Buat, Ubah, Terbitkan | **Tidak** (`403 Forbidden`, termasuk halaman daftar kategori tersebut) | **Tidak** (`403 Forbidden`) |
| **`subscriber`** | Hanya Baca Publik | **Tidak** | **Tidak** |

- Admin terakhir tidak dapat menghapus akunnya sendiri.
- Parameter `{category}` pada routing `/admin/{category}` dibatasi regex nilai `ContentCategory`. Kategori lain → **`404`** sebelum controller dipanggil.

### B. Login
- Halaman login hanya di `/{ADMIN_LOGIN_PATH}` (default `/portal-jabar`). `/login` dan `/register` → `404`.
- Login menerima **email eksak**, atau *username* (bagian sebelum `@`) yang dicocokkan eksak pada domain resmi `walhijabar.or.id`, `walhijabar.co.id`, `walhi-jabar.org`. Tidak ada pencocokan `LIKE` atau nama.
- Throttle: 5 percobaan/menit per akun+IP dan 20/menit per IP. Lockout dicatat sebagai peringatan keamanan.
- Halaman lupa password selalu memberi pesan sukses yang sama (tidak membocorkan email terdaftar).
- Password minimal 12 karakter dengan huruf besar-kecil, angka, simbol, dan tidak ada di daftar password bocor (`PasswordRule::defaults()`).

### C. Autentikasi Dua Langkah (2FA TOTP)
- Bila `AUTH_2FA_ENFORCED=true` (default), akun `admin`/`editor` wajib mengaktifkan 2FA. Middleware `two-factor` (`EnsureTwoFactorEnabled`) mengarahkan akun yang belum mendaftar ke `/two-factor`, sehingga tidak ada akun yang terkunci.
- Setelah password benar, akun dengan 2FA aktif harus melewati `/two-factor-challenge` (kode 6 digit atau recovery code). Kode TOTP tidak dapat dipakai ulang (*replay protection*).
- Recovery code sekali pakai; bisa dibuat ulang dari halaman `/two-factor`.
- Reset oleh pengelola server: `php artisan walhi:reset-2fa email@domain` (tercatat di audit log).
- Aktivasi, penonaktifan, dan pembuatan ulang recovery code dicatat di audit log.

### D. Pembuatan Akun
- Tidak ada registrasi publik. Akun dibuat dengan `php artisan walhi:create-admin` (password lewat prompt tersembunyi, tanpa opsi `--password`).
- `AdminUserSeeder` aman diulang: tidak menimpa password yang sudah ada kecuali `FORCE_ADMIN_PASSWORD_RESET=true`; akun tanpa password terkonfigurasi mendapat password acak yang tidak ditampilkan beserta tautan reset.

---

## 5. Domain Komentar Artikel (`Comment`)

1. **Moderasi:** komentar baru berstatus `pending`; hanya `approved` yang tampil. Admin & editor dapat menyetujui atau menandai spam; hanya admin yang dapat menghapus.
2. **Anti-spam:**
   - Honeypot field tersembunyi `extra_phone`. Bila terisi, kiriman dibuang diam-diam (*silent drop*).
   - Rate limit `comment`: 3 kiriman/menit per IP.
   - Cloudflare Turnstile bila `TURNSTILE_SITE_KEY` dan `TURNSTILE_SECRET_KEY` diisi. Bila API Cloudflare tidak bisa dihubungi, kiriman ditolak (*fail-closed*).
3. **Validasi:** nama & email wajib, isi 5–5.000 karakter.
4. **Balasan:** maksimal 1 level (`parent_id`). Balasan harus menginduk pada komentar di artikel yang sama.

---

## 6. Domain Newsletter (`Subscriber`)

1. **Double opt-in:**
   - Pendaftaran membuat/menyalakan ulang subscriber dengan `is_active=false` dan mengirim email konfirmasi lewat antrean (`ConfirmNewsletterSubscription`).
   - Tautan konfirmasi adalah *signed URL* yang berlaku **7 hari** (`/newsletter/konfirmasi/{id}`). Setelah diklik: `is_active=true`, `confirmed_at` terisi.
   - Respons formulir selalu sama, baik email baru, sudah terdaftar, maupun spam (tidak membocorkan daftar pelanggan). Email konfirmasi ulang dibatasi sekali per 10 menit per alamat.
2. **Berhenti berlangganan:** `/newsletter/berhenti/{token}` (token acak 64 karakter per subscriber).
3. **Anti-spam:** honeypot `extra_name`, rate limit, Turnstile opsional (sama seperti komentar).
4. **Ekspor CSV aman:** `AdminSubscriberController@export` hanya mengekspor subscriber terkonfirmasi. Setiap nilai disanitasi `App\Services\CsvSanitizer` untuk mencegah formula injection (`=`, `+`, `-`, `@`, `\t`, `\r`). Ekspor dicatat di audit log.

> Email konfirmasi hanya terkirim bila SMTP dikonfigurasi (`MAIL_MAILER=smtp`). Dengan `MAIL_MAILER=log`, email hanya tercatat di log dan pendaftar tidak bisa mengonfirmasi.

---

## 7. Domain Privasi & Retensi Data

Diatur di `config/privacy.php` dan dijalankan oleh `walhi:prune-personal-data` (tanggal 1 tiap bulan pukul 03:00, `--dry-run` untuk melihat jumlah saja):

| Data | Aturan | Variabel |
| :--- | :--- | :--- |
| Donasi lebih tua dari N bulan | Nama → `Donatur Anonim`, email → `anonymized+<id>@...`, telepon → `-`. Nominal & status tetap (untuk laporan keuangan). | `PRIVACY_RETENTION_MONTHS` (24) |
| Komentar lebih tua dari N bulan | Email dianonimkan; nama & isi tetap tampil. | `PRIVACY_RETENTION_MONTHS` (24) |
| Subscriber tidak terkonfirmasi | Dihapus permanen setelah N hari. | `PRIVACY_UNCONFIRMED_SUBSCRIBER_DAYS` (30) |
| Subscriber yang dihapus (soft delete) | Dihapus permanen setelah N hari. | `PRIVACY_DELETED_SUBSCRIBER_DAYS` (30) |

Setiap eksekusi dicatat sebagai `PERSONAL_DATA_PRUNED`. Halaman `/kebijakan-privasi` harus tetap sejalan dengan aturan ini.

---

## 8. Domain Jejak Audit (`AuditLogService`)

Setiap tindakan administratif sensitif wajib memanggil:
```php
AuditLogService::log(string $action, string $modelName, mixed $modelId, array $details = []);
```

Pencatatan ditulis ke **tabel `audit_logs`** dan ke channel log **`audit`** (`storage/logs/audit-YYYY-MM-DD.log`, retensi `LOG_AUDIT_DAYS`, default 365 hari). Kegagalan menulis ke database tidak menggagalkan aksi; entri tetap tercatat di file.

### Aksi yang Saat Ini Dicatat
| Kelompok | Aksi |
| :--- | :--- |
| Konten | `CONTENT_CREATE`, `CONTENT_UPDATE`, `CONTENT_DELETE`, `CONTENT_TOGGLE_STATUS` |
| Komentar | `COMMENT_APPROVE`, `COMMENT_SPAM`, `COMMENT_DELETE` |
| Subscriber | `SUBSCRIBER_DELETE`, `SUBSCRIBER_EXPORT` |
| 2FA | `TWO_FACTOR_ENABLED`, `TWO_FACTOR_DISABLED`, `TWO_FACTOR_RECOVERY_CODES_REGENERATED`, `TWO_FACTOR_RESET_CLI` |
| Sistem | `PERSONAL_DATA_PRUNED` |

Setiap entri memuat user (id & email), IP, user-agent, model, id model, waktu, dan detail.
