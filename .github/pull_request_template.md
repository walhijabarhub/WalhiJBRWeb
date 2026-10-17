## 📌 Deskripsi Perubahan
<!-- Jelaskan secara ringkas perubahan apa yang dibuat, latar belakang, dan tujuan dari perubahan tersebut. -->

## 🔗 Issue Terkait
<!-- Contoh: Fixes #12, Closes #45, atau Tulis 'N/A' jika tidak ada -->
Fixes #

## 🔍 Tipe Perubahan
<!-- Beri tanda centang [x] pada opsi yang sesuai -->
- [ ] 🐛 **Perbaikan Bug** (*Bug fix yang menyelesaikan masalah*)
- [ ] ✨ **Fitur Baru** (*Penambahan fungsionalitas baru*)
- [ ] 🎨 **Perubahan Tampilan / Styling** (*Penyesuaian UI / UX / CSS*)
- [ ] ⚡ **Optimasi Performa** (*Peningkatan performa aplikasi / query*)
- [ ] 📝 **Dokumentasi** (*Pembaruan README / panduan / komentar kode*)
- [ ] ♻️ **Refactoring Kode** (*Restrukturisasi kode tanpa mengubah fungsionalitas*)
- [ ] 🔒 **Pembaruan Keamanan** (*Security patch*)

## 🧪 Langkah Pengujian (Testing Steps)
<!-- Jelaskan langkah-langkah konkret untuk menguji perubahan ini di lingkungan lokal -->
1. Jalankan `docker compose up -d --build` (atau `php artisan serve`)
2. Buka URL: `http://localhost:8000/...`
3. Lakukan langkah: ...
4. Hasil yang diharapkan: ...

## 📸 Bukti Pengujian / Screenshot (Wajib untuk perubahan UI)
<!-- Sisipkan tangkapan layar / GIF / video singkat yang membuktikan fitur berjalan dengan baik -->

## 📋 Checklist Kepatuhan Aturan PR
<!-- Pastikan Anda telah memenuhi seluruh ketentuan berikut sebelum meminta review -->
- [ ] **Fokus Tunggal**: PR ini hanya membahas 1 topik/fitur (*tidak mencampur banyak hal yang tidak berkaitan*).
- [ ] **Target Branch**: PR ini diarahkan ke `production` (bukan `main` / `prod`). Merge = langsung deploy ke server live.
- [ ] **Bebas Konflik**: Branch ini sudah up-to-date dengan `production` terbaru dan tidak ada *merge conflicts*.
- [ ] **Bebas Kredensial**: Tidak ada file `.env`, password, token, atau data sensitif yang terkomit.
- [ ] **Standar Kode**: Kode mematuhi standar PSR-12 dan sistem desain Neo-Brutalisme WALHI.
- [ ] **Pengujian Lokal**: `php artisan test` lulus, dan tidak ada error di browser console (termasuk pelanggaran CSP) maupun Laravel logs.
- [ ] **Migrasi Aman**: Migrasi (bila ada) aman dijalankan saat versi lama masih melayani, dan tidak menghapus data.
- [ ] **Dokumentasi**: Dokumen terkait (`docs/`, `CHANGELOG.md`, `.env.production.example`) ikut diperbarui bila perilaku atau konfigurasi berubah.
- [ ] **Review**: Siap untuk direview oleh minimal 1 rekan tim / maintainer.
