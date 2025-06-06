# Panduan Perbaikan Sistem Inventori (Revisi)

Panduan ini berisi langkah-langkah untuk memperbaiki masalah pada sistem pemesanan barang dan integrasi dengan bahan baku.

## Masalah yang Diperbaiki

1. **Masalah 1:** Data yang diapprove di bahan_baku menghilang
2. **Masalah 2:** Status di pesan_barang tidak otomatis berubah saat bahan_baku diapprove
3. **Masalah 3:** Barang masuk seharusnya masuk ke laporan_masuk.php, bukan retur_barang.php

## Langkah-langkah Perbaikan

### Langkah 1: Perbaiki Trigger Database

**PENTING:** Ada masalah sintaks pada file SQL sebelumnya. Gunakan file yang direvisi.

**Opsi 1:** Menggunakan phpMyAdmin (DIREKOMENDASIKAN)
1. Buka phpMyAdmin
2. Pilih database `inventori_db3`
3. Buka tab SQL
4. Copy dan paste isi dari file `fix_trigger_separate_steps.sql`
5. **PENTING:** Jalankan query secara terpisah:
   - Jalankan LANGKAH 1 dan 2 (DROP TRIGGER dan DROP FUNCTION)
   - Jalankan LANGKAH 3 (CREATE FUNCTION) secara terpisah
   - Jalankan LANGKAH 4 (CREATE TRIGGER) secara terpisah

**Opsi 2:** Menggunakan file PHP
1. Buka file `update_trigger_revised.php` di browser
2. Login jika diminta
3. Tunggu sampai muncul pesan "Trigger dan function berhasil diperbarui!"

### Langkah 2: Perbaiki File bahan_baku.php

Jalankan file berikut untuk memperbaiki bahan_baku.php:

1. Buka file `fix_bahan_baku_implement.php` di browser
2. Tunggu sampai muncul pesan "Perbaikan berhasil diterapkan!"
3. File asli akan dibackup dengan format `bahan_baku.php.bak_[timestamp]`

Perbaikan yang dilakukan:
- Mengubah query untuk tetap menampilkan item yang sudah diapprove
- Memperbaiki fungsi edit_bahan_baku agar tidak menghilangkan data
- Mengubah redirect setelah approval ke laporan_masuk.php

### Langkah 3: Perbaiki File process_pesanan.php

Jalankan file berikut untuk memperbaiki process_pesanan.php:

1. Buka file `fix_process_pesanan_implement.php` di browser
2. Tunggu sampai muncul pesan "Perbaikan berhasil diterapkan!"
3. File asli akan dibackup dengan format `process_pesanan.php.bak_[timestamp]`

Perbaikan yang dilakukan:
- Memperbaiki proses pembuatan bahan_baku dari pesanan
- Menambahkan field id_pesanan ke bahan_baku untuk referensi
- Memperbarui status pesanan dengan benar

## Cara Kerja Setelah Perbaikan

1. Saat membuat pesanan di pesan_barang.php:
   - Pesanan disimpan dengan status "pending"
   - Data tidak otomatis masuk ke bahan_baku

2. Saat memproses pesanan:
   - Data masuk ke bahan_baku dengan status "pending"
   - Status pesanan berubah menjadi "processed"
   - Field id_pesanan di bahan_baku terisi dengan ID pesanan

3. Saat menyetujui bahan_baku:
   - Item tetap terlihat di daftar bahan_baku
   - Data masuk ke laporan_masuk.php
   - Status pesanan di pesan_barang.php otomatis berubah
   - Jika semua item disetujui, status pesanan menjadi "approved"

## Catatan Penting

- Pastikan semua file telah dibackup sebelum menjalankan perbaikan
- Jika terjadi masalah, Anda dapat mengembalikan file dari backup
- Perubahan pada trigger database akan langsung aktif setelah dijalankan
- **PENTING:** Jika mengalami error saat membuat trigger, pastikan untuk menjalankan query SQL secara terpisah seperti yang dijelaskan pada Langkah 1 Opsi 1 