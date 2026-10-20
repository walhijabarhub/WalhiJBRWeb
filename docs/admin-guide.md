# 👩‍💻 Panduan Pengelola Konten (Admin & Editor)

Panduan ini untuk tim WALHI Jawa Barat yang mengelola isi website lewat panel admin. Tidak perlu pengetahuan teknis.

---

## 1. Masuk ke Panel Admin

1. Buka **https://walhijabar.or.id/portal-jabar** (alamat ini sengaja tidak ditautkan dari website publik; jangan dibagikan ke luar tim).
2. Isi **email** (atau nama pengguna, yaitu bagian sebelum `@`) dan **password**.
3. Masukkan **kode 6 digit** dari aplikasi autentikator (lihat bagian 2).

Lima kali salah password dalam satu menit akan mengunci sementara percobaan login. Tunggu sebentar lalu coba lagi.

**Lupa password?** Klik "Lupa password" di halaman login. Tautan reset dikirim ke email Anda (hanya bila email server sudah aktif). Bila tidak ada email masuk, hubungi pengelola server.

---

## 2. Autentikasi Dua Langkah (2FA), Wajib

Setiap akun admin dan editor wajib memakai 2FA. Tanpa 2FA, panel admin tidak bisa dibuka.

### Mengaktifkan (sekali, saat login pertama)
1. Pasang aplikasi autentikator di HP: **Google Authenticator**, **Microsoft Authenticator**, **Aegis** (Android), atau **2FAS**.
2. Setelah login, Anda otomatis dibawa ke halaman **Autentikasi Dua Langkah**.
3. Pindai **kode QR** dengan aplikasi tersebut (atau ketik kode rahasianya).
4. Masukkan kode 6 digit yang muncul di aplikasi, lalu konfirmasi.
5. Halaman menampilkan **recovery codes** (kode cadangan). **Simpan sekarang** di tempat aman (password manager atau dicetak dan disimpan). Kode ini hanya ditampilkan sekali.

### Setiap login berikutnya
Setelah password, masukkan kode 6 digit terbaru dari aplikasi. Kode berganti setiap 30 detik dan setiap kode hanya bisa dipakai sekali.

### Ganti HP / HP hilang
- Masih punya **recovery code**: di halaman kode 2FA, isi kolom **Kode Pemulihan**. Setiap kode hanya bisa dipakai **sekali**. Setelah masuk, buka **Keamanan Akun (2FA)** di menu akun (pojok kanan atas) dan buat kode pemulihan baru (diminta konfirmasi password).
- Untuk **pindah ke HP baru**, atau bila tidak punya recovery code: hubungi pengelola server untuk reset 2FA, lalu daftarkan ulang di HP baru saat login berikutnya. (2FA tidak bisa dimatikan sendiri karena wajib.)
- Tips: aplikasi seperti Aegis, 2FAS, atau Google Authenticator (dengan pencadangan akun Google) bisa memindahkan kode ke HP baru tanpa reset.

---

## 3. Peran & Hak Akses

| Bisa melakukan | Editor | Admin |
| :--- | :---: | :---: |
| Membuat, mengubah, menerbitkan, mengarsipkan konten | ✅ | ✅ |
| Menyetujui komentar / menandai spam | ✅ | ✅ |
| Melihat daftar pelanggan nawala | ✅ | ✅ |
| Mengelola **Kontak**, **Donasi Publik**, **Kampanye Darurat** | ❌ | ✅ |
| Menghapus konten, komentar, pelanggan | ❌ | ✅ |
| Mengekspor pelanggan ke CSV | ❌ | ✅ |

Semua perubahan tercatat di jejak audit (siapa, kapan, dari mana, apa yang diubah).

---

## 4. Mengelola Konten

Menu di sidebar dikelompokkan menjadi **Halaman Beranda** (banner, statistik, isu kritis), **Publikasi** (blog, siaran pers, infografis, kertas posisi, catatan kritis, regulasi, laporan tahunan, nawala, buletin, jurnal), **Tentang Kami**, dan **Dukung Kami**.

### Status konten
| Status | Arti |
| :--- | :--- |
| **Draft** | Belum tampil di website. Aman untuk menyimpan tulisan setengah jadi. |
| **Published** | Tampil untuk publik. |
| **Archived** | Disembunyikan dari website tanpa dihapus. |

Status bisa diubah dari form edit atau tombol ubah status di daftar konten.

### Menulis artikel
- **Judul** wajib. **Slug** (alamat URL) dibuat otomatis dari judul bila dikosongkan.
- Isi ditulis di editor. Gambar di dalam artikel diunggah lewat tombol gambar editor (JPEG/PNG/WebP/GIF, maks. 2 MB).
- Kode yang ditempel ke editor (skrip, iframe, atribut berbahaya) otomatis dibuang saat disimpan. Untuk menyematkan video, cukup tautkan URL-nya.
- **Gambar sampul / berkas:** unggah file (maks. **2 MB**; gambar JPEG/PNG/WebP/GIF, atau dokumen PDF/DOC/DOCX/XLS/XLSX), atau isi URL gambar/dokumen. File SVG dan file program ditolak.
- Gambar JPEG/PNG/WebP yang diunggah (sampul maupun gambar di editor) otomatis diperkecil ke sisi terpanjang **1920 px** dan orientasi foto ponsel dibetulkan. Nama, format, dan alamat berkas tetap sama; GIF tidak diubah, dan WebP hanya diproses bila GD di server mendukung WebP (image Docker produksi saat ini belum).
- **Dokumen** (PDF, Word, Excel) disimpan di area privat dan diunduh pengunjung lewat tombol unduh. Dokumen dari konten **draft** tidak bisa diunduh publik.

### Format khusus
| Menu | Isian khusus |
| :--- | :--- |
| **Banner Beranda** | Teks & tautan dua tombol. Tautan harus diawali `https://`, `/` (halaman di situs ini), atau `#`. Tautan lain ditolak. |
| **Isu Kritis** | Ikon (nama file, mis. `Icon-4.svg`) dan teks badge. |
| **Regulasi** | Tag: `kategori, penerbit, status` (contoh: `undang-undang, Pemerintah RI, berlaku`). |

Perubahan konten langsung terlihat di website. Cache beranda diperbarui otomatis.

---

## 5. Moderasi Komentar

Menu **Komentar**. Komentar baru berstatus *pending* dan **belum tampil** sampai disetujui.
- **Setujui**: komentar tampil di artikel.
- **Spam**: komentar disembunyikan.
- **Hapus** (admin): komentar dihapus.

Jangan menyetujui komentar yang berisi data pribadi orang lain, ujaran kebencian, atau tautan mencurigakan.

---

## 6. Pelanggan Nawala (Newsletter)

Menu **Pelanggan**. Pendaftar harus mengklik tautan konfirmasi di email (berlaku 7 hari). Yang belum mengonfirmasi tidak ikut diekspor dan dihapus otomatis setelah 30 hari.

**Ekspor CSV** (admin) hanya berisi pelanggan terkonfirmasi. File ini berisi data pribadi, jadi simpan dengan aman dan jangan dibagikan ke pihak di luar keperluan pengiriman nawala.

---

## 7. Donasi

Status donasi diperbarui otomatis dari Midtrans: *pending* (menunggu pembayaran), *success*, *failed*, atau *expired* (tidak dibayar dalam 24 jam). Status tidak bisa diubah manual dari panel. Bila ada selisih dengan dashboard Midtrans, laporkan ke pengelola server.

Data pribadi donatur (nama, email, telepon) dianonimkan otomatis setelah 24 bulan; nominal tetap tercatat untuk laporan transparansi.

---

## 8. Keamanan Akun, Wajib Dibaca

- Jangan pernah membagikan password, kode 2FA, atau recovery code, termasuk ke sesama tim. Setiap orang memakai akunnya sendiri.
- Gunakan password unik minimal 12 karakter (huruf besar, kecil, angka, simbol). Password yang pernah bocor di internet akan ditolak.
- Selalu **Keluar** setelah memakai komputer bersama.
- Waspadai email yang meminta Anda login atau mengirim kode. WALHI Jawa Barat tidak pernah meminta kode 2FA lewat email, chat, atau telepon.
- Bila curiga akun disalahgunakan, segera ganti password dan hubungi pengelola server.
