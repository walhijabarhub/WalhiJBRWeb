# walhijabar_reviewbot

Bot review otomatis untuk setiap pull request. Bot ini memeriksa baris yang **ditambahkan** PR terhadap aturan proyek: [AGENTS.md](../AGENTS.md), [.agents/rules/secure_coding.md](../.agents/rules/secure_coding.md), dan [domain-rules.md](domain-rules.md).

- Workflow: [`.github/workflows/reviewbot.yml`](../.github/workflows/reviewbot.yml)
- Aturan: [`.github/reviewbot/walhijabar_reviewbot.py`](../.github/reviewbot/walhijabar_reviewbot.py) (Python standar, tanpa dependency, tanpa layanan eksternal)

## Cara kerja

1. Berjalan pada event `pull_request` (dibuka, push baru, dibuka ulang, judul/base diubah). Bot tidak pernah memakai `pull_request_target`, jadi kode PR hanya dibaca.
2. Aturan diambil dari **branch base**. PR tidak bisa melemahkan bot yang me-review-nya. Satu-satunya pengecualian adalah PR yang pertama kali menambahkan bot.
3. Hasil review muncul di tiga tempat:
   - **anotasi** di baris terkait (tab *Files changed*);
   - **satu komentar PR**, diperbarui di setiap push (tidak menumpuk);
   - **ringkasan job** di tab Actions.
4. Status check `walhijabar_reviewbot` **gagal bila ada blocker**.

## Tingkat temuan

| Tingkat | Arti | Contoh |
|---|---|---|
| 🛑 Blocker | Melanggar aturan keamanan/arsitektur; check gagal | `{!! $item->body !!}`, `onclick=`, `<script>` tanpa nonce, `env()` di luar `config/`, dokumen ke disk `public`, `$request->all()` ke `create()`, open redirect, pemetaan status Midtrans manual, `'unsafe-inline'` di `script-src`, secret/`.env` ter-commit, `pull_request_target`, PR ke `main`/`prod` |
| ⚠️ Peringatan | Perlu ditinjau manusia | raw SQL dengan variabel, cache objek Eloquent, aksi admin tanpa `AuditLogService::log()`, migration drop/rename kolom, action tidak di-pin SHA, `innerHTML`, sisa `dd()`, kode berubah tanpa test |
| 💡 Catatan | Pengingat konteks | file infrastruktur/deploy berubah, ada migration, route tulis baru, dokumentasi perlu disinkronkan |

Bot memeriksa **pola**, bukan logika. Bot tidak menggantikan review manusia; checklist *Definition of Done* disertakan di setiap komentar.

## Menjadikan check wajib

Di **Settings → Branches → Branch protection rules** untuk `production` (perlu akses admin): aktifkan *Require status checks to pass* lalu pilih `walhijabar_reviewbot`, bersama check CI (`PHP Tests (SQLite)`, `PHP Tests (MySQL 8.0)`, `Frontend Assets Build`, `Docker Build Validation`).

## Nama "walhijabar_reviewbot" di komentar (opsional)

Tanpa konfigurasi, komentar diposting oleh `github-actions[bot]` dengan judul **🌿 walhijabar_reviewbot**. Agar akunnya sendiri bernama walhijabar_reviewbot:

1. Owner organisasi membuat GitHub App bernama `walhijabar_reviewbot` (Settings → Developer settings → GitHub Apps). Tidak perlu webhook. Izin repository: *Pull requests: Read and write*, *Contents: Read-only*.
2. Install App tersebut di repo `walhijabarhub/WalhiJBRWeb`.
3. Tambahkan dua secret repository: `REVIEWBOT_CLIENT_ID` (Client ID App) dan `REVIEWBOT_APP_PRIVATE_KEY` (isi file `.pem`).

Setelah itu workflow otomatis memakai token App. PR dari fork tidak mendapat secret; anotasi dan ringkasan job tetap berjalan.

## Menjalankan secara lokal

```bash
python3 .github/reviewbot/walhijabar_reviewbot.py --base origin/production --head HEAD --no-annotations
```

Exit code `1` berarti ada blocker.

## Menambah atau mengubah aturan

Aturan per baris ada di `line_rules()`, aturan per file di `file_rules()`, dan aturan per PR di `pr_rules()`. Sebelum merge, uji perubahan aturan terhadap riwayat agar tidak menambah false positive:

```bash
for c in $(git rev-list --first-parent -25 origin/production); do
  python3 .github/reviewbot/walhijabar_reviewbot.py --base "$c^" --head "$c" --no-annotations 2>&1 >/dev/null
done
```

Perubahan aturan baru berlaku untuk PR lain setelah di-merge ke `production` (aturan dibaca dari base).
