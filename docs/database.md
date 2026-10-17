# 🗄️ Dokumentasi Basis Data & Skema (Database Guide)

Dokumen ini memetakan skema tabel, indeks, relasi Eloquent, dan casting tipe data pada platform **WALHI Jawa Barat**. Isinya diturunkan langsung dari `database/migrations/`. Bila ada perbedaan, migrasi yang benar, dan dokumen ini wajib diperbarui.

---

## 1. Driver Basis Data

| Lingkungan | Driver | Catatan |
| :--- | :--- | :--- |
| **Local / Testing** | `sqlite` | `database/database.sqlite` (lokal) atau `:memory:` (PHPUnit). |
| **CI** | `sqlite` + `mysql` | Test dijalankan dua kali: SQLite dan MySQL 8.0 (service container). |
| **Production** | `mysql` | MySQL 8.0 di container `db`, charset `utf8mb4`, collation `utf8mb4_unicode_ci`. |

Fitur khusus MySQL:
- Indeks **FULLTEXT** `ft_contents_title_body` hanya dibuat di MySQL/MariaDB. Pencarian (scope `Content::search()`) memakai `whereFullText` di MySQL dan fallback `LIKE` di SQLite.

---

## 2. Skema Tabel Aplikasi

### A. `contents`
Tabel tunggal untuk seluruh artikel, siaran pers, dokumen, regulasi, profil organisasi, kampanye, banner, statistik, dan kontak.

| Kolom | Tipe | Null | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | bigint unsigned | Tidak | Primary key. |
| `title` | varchar(255) | Tidak | Judul. |
| `slug` | varchar(255) | Tidak | Unik, dipakai di URL (`SlugService`). |
| `body` | longtext | Ya | HTML dari Quill, **disanitasi saat disimpan** (`HtmlSanitizer::forStorage`). |
| `tags` | varchar(255) | Ya | Tag biasa atau metadata terstruktur per kategori (lihat [domain-rules.md](domain-rules.md#c-format-khusus-kolom-tags)). |
| `status` | varchar | Tidak | `ContentStatus`: `draft` (default), `published`, `archived`. |
| `image_url` | varchar(255) | Ya | Path gambar publik (`/storage/uploads/...`), path dokumen privat (`documents/<uuid>.pdf`), atau URL eksternal. |
| `publish_date` | date | Ya | Tanggal terbit. |
| `category` | varchar | Tidak | `ContentCategory` (22 nilai). |
| `views` | integer unsigned | Tidak | Jumlah tayang/unduh (default 0). Ditambah lewat buffer Redis (`ViewCounter`) dan ditulis oleh `walhi:flush-views`. |
| `is_promoted` | boolean | Tidak | Sorotan beranda (default `false`). |
| `author` | varchar(255) | Ya | Penulis / unit kerja. |
| `created_at`, `updated_at` | timestamp | Ya | |
| `deleted_at` | timestamp | Ya | Soft delete. |

**Indeks:** `UNIQUE(slug)`, `(category, status)`, `(category, status, publish_date)`, `(is_promoted, status, publish_date)`, FULLTEXT `(title, body)` (MySQL).

---

### B. `donations`

| Kolom | Tipe | Null | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | bigint unsigned | Tidak | Primary key. |
| `order_id` | varchar | Tidak | Unik, dibuat server (`DonationService::generateOrderId`). |
| `donor_name` | varchar | Tidak | Diganti `Donatur Anonim` setelah masa retensi. |
| `donor_email` | varchar | Tidak | Dianonimkan menjadi `anonymized+<id>@...` setelah masa retensi. |
| `donor_phone` | varchar | Tidak | Diganti `-` setelah masa retensi. |
| `amount` | integer | Tidak | Nominal Rupiah. Wajib sama dengan `gross_amount` Midtrans. |
| `status` | varchar | Tidak | `DonationStatus`: `pending` (default), `success`, `failed`, `expired`. |
| `snap_token` | varchar | Ya | Token Midtrans Snap. |
| `campaign_id` | bigint unsigned | Ya | FK ke `contents.id`, `ON DELETE SET NULL`. |
| `payment_type` | varchar | Ya | Metode bayar dari Midtrans (`qris`, `bank_transfer`, ...). |
| `created_at`, `updated_at`, `deleted_at` | timestamp | Ya | Soft delete. |

**Indeks:** `UNIQUE(order_id)`, `(status, created_at)`, `(status, donor_email)`.

---

### C. `comments`

| Kolom | Tipe | Null | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | bigint unsigned | Tidak | Primary key. |
| `content_id` | bigint unsigned | Tidak | FK ke `contents.id`, `ON DELETE CASCADE`. |
| `parent_id` | bigint unsigned | Ya | FK ke `comments.id` (balasan 1 level), `ON DELETE CASCADE`. |
| `author_name` | varchar | Tidak | |
| `author_email` | varchar | Tidak | Dianonimkan setelah masa retensi. |
| `body` | text | Tidak | Teks polos, di-escape Blade `{{ }}`. |
| `status` | enum | Tidak | `pending` (default), `approved`, `spam`. |
| `created_at`, `updated_at`, `deleted_at` | timestamp | Ya | Soft delete. |

**Indeks:** `(content_id, status, parent_id)`, `(parent_id, status, created_at)`.

---

### D. `subscribers`

| Kolom | Tipe | Null | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | bigint unsigned | Tidak | Primary key. |
| `email` | varchar | Tidak | Unik. |
| `token` | varchar | Ya | Unik. Dipakai untuk tautan berhenti berlangganan. |
| `is_active` | boolean | Tidak | Default `true`. |
| `confirmed_at` | timestamp | Ya | Diisi saat tautan konfirmasi (*double opt-in*) diklik. Ekspor CSV hanya memuat yang terkonfirmasi. |
| `created_at`, `updated_at`, `deleted_at` | timestamp | Ya | Soft delete. |

Subscriber yang tidak mengonfirmasi dalam `PRIVACY_UNCONFIRMED_SUBSCRIBER_DAYS` (default 30) dihapus permanen oleh `walhi:prune-personal-data`.

---

### E. `users`

| Kolom | Tipe | Null | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | bigint unsigned | Tidak | Primary key. |
| `name` | varchar | Tidak | |
| `email` | varchar | Tidak | Unik, dipakai untuk login. |
| `email_verified_at` | timestamp | Ya | Area admin memerlukan email terverifikasi. |
| `password` | varchar | Tidak | Hash bcrypt (`BCRYPT_ROUNDS`). |
| `role` | varchar | Tidak | `UserRole`: `admin`, `editor`, `subscriber` (default). |
| `two_factor_secret` | text | Ya | Secret TOTP, **cast `encrypted`** (bergantung pada `APP_KEY`). |
| `two_factor_recovery_codes` | text | Ya | Daftar recovery code yang belum dipakai, cast `encrypted:array`. Kode yang dipakai langsung dihapus. |
| `two_factor_confirmed_at` | timestamp | Ya | 2FA dianggap aktif bila terisi. |
| `remember_token` | varchar(100) | Ya | |
| `created_at`, `updated_at` | timestamp | Ya | |

> ⚠️ Mengganti `APP_KEY` membuat `two_factor_secret` tidak bisa didekripsi. Semua pengguna harus di-reset dengan `walhi:reset-2fa`.

---

### F. `audit_logs`

| Kolom | Tipe | Null | Keterangan |
| :--- | :--- | :---: | :--- |
| `id` | bigint unsigned | Tidak | Primary key. |
| `user_id` | bigint unsigned | Ya | FK ke `users.id`, `ON DELETE SET NULL`. |
| `user_email` | varchar | Ya | Salinan email saat aksi terjadi (tetap ada walau user dihapus). |
| `action` | varchar(100) | Tidak | Misal `CONTENT_UPDATE` (daftar di [domain-rules.md](domain-rules.md#8-domain-jejak-audit-auditlogservice)). |
| `model` | varchar(100) | Tidak | Nama model, atau `System`. |
| `model_id` | varchar(64) | Ya | |
| `ip` | varchar(45) | Ya | IPv4/IPv6. |
| `user_agent` | varchar(512) | Ya | |
| `details` | json | Ya | Detail tambahan (field yang berubah, jumlah data, dll.). |
| `created_at` | timestamp | Tidak | Default waktu sekarang. Tidak ada `updated_at` (catatan tidak diubah). |

**Indeks:** `(action, created_at)`, `(model, model_id)`, `(created_at)`.

---

## 3. Tabel Bawaan Laravel

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`. Di produksi, session/cache/queue berjalan di Redis, sehingga tabel `sessions`, `cache`, dan `jobs` umumnya kosong. `failed_jobs` tetap dipakai untuk job yang gagal.

---

## 4. Relasi Antar Model

```text
  ┌───────────────┐            ┌───────────────┐
  │     User      │──1:N──────►│   AuditLog    │
  └───────────────┘            └───────────────┘
          │ (mengelola lewat Policy)
          ▼
  ┌───────────────┐
  │    Content    │◄────────┐ 1:N (campaign_id)
  └───────┬───────┘         │
          │ 1:N             │
          ▼                 │
  ┌───────────────┐  ┌───────────────┐
  │    Comment    │  │   Donation    │
  └───────┬───────┘  └───────────────┘
          │ 1:N (parent_id)
          ▼
      (Balasan)
```

1. **`Content` ↔ `Comment`**: `$content->comments()` (`hasMany`), `$comment->content()` (`belongsTo`).
2. **`Comment` ↔ `Comment`**: `$comment->parent()` / `$comment->replies()` lewat `parent_id`.
3. **`Content` ↔ `Donation`**: `$content->donations()` lewat `campaign_id`, `$donation->campaign()`.
4. **`AuditLog` → `User`**: `$auditLog->user()`.

---

## 5. Aturan Migrasi

- Migrasi produksi dijalankan oleh `deploy.sh` dengan image baru **sebelum** container diganti (`php artisan migrate --force`). Migrasi harus aman dijalankan saat versi lama masih melayani (tambah kolom nullable dulu, hapus kolom di rilis berikutnya).
- **Dilarang** `migrate:fresh`, `migrate:refresh`, atau `db:seed` di produksi.
- Migrasi yang hanya berlaku di MySQL harus memeriksa driver (contoh: migrasi FULLTEXT) agar test SQLite tetap jalan.
- Migrasi data besar: kumpulkan id dulu, baru update (lihat migrasi `double_opt_in`), jangan mengubah baris sambil mem-paging kolom yang sama.
