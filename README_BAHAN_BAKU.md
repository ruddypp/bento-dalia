# Panduan Penggunaan Fitur Bahan Baku

## Pengenalan
Fitur Bahan Baku digunakan untuk mencatat dan mengelola data bahan baku yang masuk ke dalam sistem inventori. Fitur ini terintegrasi dengan modul barang dan laporan masuk, sehingga setiap penambahan bahan baku akan otomatis memperbarui stok barang dan membuat catatan di laporan masuk.

## Perbaikan Database
Sebelum menggunakan fitur ini, pastikan struktur database sudah sesuai. Jalankan query berikut di phpMyAdmin atau melalui terminal MySQL:

1. Buka file `database/fix_database.sql`
2. Jalankan semua query yang ada di file tersebut

**Catatan**: Query ini akan:
- Memperbaiki foreign key constraint antara tabel `bahan_baku` dan `users`
- Mengubah tipe data kolom periode di tabel `barang` menjadi DECIMAL
- Menambahkan kolom lokasi di tabel `barang` jika belum ada
- Menambahkan kolom periode di tabel `barang_masuk` jika belum ada
- Mereset tabel `bahan_baku` untuk menghindari masalah data yang tidak konsisten

## Cara Menggunakan Fitur Bahan Baku

### 1. Menambahkan Bahan Baku
1. Klik tombol "Tambah Bahan Baku" di halaman Bahan Baku
2. Isi form dengan data yang diperlukan:
   - Pilih nama barang dari dropdown
   - Masukkan quantity
   - Pilih periode (1-4)
   - Masukkan harga satuan
   - Masukkan lokasi penyimpanan
3. Klik "Simpan" untuk menyimpan data

Setelah data disimpan, sistem akan otomatis:
- Menambahkan stok barang sesuai quantity
- Mencatat nilai periode pada barang
- Membuat entri di tabel barang_masuk
- Membuat laporan masuk baru dengan status "approved"

### 2. Melihat Data Bahan Baku
Data bahan baku ditampilkan dalam bentuk tabel dengan informasi:
- Nama barang
- Quantity
- Satuan
- Periode
- Harga satuan
- Total (Qty x Harga satuan)
- Lokasi
- Tanggal input

### 3. Memfilter Data Bahan Baku
Anda dapat memfilter data berdasarkan periode dengan menggunakan dropdown filter di bagian atas halaman.

### 4. Melihat Total Per Periode
Di bagian atas halaman terdapat kartu ringkasan yang menampilkan total nilai bahan baku untuk setiap periode (1-4).

### 5. Menghapus Bahan Baku
1. Klik ikon hapus (tempat sampah) pada baris data yang ingin dihapus
2. Konfirmasi penghapusan pada dialog yang muncul

Setelah data dihapus, sistem akan otomatis:
- Mengurangi stok barang sesuai quantity yang dihapus
- Mereset nilai periode pada barang
- Menghapus data bahan baku dari database

## Integrasi dengan Modul Lain
Fitur Bahan Baku terintegrasi dengan:
1. **Barang**: Setiap penambahan/penghapusan bahan baku akan memperbarui stok barang dan nilai periode
2. **Laporan Masuk**: Setiap penambahan bahan baku akan membuat entri baru di laporan masuk
3. **Log Aktivitas**: Semua aktivitas penambahan/penghapusan bahan baku akan dicatat di log aktivitas

## Troubleshooting
Jika mengalami masalah dengan DataTables, pastikan:
1. Class tabel sudah diubah dari "data-table" menjadi "bahan-baku-table"
2. Inisialisasi DataTables hanya dilakukan jika tabel belum diinisialisasi sebelumnya

## Catatan Penting
- Nilai periode disimpan dalam format Rupiah (decimal) di tabel barang
- Setiap bahan baku hanya bisa dihapus oleh user yang membuatnya atau admin
- Pastikan semua field required diisi saat menambahkan bahan baku baru 