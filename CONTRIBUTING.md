# 🤝 Panduan Kontribusi & Aturan Kolaborasi (Contributing Guidelines)

Terima kasih telah tertarik untuk berkontribusi pada proyek pengembangan website resmi **WALHI Jawa Barat**! Dokumen ini berisi panduan alur kerja (*workflow*), standar kode, dan **ketentuan serta aturan resmi Pull Request (PR)** bagi seluruh anggota tim dan kontributor terbuka.

---

## 📋 Daftar Isi
1. [Alur Kerja Git & Percabangan (Branching Workflow)](#-alur-kerja-git--percabangan)
2. [Konvensi Pesan Komit (Commit Conventions)](#-konvensi-pesan-komit)
3. [Standar Kode (Coding Standards)](#-standar-kode)
4. [📜 Ketentuan & Aturan Pull Request (PR Rules)](#-ketentuan--aturan-pull-request)
5. [Langkah Membuat Pull Request (Step-by-Step)](#-langkah-membuat-pull-request)
6. [Menjalankan Pengujian (Testing)](#-menjalankan-pengujian)
7. [Pelaporan Masalah (Reporting Issues)](#-pelaporan-masalah-issues)

---

## 🌿 Alur Kerja Git & Percabangan

| Branch | Peran |
| :--- | :--- |
| **`production`** | **Patokan utama (source of truth).** Isinya selalu kode yang sedang live di server WALHI Jawa Barat. Setiap merge ke branch ini **otomatis dideploy** ke VPS lewat GitHub Actions (`deploy.yml`). Semua PR diarahkan ke sini. |
| **`main`** | **Cermin (mirror) dari `production`.** Tidak menerima commit atau PR sendiri. Workflow `sync-main.yml` mem-*fast-forward* `main` ke `production` setiap kali `production` berubah. |
| `prod` | **Usang (deprecated).** Branch lama dari sebelum audit Oktober 2026 dan sudah jauh tertinggal. Jangan dipakai sebagai dasar branch atau target PR; dibiarkan hanya sebagai arsip. |

Alur singkat:

```text
branch kerja (feat/…, fix/…, docs/…)  ──PR──►  production  ──(otomatis)──►  deploy ke VPS
                                                    │
                                                    └──(otomatis)──►  main (fast-forward)
```

Karena merge ke `production` langsung tayang, pastikan CI hijau dan perubahan sudah diuji lokal sebelum merge. Untuk perubahan berisiko (migrasi besar, konfigurasi server), koordinasikan waktu merge dengan pengelola server.

### Format Penamaan Branch:
- `feat/nama-fitur` : Untuk penambahan fitur baru (contoh: `feat/payment-gateway-midtrans`).
- `fix/nama-perbaikan` : Untuk perbaikan bug atau error (contoh: `fix/tentang-kami-tabs`).
- `refactor/nama-refactor` : Untuk restrukturisasi kode tanpa mengubah fungsionalitas (contoh: `refactor/audit-log-service`).
- `docs/nama-dokumen` : Untuk pembaruan dokumentasi (contoh: `docs/api-documentation`).
- `style/nama-styling` : Untuk penyesuaian tampilan CSS/Tailwind (contoh: `style/neo-brutalist-card`).
- `hotfix/nama-masalah` : Untuk perbaikan mendesak di production, dibuat dari `production`.

---

## 💬 Konvensi Pesan Komit

Kami menggunakan standar [Conventional Commits](https://www.conventionalcommits.org/) agar riwayat perubahan (*git history*) rapi dan mudah ditelusuri.

Format:
```
<type>(<scope optional>): <deskripsi singkat jelas>
```

Contoh:
- `feat(donasi): tambahkan filter riwayat donasi berdasarkan tanggal`
- `fix(tabs): perbaiki active state pada tab navigasi tentang kami`
- `docs(readme): tambahkan panduan instalasi docker`
- `refactor(auth): perbarui middleware pemeriksaan role admin`

Daftar `type`:
- `feat` : Fitur baru untuk pengguna.
- `fix` : Perbaikan bug.
- `docs` : Perubahan dokumentasi saja.
- `style` : Formatting kode, whitespace, titik koma (tidak mengubah logika).
- `refactor` : Refactoring kode tanpa menambah fitur baru atau memperbaiki bug.
- `test` : Menambahkan atau memperbarui unit/feature tests.
- `chore` : Pembaruan build scripts, dependencies, konfigurasi CI/CD.

---

## 🎨 Standar Kode

### 1. PHP (Laravel)
- Mengikuti standar **PSR-12** (Coding Style Guide).
- Gunakan *Type Hinting* dan *Return Types* pada setiap method controller dan service.
- Logika bisnis yang kompleks dipisahkan ke dalam **Service Classes** (misal `app/Services/AuditLogService.php`), bukan menumpuk di Controller.
- Gunakan **Form Request Validation** atau `$request->validate([...])` untuk semua input pengguna.

### 2. Blade & Frontend
- Gunakan **Tailwind CSS** untuk utility styling.
- Pertahankan tema desain **Neo-Brutalist WALHI** (border hitam tegas `#1D1D1D`, bayangan blok `shadow-[4px_4px_0px_0px_#1D1D1D]`, palet warna hijau hutan `#256D4A`, krem `#F4F1EA`, dan oranye terakota `#D95C3F`).
- Hindari penggunaan CDN eksternal jika memungkinkan; gunakan **native inline SVG** untuk ikon agar tidak ada kegagalan rendering saat koneksi offline/lambat.
- **Content Security Policy ketat:** jangan menulis `onclick=`, `onsubmit=`, atau handler inline lain, dan jangan menulis `<script>` tanpa `nonce="{{ Vite::cspNonce() }}"`. Pakai atribut `data-on-click` dkk. (lihat [docs/frontend.md](docs/frontend.md)).

### 3. Keamanan
- Ikuti [.agents/rules/secure_coding.md](.agents/rules/secure_coding.md) (berlaku untuk manusia maupun AI agent).

---

## 📜 Ketentuan & Aturan Pull Request

Setiap Pull Request yang diajukan ke repository ini **wajib mematuhi ketentuan berikut** agar dapat ditinjau dan digabungkan:

### 1. Prinsip 1 PR = 1 Topik (*Single Responsibility*)
- **Dilarang membuat "Mega PR"**: Jangan menggabungkan banyak fitur yang tidak berkaitan atau mencampur *refactoring* besar dengan *bug fix* dalam satu PR.
- PR yang fokus dan berukuran kecil/sedang (< 400 baris perubahan) akan ditinjau dan di-merge jauh lebih cepat.

### 2. Bebas Konflik (*No Merge Conflicts*)
- Branch pengaju PR **wajib up-to-date** dengan branch `production` terbaru sebelum mengajukan review:
  ```bash
  git checkout feat/nama-fitur
  git fetch origin
  git merge origin/production
  ```
- Jika ada konflik, selesaikan (*resolve*) di branch Anda terlebih dahulu.

### 3. Bersih dari File Sensitif & Kredensial (*Security Policy*)
- **DILARANG KERAS** menyertakan file `.env`, kredensial database, API keys, password pribadi, atau file backup (`.sql`, `.sqlite`) ke dalam commit / PR.
- Pastikan file `.gitignore` dihormati.

### 4. Kelengkapan Informasi & Template PR
- **Judul PR Jelas**: Mengikuti format `[Tipe]: Ringkasan singkat` (contoh: `Fix: Perbaikan tab Tentang Kami dan icon inline SVG`).
- **Deskripsi Lengkap**: Wajib mengisi formulir template PR (ringkasan perubahan, alasan/konteks, dan langkah pengujian).
- **Bukti Visual (Screenshot / Video)**: Wajib melampirkan screenshot atau rekaman layar untuk setiap perubahan antarmuka pengguna (UI/UX).
- **Tautkan Issue Terkait**: Gunakan kata kunci GitHub seperti `Fixes #12` atau `Closes #15` jika PR menyelesaikan isu yang terdaftar.

### 5. Aturan Review & Approval (Persetujuan)
- **Minimal 1 Approval**: Setiap PR wajib mendapatkan persetujuan (*approval*) dari minimal 1 maintainer / lead developer atau rekan tim sebelum di-merge.
- **Dilarang Self-Merge**: Kontributor dilarang melakukan merge pada PR miliknya sendiri tanpa persetujuan dari tim peninjau (kecuali hotfix kritis yang telah dikoordinasikan).
- **Review Otomatis `walhijabar_reviewbot`**: Setiap PR diperiksa otomatis terhadap aturan keamanan & arsitektur proyek. Temuan *blocker* harus diperbaiki sebelum merge (lihat [docs/reviewbot.md](docs/reviewbot.md)).
- **Responsif terhadap Masukan**: Pembuat PR diharapkan menanggapi dan merevisi catatan/komentar dari reviewer secara berkala.

### 6. Strategi Penggabungan (Merge Strategy)
- Gunakan metode **Squash and Merge** agar riwayat `production` tetap bersih dan linear (satu commit per PR). Judul commit squash = judul PR beserta nomornya.
- Jangan pernah melakukan *force push* ke `production` atau `main`.
- Selalu **hapus branch fitur** setelah PR berhasil di-merge (*Delete branch after merge*).

---

## 🚀 Langkah Membuat Pull Request

1. **Fork atau Clone Repository:**
   ```bash
   git clone https://github.com/walhijabarhub/WalhiJBRWeb.git
   cd WalhiJBRWeb
   ```

2. **Buat Branch Baru dari `production`:**
   ```bash
   git checkout production
   git pull origin production
   git checkout -b feat/nama-fitur-kamu
   ```

3. **Lakukan Perubahan & Uji di Lingkungan Lokal:**
   ```bash
   docker compose up -d --build
   ```

4. **Komit Perubahan Anda (Sesuai Konvensi):**
   ```bash
   git add .
   git commit -m "feat(konten): tambahkan filter kategori artikel"
   ```

5. **Push ke GitHub:**
   ```bash
   git push -u origin feat/nama-fitur-kamu
   ```

6. **Buka Pull Request (PR):**
   - Buka repository di GitHub dan klik **Compare & pull request**.
   - Pastikan **base branch = `production`**.
   - Isi deskripsi sesuai template PR.
   - Tetapkan reviewer dari tim Anda.
   - Tunggu seluruh check CI (`CI`, `Security Audit`) hijau sebelum merge.

---

## 🧪 Menjalankan Pengujian

Sebelum mengajukan PR, pastikan semua tes lokal berjalan tanpa error:

```bash
# Menjalankan PHPUnit Feature & Unit Tests (298 test)
php artisan test

# Cek format kode PHP (Laravel Pint)
vendor/bin/pint --test

# Build aset frontend (memastikan Vite & Tailwind tidak error)
npm run build

# Atau jalankan test di dalam container Docker
docker compose exec app php artisan test
```

CI menjalankan test di SQLite **dan** MySQL 8.0. Detail lihat [docs/testing.md](docs/testing.md).

---

## 🐞 Pelaporan Masalah (Issues)

Jika Anda menemukan bug, error, atau memiliki usulan fitur baru:
1. Periksa tab **Issues** untuk memastikan isu serupa belum pernah dilaporkan.
2. Buat Issue baru dengan memilih template yang sesuai (*Bug Report* atau *Feature Request*).
3. Sertakan langkah-langkah reproduksi error (*steps to reproduce*), screenshot/log, dan spesifikasi lingkungan (OS, versi Docker/PHP).
