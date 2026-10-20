# 🛠️ Runbook Operasional Produksi

Panduan langkah demi langkah untuk pekerjaan rutin dan penanganan masalah di server produksi WALHI Jawa Barat. Setup awal server ada di [DEPLOYMENT.md](../DEPLOYMENT.md), dan arti setiap variabel `.env` ada di [configuration.md](configuration.md).

| Item | Nilai |
| :--- | :--- |
| Domain | `walhijabar.or.id` |
| Direktori aplikasi | `/var/www/walhi_app` |
| Branch yang dideploy | `production` |
| Compose | `docker-compose.prod.yml` (**selalu** pakai `-f docker-compose.prod.yml`) |
| Backup | `/var/backups/walhi` (cron harian 02:00, log `/var/log/walhi_backup.log`) |
| Image | `ghcr.io/walhijabarhub/walhijbrweb:<12 karakter commit>` |
| Login admin | `https://walhijabar.or.id/portal-jabar` |

Semua perintah di bawah dijalankan **di server** (setelah `ssh` ke VPS), dari `/var/www/walhi_app`, kecuali disebutkan lain. Perintah yang diawali `!` di Claude Code atau dijalankan di terminal laptop **tidak** berjalan di server.

```bash
cd /var/www/walhi_app
alias dc='docker compose -f docker-compose.prod.yml'   # singkatan untuk sesi ini
```

---

## 1. Cek Kesehatan

```bash
dc ps                                   # semua container harus running/healthy
curl -fsS https://www.walhijabar.or.id/up   # 200 = aplikasi, database & Redis sehat; 500 = salah satunya bermasalah (detail di log)
dc logs --tail=100 app                  # log PHP-FPM / Laravel
dc exec app tail -n 100 storage/logs/laravel-$(date +%F).log
dc exec app php artisan schedule:list   # jadwal reconcile / flush-views / prune
dc exec app php artisan queue:failed    # job email yang gagal
df -h / && docker system df             # ruang disk
cat .deploy/last_good                   # image, tag & commit terakhir yang sehat
```

---

## 2. Deploy

### Normal (otomatis)
Merge PR ke `production` sudah cukup. GitHub Actions (`deploy.yml`) menjalankan test, membangun image, lalu SSH ke server dan menjalankan `./deploy.sh production <commit>`. Pantau di tab **Actions** repo. Setelah itu `sync-main.yml` menyamakan `main`.

Urutan `deploy.sh`: backup → tarik image → maintenance mode → migrasi dengan image baru → ganti container → health check → selesai, atau **rollback otomatis** bila health check gagal.

### Manual dari server
Dipakai bila Actions bermasalah, atau setelah mengubah `.env`:

```bash
./deploy.sh production                 # deploy ujung origin/production
./deploy.sh production 3c0d0448d7db    # deploy commit tertentu
```
`APP_IMAGE` di `.env` sudah menunjuk GHCR, jadi image ditarik dari registry. Bila package GHCR private dan server belum login, login sementara dulu dengan token `read:packages`, atau jalankan ulang workflow **Production CI/CD Deployment** dari tab Actions (*Run workflow*).

### Rollback kode
Rollback otomatis sudah terjadi bila health check gagal. Untuk mundur secara manual ke versi sebelumnya:

```bash
cat .deploy/last_good                        # versi yang sedang jalan
git log --oneline -10 origin/production      # pilih commit sehat sebelumnya
./deploy.sh production <commit-lama>
```
Lalu buat PR *revert* di GitHub agar `production` sesuai dengan yang live. Kalau tidak, deploy berikutnya akan memasang ulang perubahan yang bermasalah.

> Migrasi tidak dibatalkan otomatis. Bila skema/data harus mundur, gunakan restore (bagian 4).

---

## 3. Maintenance Mode

```bash
dc exec app php artisan down --secret="$(openssl rand -hex 16)"   # catat secret-nya untuk akses bypass
dc exec app php artisan up
```
Maintenance disimpan di Redis, jadi berlaku untuk semua container dan bertahan saat container dibuat ulang. Bila deploy terhenti di tengah jalan dan situs tetap maintenance, jalankan `php artisan up` seperti di atas.

---

## 4. Backup & Restore

```bash
./docker-backup.sh                       # backup manual (DB + storage/app)
ls -lt /var/backups/walhi | head         # daftar backup
tail -n 50 /var/log/walhi_backup.log     # hasil cron harian

./docker-restore.sh /var/backups/walhi/walhi_prod_mysql_YYYYMMDD_HHMMSS.sql.gz \
                    /var/backups/walhi/walhi_prod_files_YYYYMMDD_HHMMSS.tar.gz
```
Restore meminta konfirmasi `restore`, mengaktifkan maintenance mode, lalu mematikannya setelah selesai.

- Snapshot sebelum setiap deploy bernama `walhi_predeploy_*`.
- Setiap backup punya checksum `<berkas>.sha256`.
- **Belum ada backup off-site** sampai `OFFSITE_BACKUP_TARGET` diisi. Bila VPS hilang, backup ikut hilang. Urutan aktivasi aman (R2/S3, lalu `OFFSITE_BACKUP_REQUIRED=true`): lihat [DEPLOYMENT.md](../DEPLOYMENT.md#mengaktifkan-backup-off-site-urutan-aman).
- Uji restore minimal sebulan sekali, tanpa menyentuh situs: `./docker-restore-test.sh <mysql.sql.gz> <files.tar.gz>` (exit `2` = ada dokumen yang dirujuk database tetapi tidak ada di backup).

---

## 5. Akun & Akses

### Membuat akun baru / mengganti password
```bash
dc exec app php artisan walhi:create-admin --name="Nama Lengkap" --email=nama@walhijabar.or.id --role=editor
```
Password diminta dua kali lewat prompt tersembunyi (minimal 12 karakter, huruf besar-kecil, angka, simbol).

> ⚠️ Perintah ini juga **memperbarui akun yang sudah ada**: nama dan role ikut ditimpa, dan `--role` default-nya `admin`. Untuk mengganti password editor, **selalu** sertakan `--name` dan `--role=editor` yang benar.

Setelah SMTP aktif, pengguna bisa memakai "Lupa password" di halaman login.

### Reset 2FA (HP hilang / tidak punya recovery code)
```bash
dc exec app php artisan walhi:reset-2fa nama@walhijabar.or.id
```
Pengguna akan diminta mendaftarkan 2FA lagi saat login berikutnya. Aksi ini tercatat di audit log (`TWO_FACTOR_RESET_CLI`). Pastikan identitas peminta sudah diverifikasi lewat kanal lain sebelum mereset.

### Mengunci akun (orang keluar / akun bocor)
Ganti password akun itu dengan password acak yang tidak disimpan siapa pun, lalu reset 2FA-nya:
```bash
dc exec app php artisan walhi:create-admin --name="Nama Lengkap" --email=nama@walhijabar.or.id --role=<role-sekarang>
dc exec app php artisan walhi:reset-2fa nama@walhijabar.or.id
```
Sesi yang sedang terbuka berakhir setelah tidak aktif selama `SESSION_LIFETIME` (120 menit). Karena 2FA juga di-reset, login baru dengan password lama tidak mungkin lagi.

### Melihat jejak audit
```bash
dc exec app tail -n 50 storage/logs/audit-$(date +%F).log
dc exec db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -u"$MYSQL_USER" "$MYSQL_DATABASE" -e "SELECT created_at,user_email,action,model,model_id FROM audit_logs ORDER BY id DESC LIMIT 30"'
```

---

## 6. Mengaktifkan Email (SMTP)

Selama `MAIL_MAILER=log`, email reset password dan konfirmasi newsletter **tidak terkirim** (hanya ditulis ke log). Pendaftar newsletter tidak akan pernah terkonfirmasi.

1. Siapkan akun pengirim, misalnya `kontak@walhijabar.or.id` di Google Workspace.
2. Untuk Google: aktifkan 2-Step Verification di akun tersebut, lalu buat **App Password** (Akun Google → Keamanan → App passwords).
3. Edit `.env` di server (**isi sendiri, jangan menempelkan password ke chat/issue**):
   ```bash
   nano .env
   # MAIL_MAILER=smtp
   # MAIL_HOST=smtp.gmail.com
   # MAIL_PORT=587
   # MAIL_ENCRYPTION=tls
   # MAIL_USERNAME=kontak@walhijabar.or.id
   # MAIL_PASSWORD=<app password 16 digit>
   # MAIL_FROM_ADDRESS=kontak@walhijabar.or.id
   ```
4. Terapkan: `./deploy.sh production`.
5. Uji: buka halaman login → "Lupa password" dengan email akun Anda, atau daftar newsletter dengan email pribadi. Bila gagal, lihat `dc logs worker` dan `dc exec app php artisan queue:failed`, lalu `dc exec app php artisan queue:retry all` setelah diperbaiki.

Agar email tidak masuk spam, tambahkan record SPF/DKIM untuk domain pengirim (di Google Workspace: Admin → Apps → Gmail → Authenticate email).

---

## 7. CAPTCHA Turnstile

1. Cloudflare Dashboard → **Turnstile** → *Add site*, domain `walhijabar.or.id`, mode *Managed*.
2. Isi `TURNSTILE_SITE_KEY` dan `TURNSTILE_SECRET_KEY` di `.env`, lalu `./deploy.sh production`.
3. Cek: form komentar & newsletter menampilkan widget. Untuk mematikan, kosongkan kedua key lalu deploy ulang.

---

## 8. Tugas Terjadwal & Antrean

| Tugas | Jadwal | Cek manual |
| :--- | :--- | :--- |
| `walhi:donations-reconcile` | tiap 15 menit | `dc exec app php artisan walhi:donations-reconcile` |
| `walhi:flush-views` | tiap 5 menit | `dc exec app php artisan walhi:flush-views` |
| `walhi:prune-personal-data` | tanggal 1, 03:00 | `dc exec app php artisan walhi:prune-personal-data --dry-run` |

Semuanya dijalankan container `scheduler`. Email dikirim container `worker`. Bila salah satu mati: `dc up -d scheduler worker`.

---

## 9. Cache

```bash
dc exec app php artisan cache:clear      # isi cache aplikasi (beranda, sitemap, rate limit)
dc exec app php artisan optimize:clear   # + config/route/view cache
dc exec app php artisan optimize         # bangun ulang cache config/route/view
```
Cache konten sudah dibersihkan otomatis setiap kali konten disimpan atau dihapus. Biasanya tidak perlu dibersihkan manual.

---

## 10. Masalah yang Pernah Terjadi & Solusinya

| Gejala | Penyebab | Solusi |
| :--- | :--- | :--- |
| Beranda / sitemap **500** setelah deploy | Objek Eloquent di cache tidak bisa di-unserialize di Laravel 13 | Sudah diperbaiki (`ContentCache`, key `:v2`). Bila muncul lagi untuk key lain: `cache:clear`, lalu perbaiki kodenya agar menyimpan array. |
| **502 Bad Gateway** setelah container `app` dibuat ulang | nginx menyimpan IP lama container | Template nginx sudah memakai resolver dinamis. Bila tetap terjadi: `dc restart web`. |
| Situs tertahan di **maintenance** | Deploy terhenti di tengah | `dc exec app php artisan up` |
| Deploy Actions gagal di langkah SSH: *fingerprint mismatch* | Secret `VPS_SSH_FINGERPRINT` bukan kunci ECDSA | Di server: `ssh-keyscan -t ecdsa localhost \| ssh-keygen -lf -`, salin `SHA256:...` ke secret. |
| Deploy gagal: *unauthorized* saat pull GHCR | Server tidak login ke registry | Workflow login otomatis memakai `GITHUB_TOKEN`; untuk deploy manual login sementara dengan token `read:packages`. |
| Test CI gagal: *Vite manifest not found* | View dirender tanpa aset terbangun | `tests/TestCase.php` memanggil `withoutVite()`. Jangan dihapus. |
| HTTPS mati setelah perpanjangan sertifikat | nginx tidak di-reload | Hook `/etc/letsencrypt/renewal-hooks/deploy/reload-walhi-nginx.sh` menangani ini. Cek dengan `certbot renew --dry-run`. |
| Disk penuh | Image lama & backup | `docker image prune -a --filter "until=168h"`; cek `BACKUP_RETENTION_DAYS`. |

---

## 11. Branch `main`

`main` selalu disamakan otomatis dengan `production` oleh workflow `sync-main.yml`. Bila workflow itu gagal (misalnya ada yang commit langsung ke `main`), jangan force push. Pindahkan commit tersebut lewat PR ke `production`, lalu jalankan ulang workflow **Sync main with production** dari tab Actions.

---

## 12. Checklist Keamanan Server (cek berkala)

- [ ] Login SSH hanya dengan key: `sshd -T | grep -E 'passwordauthentication|permitrootlogin'` harus `no` dan `prohibit-password`. Pastikan Anda **sudah bisa login dengan key** sebelum menonaktifkan password.
- [ ] fail2ban aktif: `systemctl is-active fail2ban`.
- [ ] Update keamanan OS: `apt update && apt list --upgradable` (Ubuntu `unattended-upgrades` disarankan aktif).
- [ ] Semua akun admin/editor sudah mengaktifkan 2FA.
- [ ] Tidak ada file password sementara di `/root` atau di direktori aplikasi.
- [ ] `ADMIN_PASSWORD` / `EDITOR_PASSWORD` di `.env` kosong setelah akun dibuat.
- [ ] Backup harian berhasil dan off-site terisi.
- [ ] Sertifikat SSL > 30 hari sebelum kedaluwarsa.
