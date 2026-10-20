# Audit Produksi WALHI Jawa Barat — 19 Oktober 2026

Basis audit: `origin/production` @ `85877b65f8b7` (sama dengan HEAD lokal saat mulai).
Cakupan: kode aplikasi, konfigurasi Docker/nginx/PHP-FPM/MySQL/Redis, skrip deploy/backup/restore, workflow GitHub Actions.
Tidak ada akses ke VPS, database produksi, atau situs live dalam audit ini; semua pernyataan runtime di bawah ditandai sebagai **belum diverifikasi**.

## A. Ringkasan Eksekutif

**Sebelum audit.** Fondasi kuat: RBAC berbasis policy + middleware, 2FA wajib untuk admin/editor, CSP ketat dengan nonce, sanitasi HTMLPurifier, webhook Midtrans terverifikasi dengan state machine terkunci, deploy migrate-before-swap dengan rollback image, backup pra-deploy wajib. 214 test lulus.

Namun ada tiga celah nyata di jalur file/dokumen:

1. Mengganti/menghapus gambar satu konten bisa **menghapus berkas yang masih dipakai konten lain** (termasuk oleh editor, yang tidak punya hak hapus).
2. Editor dapat **menautkan dokumen privat milik entri lain** (draf atau kategori khusus admin) ke entri yang dipublikasikan, sehingga dokumen itu bisa diunduh publik.
3. nginx menyajikan `/storage/documents/*` secara statis, sehingga **dokumen lama di disk publik bisa diunduh tanpa otorisasi**, termasuk dokumen draf/arsip.

**Setelah audit.** Ketiga celah tersebut ditambah tiga temuan kecil telah diperbaiki dengan regression test (13 test baru; total 227 test, 1009 assertion, semua lulus). Perintah migrasi dokumen kini punya `--dry-run`, verifikasi SHA-256, dan tidak pernah menimpa file.

**Layak deploy?** Perubahan kode layak di-review dan di-merge ke `production`. Sebelum merge, perlu: (1) review manusia, (2) CI hijau termasuk Docker build (tidak bisa dijalankan lokal karena Docker daemon mati), (3) setelah deploy, operator menjalankan langkah verifikasi di bagian D.

**Risiko utama yang tersisa:** belum ada bukti uji restore backup, salinan off-site bersifat opsional, kapasitas VPS belum diukur, Redis berbagi memori kecil untuk sesi+cache+antrean, dan gate deploy tidak menjalankan test MySQL.

## B. Temuan

| ID | Severity | Ringkas | Status |
|---|---|---|---|
| AUD-01 | Tinggi | Penghapusan berkas bersama | fixed |
| AUD-02 | Tinggi | Dokumen privat entri lain bisa ditautkan & dipublikasikan | fixed |
| AUD-03 | Tinggi | nginx menyajikan dokumen legacy di `/storage/documents/` | fixed (perlu deploy + verifikasi) |
| AUD-04 | Sedang | Migrasi dokumen legacy tanpa dry-run/checksum, bisa menimpa | fixed |
| AUD-05 | Rendah | Editor bisa melihat draf kategori sensitif di halaman publik | fixed |
| AUD-06 | Rendah | Path PDF dipakai sebagai og:image / twitter:image / JSON-LD | fixed |
| AUD-07 | Rendah | Workflow deploy bisa dipicu dari branch selain `production` | mitigated |
| AUD-08 | Sedang | Gate deploy hanya test SQLite; test MySQL/Pint bukan prasyarat | open |
| AUD-09 | Rendah | `pint --test` gagal pada 10 file lama; CI tidak menegakkannya | open |
| AUD-10 | Rendah | `npm audit`: 5 high / 2 moderate di tooling build (Tailwind 3) | open |
| AUD-11 | Sedang | Redis 200 MB berbagi sesi/cache/antrean; risiko OOM | open (ops) |
| AUD-12 | Rendah | Soft-delete konten tetap menghapus berkas lampirannya | open (keputusan desain) |
| AUD-13 | Sedang | Backup: off-site opsional, tanpa uji restore, tanpa monitoring | sebagian: checksum + `docker-restore-test.sh` + `OFFSITE_BACKUP_REQUIRED` (default off); tujuan off-site belum dipilih |

### AUD-01 — Penghapusan berkas yang masih dipakai entri lain (Tinggi)
- **Lokasi:** `app/Http/Controllers/Admin/ContentController.php` `deleteOldImage()` (dipanggil dari `update()` dan `destroy()`).
- **Bukti:** berkas dihapus hanya berdasarkan `image_url` entri itu sendiri. Entri lain yang memakai URL yang sama (salin-tempel URL gambar, atau gambar Quill di `body`) ikut kehilangan berkas. Editor dapat membuat entri dengan `image_url` = URL gambar publik entri mana pun (misalnya banner yang hanya boleh dikelola admin), lalu mengirim `remove_image=1` untuk menghapus berkas itu.
- **Dampak:** gambar/dokumen hilang permanen dari entri lain; editor melewati batas "hapus hanya admin" pada level berkas.
- **Perbaikan:** `isFileUsedElsewhere()` — berkas tidak dihapus bila entri lain (termasuk yang di-soft-delete) masih merujuknya di `image_url` atau `body`.
- **Test:** `test_removing_image_keeps_file_still_used_by_another_content`, `test_deleting_content_keeps_file_embedded_in_another_body`, `test_file_of_soft_deleted_content_is_kept_for_restore`, `test_unshared_image_is_still_removed`.

### AUD-02 — Dokumen privat entri lain dapat dipublikasikan lewat `image_url` (Tinggi)
- **Lokasi:** `StoreContentRequest` / `UpdateContentRequest` (validasi `image_url`), `DocumentDownloadController::download()`.
- **Bukti:** validasi menerima jalur lokal apa pun berawalan `/storage/`, `/documents/`, `/assets/`, dll. Endpoint unduhan memetakan jalur lokal non-`uploads/` ke `documents/<basename>`. Akibatnya editor bisa membuat entri `published` dengan `image_url` = `documents/<nama>.pdf` (atau `/assets/<nama>.pdf`) milik entri draf atau kategori khusus admin, dan dokumen itu bisa diunduh tamu. Nama file unggahan baru acak (40 karakter), tetapi nama dokumen legacy mudah ditebak (mis. `laporan-tahunan-walhi-jabar-2025.pdf`).
- **Dampak:** kebocoran dokumen draf/arsip atau khusus admin; melewati `ContentPolicy@view`.
- **Perbaikan:** `ValidatesContentLinks::privateDocumentPathRule()` menolak `image_url` ketikan yang berawalan `documents/` atau yang di-resolve endpoint ke dokumen yang ada di disk privat/publik, kecuali nilai itu sama dengan jalur entri itu sendiri (form edit mengirimnya ulang).
- **Test:** `test_editor_cannot_attach_private_document_of_another_content` (6 varian jalur), `test_editor_cannot_switch_existing_content_to_another_private_document`, `test_editing_document_content_resubmitting_its_own_path_is_allowed`.

### AUD-03 — nginx menyajikan dokumen legacy secara statis (Tinggi)
- **Lokasi:** `docker/nginx/snippets/laravel.conf`, `location ^~ /storage/`.
- **Bukti:** blok ini me-*alias* seluruh `storage/app/public`, termasuk `documents/`. Endpoint aplikasi masih punya fallback ke disk publik untuk dokumen lama, yang berarti file semacam itu memang bisa ada di sana. Siapa pun yang tahu namanya bisa mengunduhnya langsung, tanpa melihat status draf/arsip.
- **Perbaikan:** `location ^~ /storage/documents/ { return 404; }` (prefix terpanjang menang atas `/storage/`). Unduhan sah tetap berjalan lewat `/dokumen/{slug}/unduh`, yang membaca disk dari PHP, bukan via nginx. View publik sudah memakai `download_url` untuk dokumen.
- **Test:** `test_nginx_blocks_direct_access_to_public_documents_folder` (memeriksa konfigurasi). `nginx -t` **belum dijalankan** (Docker daemon tidak aktif di mesin audit); CI `docker-build` dan langkah verifikasi di bagian D wajib dijalankan.

### AUD-04 — `walhi:migrate-documents` tidak aman untuk produksi (Sedang)
- **Lokasi:** `app/Console/Commands/MigrateLegacyDocuments.php`.
- **Bukti:** `put()` menimpa file privat bernama sama; `--delete-legacy` menghapus salinan publik tanpa memverifikasi salinan privat; tidak ada mode simulasi.
- **Perbaikan:** `--dry-run`; checksum SHA-256 (streaming) sebelum dan sesudah salin; file privat yang berbeda tidak pernah ditimpa (dilaporkan sebagai konflik, exit non-zero); salinan publik dihapus hanya bila checksum cocok; tabel ringkasan; normalisasi DB juga mencakup entri yang di-soft-delete.
- **Test:** `test_migrate_documents_dry_run_changes_nothing`, `test_migrate_documents_never_overwrites_a_different_private_file`, `test_migrate_documents_removes_legacy_copy_only_when_identical`, plus test lama `test_legacy_migration_command_moves_files_and_updates_database`.

### AUD-05 — Pratinjau draf kategori sensitif oleh editor (Rendah)
- **Lokasi:** `PublicContentController::show()`.
- **Bukti:** cek manual `canManageContent()` mengizinkan editor melihat draf `kontak`/`donasi`/`kampanye-darurat`, padahal `ContentPolicy@view` membatasinya ke admin.
- **Perbaikan:** memakai `Gate::denies('view', $item)` → 404.
- **Test:** `test_editor_cannot_preview_unpublished_sensitive_content`.

### AUD-06 — Dokumen dijadikan gambar berbagi (Rendah)
- **Lokasi:** `PublicContentController::show()`, `resources/views/content-detail.blade.php`.
- **Bukti:** `og:image`/`twitter:image`/JSON-LD berisi `/storage/documents/<file>.pdf` untuk entri dokumen (pratinjau media sosial rusak; jalur internal terpapar).
- **Perbaikan:** dokumen tidak pernah dipakai sebagai gambar berbagi; logo dipakai sebagai cadangan.
- **Test:** `test_document_path_is_not_used_as_share_image`.

### AUD-07 — Workflow deploy dari branch lain (Rendah)
- **Lokasi:** `.github/workflows/deploy.yml` (`workflow_dispatch` tanpa batasan ref).
- **Perbaikan:** job gate punya `if: github.ref == 'refs/heads/production'`; job build dan deploy ikut terlewati.
- **Rekomendasi pemilik:** di Settings → Environments → `production`, batasi *deployment branches* ke `production` dan aktifkan *required reviewers*.

### AUD-08 — Gate deploy lebih lemah dari CI (Sedang, open)
`deploy.yml` hanya menjalankan test SQLite + `composer audit`. Test MySQL 8, build frontend, dan secret scan berjalan di workflow terpisah secara paralel dan **tidak memblokir deploy**. Rekomendasi: tambahkan job MySQL (salin dari `ci.yml`) sebagai `needs` untuk `build-image`, atau wajibkan status check lewat branch protection pada `production`. Tidak diubah tanpa persetujuan pemilik.

### AUD-09 — Format kode (Rendah, open)
`vendor/bin/pint --test` gagal pada 10 file yang tidak disentuh audit ini (mis. `routes/web.php`, `ContentSeeder.php`). Dua file yang disentuh (`ContentController`, `PublicContentController`) sudah dirapikan. Rekomendasi: PR terpisah `vendor/bin/pint` + langkah `pint --test` di CI.

### AUD-10 — Kerentanan npm di tooling build (Rendah, open)
`npm audit --omit=dev`: 0 kerentanan. `npm audit` penuh: 5 high, 2 moderate di `postcss-selector-parser`/`postcss-nested` (via Tailwind 3). Hanya dipakai saat build; tidak masuk image runtime. Perbaikan butuh upgrade Tailwind 4 (breaking; PR Dependabot sudah ada) — perlu pengujian visual terpisah.

### AUD-11 — Kapasitas Redis (Sedang, open)
Redis: `maxmemory 200mb`, `volatile-lru`, AOF aktif, limit container 256 MB. Sesi, cache, rate limiter, dan antrean berbagi instans ini. Kunci tanpa TTL (job antrean, cache `forever`) tidak bisa di-evict, sehingga saat penuh Redis menolak tulis (login dan antrean gagal). Rewrite AOF (fork) juga bisa melewati limit cgroup 256 MB sehingga container di-OOM-kill. Rekomendasi: pantau `INFO memory`; naikkan limit container ke ±384 MB atau turunkan `maxmemory`.

### AUD-12 — Soft-delete menghapus berkas (Rendah, open)
`destroy()` melakukan soft-delete pada baris, tetapi menghapus berkas lampiran secara permanen (kecuali dipakai entri lain, sejak AUD-01). Tidak ada UI restore, jadi dampaknya kecil. Bila restore akan disediakan, berkas sebaiknya baru dihapus saat *force delete*.

### AUD-13 — Backup & pemulihan (Sedang, open — operasional)
**Update:** setiap backup kini punya `<berkas>.sha256`; `docker-restore-test.sh` menguji restore di container sementara tanpa jaringan dan memeriksa kelengkapan dokumen; `OFFSITE_BACKUP_REQUIRED` tersedia tetapi **belum diaktifkan** sampai tujuan off-site (R2/S3) dikonfigurasi dan uji restore lulus. Uji pertama pada backup produksi akan melaporkan 12 dokumen yang dirujuk database tetapi tidak ada (lihat temuan dokumen hilang).

Yang sudah baik: `mysqldump --single-transaction` dengan verifikasi `Dump completed`; arsip `storage/app` (dokumen privat + unggahan); `umask 077`; alert webhook saat gagal; retensi 14/30 hari; backup pra-deploy wajib (deploy batal bila backup gagal); kredensial tidak dicetak.
Kekurangan:
- Off-site hanya **peringatan** bila `OFFSITE_BACKUP_TARGET` kosong. Backup bisa "berhasil" padahal hanya ada di VPS yang sama.
- Tidak ada uji restore terjadwal, checksum/manifest backup, atau pemantauan bahwa cron backup benar-benar berjalan.
- `docker-restore.sh` tidak mengembalikan skema ke versi image yang sedang berjalan. Restore backup pra-deploy setelah migrasi baru harus dibarengi rollback image (lihat checklist).

## C. Hasil Pengujian

Lingkungan: macOS, PHP 8.5.5 CLI, Composer 2, Node 20, SQLite in-memory (phpunit.xml). Docker daemon **tidak aktif**.

| Perintah | Sebelum | Sesudah |
|---|---|---|
| `php artisan test` | 214 lulus / 0 gagal (965 assertion) | **227 lulus / 0 gagal / 0 dilewati (1009 assertion)** |
| `php artisan test --filter=StorageIntegrityAuditTest` (sebelum fix) | 10 gagal / 3 lulus (mereproduksi temuan) | 13 lulus |
| `vendor/bin/pint --test` | gagal, 13 file | gagal, 10 file (semuanya file yang tidak disentuh, AUD-09) |
| `composer audit --abandoned=ignore` | 0 advisory | — (dependency tidak berubah) |
| `npm audit --omit=dev --audit-level=high` | 0 kerentanan | — |
| `npm run build` | sukses | sukses |
| `php -l` pada file PHP yang diubah | — | tanpa error |
| YAML `deploy.yml` (parse) | — | valid |
| `nginx -t`, `docker build` | **tidak dijalankan** (Docker mati) | **tidak dijalankan** |
| Test MySQL 8 | **tidak dijalankan lokal**; berjalan di CI `ci.yml` | perlu dilihat di CI |
| Browser/UI, aksesibilitas, mobile, load test | **tidak dijalankan** (tanpa izin/akses ke situs live) | — |

## D. VPS & Operasional — tindakan operator

Semua hasil runtime di bawah **belum diverifikasi**; jalankan di VPS (`/var/www/walhi_app`).

**Setelah deploy perubahan ini:**
```bash
# 1. nginx memblokir dokumen statis (harus 404)
curl -sI https://walhijabar.or.id/storage/documents/laporan-tahunan-walhi-jabar-2025.pdf | head -1
# 2. unduhan berotorisasi tetap jalan (harus 200 + attachment)
curl -sI https://walhijabar.or.id/dokumen/<slug-laporan-published>/unduh | grep -i 'HTTP\|content-disposition'
# 3. inventaris dokumen legacy di disk publik (tidak mengubah apa pun)
docker compose -f docker-compose.prod.yml exec app php artisan walhi:migrate-documents --dry-run
```
Jalankan migrasi sungguhan (`walhi:migrate-documents`, lalu `--delete-legacy`) hanya **setelah** backup baru dan bila dry-run tanpa konflik.

**Kesehatan & kapasitas (baca saja):**
```bash
docker compose -f docker-compose.prod.yml ps
docker stats --no-stream
free -m; df -h; docker system df
docker exec walhi_prod_redis sh -c 'REDISCLI_AUTH=$REDIS_PASSWORD redis-cli INFO memory' | grep -E 'used_memory_human|maxmemory_human|evicted_keys'
docker exec walhi_prod_app sh -c 'SCRIPT_NAME=/status SCRIPT_FILENAME=/status REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000' | grep -E 'max children reached|active processes'
cat /etc/docker/daemon.json   # rotasi log json-file (setup-vps.sh: 20m x 5)
sudo ufw status; sudo sshd -T | grep -E 'passwordauthentication|permitrootlogin'
grep -E '^(APP_DEBUG|APP_ENV|SESSION_SECURE_COOKIE|OFFSITE_BACKUP_TARGET|BACKUP_ALERT_WEBHOOK)=' .env
crontab -l | grep docker-backup
```
Catatan konfigurasi (dari file, bukan pengukuran): total limit container ±5,8 GB dari 8 GB. PHP-FPM 18 worker × `memory_limit` 128 MB = 2,3 GB secara teoritis, melebihi limit container app 2 GB pada kasus terburuk; normalnya 40–60 MB/worker. Kapasitas nyata hanya bisa diketahui lewat load test terkontrol di **staging**.

**Checklist uji restore (staging, jangan di produksi):**
1. Salin backup terbaru dari **off-site** (bukan dari VPS produksi) ke server staging.
2. Pastikan staging memakai image dengan tag commit yang sama dengan saat backup (`.deploy/last_good`).
3. `gzip -t` kedua arsip; jalankan `./docker-restore.sh <mysql.sql.gz> <files.tar.gz>` di staging.
4. Verifikasi: `php artisan migrate:status` tanpa pending; jumlah baris `contents`, `donations`, `users` sesuai catatan; beberapa dokumen privat dapat diunduh via `/dokumen/{slug}/unduh`; gambar publik tampil; login admin + 2FA berhasil.
5. Catat durasi restore (RTO) dan umur backup (RPO). Ulangi tiap kuartal.

**Rollback:** `deploy.sh` mengembalikan *image* dan commit, tetapi **tidak** membatalkan migrasi. Bila skema harus dikembalikan: rollback image ke tag sebelum migrasi, lalu restore DB dari backup `walhi_predeploy_*` yang dibuat tepat sebelum deploy tersebut.

**Monitoring yang belum ada:** uptime check eksternal, alert 5xx/disk/memori, dan heartbeat cron backup. `/up` hanya memeriksa aplikasi boot, tidak memeriksa DB/Redis.

## E. File yang Diubah

| File | Alasan |
|---|---|
| `app/Http/Controllers/Admin/ContentController.php` | AUD-01: guard berkas bersama (`isFileUsedElsewhere`); Pint merapikan spasi lama |
| `app/Http/Requests/Admin/Concerns/ValidatesContentLinks.php` | AUD-02: `privateDocumentPathRule()` |
| `app/Http/Requests/Admin/StoreContentRequest.php`, `UpdateContentRequest.php` | AUD-02: memasang rule di `image_url` |
| `docker/nginx/snippets/laravel.conf` | AUD-03: blok `/storage/documents/` |
| `app/Console/Commands/MigrateLegacyDocuments.php` | AUD-04: dry-run, checksum, tanpa overwrite |
| `app/Http/Controllers/PublicContentController.php` | AUD-05 (policy), AUD-06 (gambar berbagi); Pint menghapus import tak terpakai |
| `resources/views/content-detail.blade.php` | AUD-06 |
| `.github/workflows/deploy.yml` | AUD-07: guard ref `production` |
| `tests/Feature/StorageIntegrityAuditTest.php` (baru) | 13 regression test |
| `AGENTS.md`, `CONTRIBUTING.md`, `docs/testing.md`, `docs/domain-rules.md` | jumlah test, perilaku migrasi & referensi berkas |
| `docs/audits/2026-10-19-production-audit.md` (baru) | laporan ini |

## F. GitHub

- Branch kerja: `claude/audit-walhi-jawa-barat-01b77e` (worktree), berbasis `origin/production` @ `85877b65f8b7`.
- **Belum ada commit, push, PR, atau deploy.** Identitas Git yang terkonfigurasi (`irsalshydiq <ichalprov@gmail.com>`, dari `~/.gitconfig`) **tidak sama** dengan identitas yang diminta (`LM. Irsal Shydiq <144502106+Irs622@users.noreply.github.com>`), sehingga sesuai aturan, commit dihentikan sampai identitas dikonfirmasi.
- Status CI/CD untuk perubahan ini: belum ada (belum di-push).

## G. Risiko yang Tersisa

- Konfigurasi nginx baru belum divalidasi dengan `nginx -t`/Docker build secara lokal.
- Isi aktual disk publik `documents/` di VPS tidak diketahui; jalankan `--dry-run` untuk inventaris.
- Baris `image_url` produksi dengan format di luar yang terlihat di seed data tidak bisa diperiksa tanpa akses DB.
- Belum ada bukti restore, off-site, load test, uji UI/aksesibilitas/mobile, atau monitoring (AUD-11, AUD-13).
- AUD-08/09/10 masih terbuka dan butuh keputusan pemilik.
