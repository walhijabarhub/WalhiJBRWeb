# Kebijakan Keamanan (Security Policy)

Wahana Lingkungan Hidup Indonesia — Jawa Barat (WALHI Jawa Barat) berkomitmen untuk menjaga keamanan dan privasi data anggota, jurnalis, pegiat lingkungan, donatur, serta seluruh pengguna platform web ini.

Dokumen ini menjelaskan kebijakan pelaporan kerentanan keamanan (*Responsible & Coordinated Vulnerability Disclosure*), versi yang didukung, serta komitmen standar pengamanan sistem kami.

---

## 🛡️ Versi yang Didukung (Supported Versions)

Kami secara aktif memelihara dan memberikan pembaruan keamanan (*security patches*) untuk rilis berikut:

| Versi | Didukung Pembaruan Keamanan |
| :--- | :---: |
| **Branch `production`** (yang sedang live; `main` adalah cerminnya) | :white_check_mark: Ya |
| Branch `prod` (usang) dan branch lain | :x: Tidak |

---

## 🚨 Prosedur Pelaporan Kerentanan (Reporting a Vulnerability)

Jika Anda menemukan celah keamanan (*vulnerability*) pada aplikasi atau infrastruktur WALHI Jawa Barat, kami sangat menghargai kerja sama Anda untuk melaporkannya secara bertanggung jawab (*Responsible Disclosure*).

### 1. Kanal Pelaporan Resmi
Kirimkan laporan teknis lengkap Anda ke alamat email:
📧 **kontak@walhijabar.org** *(Cc: **kontak@walhijabar.or.id**)*  
**Subjek:** `[SECURITY] Laporan Kerentanan - [Komponen Terkait]`

### 2. Informasi yang Diperlukan
Untuk mempercepat proses investigasi dan mitigasi, mohon sertakan:
- **Deskripsi & Dampak:** Penjelasan mengenai jenis kerentanan dan potensi risiko yang ditimbulkan.
- **Langkah Reproduksi (PoC):** Langkah-langkah detail agar tim teknis kami dapat mereproduksi celah tersebut.
- **Tangkapan Layar / Log:** Bukti pengujian (tanpa merusak integritas data sistem yang sedang berjalan).
- **Saran Perbaikan:** Rekomendasi teknis mitigasi (jika ada).

### 3. Etika & Ketentuan Pelaporan (*Disclosure Guidelines*)
- **JANGAN** mempublikasikan detail kerentanan ke ranah publik (GitHub Issues, media sosial, atau blog) sebelum perbaikan resmi dirilis.
- **JANGAN** mengakses, mengubah, atau menghapus data pengguna nyata tanpa izin.
- **JANGAN** melakukan serangan *Denial of Service (DoS/DDoS)* atau tindakan yang dapat mengganggu ketersediaan layanan publik.

### 4. Komitmen & SLA Penanganan Kami
- **Konfirmasi Penerimaan:** Tim teknis kami akan merespons dan mengonfirmasi laporan Anda dalam waktu maksimal **48 jam**.
- **Investigasi & Validasi:** Tim akan menganalisis tingkat keparahan (*Severity Assessment*) dan menguji PoC yang dikirimkan.
- **Perilisan Patch:** Perbaikan akan segera diterapkan ke branch utama dan server produksi sesegera mungkin.
- **Penghargaan (Acknowledgement):** Kami sangat mengapresiasi dan akan mencantumkan kontribusi Anda pada catatan rilis (*Release Notes*) jika diinginkan.

---

## 🔒 Standar Praktik Keamanan Platform

Secara arsitektural, platform web ini mengadopsi standar pengamanan berlapis (*Defense-in-Depth*):

1. **Proteksi Injeksi SQL & Validasi Input:** seluruh query memakai Eloquent / parameter binding; input divalidasi FormRequest; URL dibatasi `App\Rules\SafeUrl` (hanya `http(s)://`, `/…`, `#…`).
2. **Proteksi XSS:** HTML konten disanitasi HTMLPurifier saat disimpan dan saat ditampilkan; Blade `{{ }}` untuk semua teks lain.
3. **Content Security Policy ketat:** nonce per request, tanpa `unsafe-inline`/`unsafe-eval` untuk script, handler inline dilarang (`script-src-attr 'none'`), skrip CDN di-pin ke versi tertentu + SRI.
4. **HTTP Security Headers:** HSTS (HTTPS), `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, `X-Robots-Tag` untuk area admin.
5. **Autentikasi kuat:** URL login tersembunyi, registrasi publik nonaktif, rate limit per akun + IP, password minimal 12 karakter campuran, **2FA TOTP wajib** untuk admin/editor beserta *recovery codes*.
6. **Otorisasi:** RBAC (`admin`, `editor`, `subscriber`) lewat middleware `role:` dan Laravel Policies; kategori sensitif & aksi hapus hanya Admin.
7. **Dokumen privat:** dokumen organisasi disimpan di luar webroot dan diunduh lewat endpoint terotorisasi dengan nama file yang disanitasi.
8. **Pembayaran:** webhook Midtrans diverifikasi signature SHA-512, nominal dicocokkan, status dikonfirmasi ke Status API, transisi status dikunci (`lockForUpdate`) dan `success` bersifat final.
9. **Anti-spam:** honeypot, rate limit, CAPTCHA Cloudflare Turnstile opsional (fail-closed).
10. **Jejak audit & privasi:** aksi administratif tercatat di `audit_logs` dan `audit.log`; data pribadi lama dianonimkan otomatis; newsletter *double opt-in*.
11. **Infrastruktur:** `APP_DEBUG=false`, sesi terenkripsi di Redis ber-password, Redis/MySQL tidak dipublish, backup harian terverifikasi. `setup-vps.sh` menyediakan pengerasan SSH (key-only, `PermitRootLogin prohibit-password`) dan fail2ban; pastikan keduanya benar-benar aktif di server (lihat [docs/operations.md](docs/operations.md)).
12. **Rantai pasok:** semua GitHub Action di-pin ke commit SHA, gitleaks memindai seluruh riwayat, `composer audit` & `npm audit` tiap minggu, Dependabot aktif, image produksi dibangun di CI.
