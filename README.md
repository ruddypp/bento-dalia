# DOKUMENTASI SISTEM MANAJEMEN INVENTORI

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Persyaratan Sistem](#persyaratan-sistem)
3. [Instalasi](#instalasi)
4. [Struktur Database](#struktur-database)
5. [Fitur Sistem](#fitur-sistem)
6. [Panduan Penggunaan](#panduan-penggunaan)
7. [Manajemen Akses (RBAC)](#manajemen-akses-rbac)
8. [Fitur Ekspor Data](#fitur-ekspor-data)
9. [Modul Laporan](#modul-laporan)
10. [Pemecahan Masalah](#pemecahan-masalah)
11. [Pengembangan Lanjutan](#pengembangan-lanjutan)
12. [Informasi Teknis](#informasi-teknis)

## Pendahuluan

Sistem Manajemen Inventori adalah aplikasi berbasis web yang dirancang untuk mengelola stok barang, supplier, penerimaan, pengeluaran, stok opname, dan retur barang. Aplikasi ini dibangun menggunakan PHP native, MySQL, dan Tailwind CSS.

Aplikasi ini cocok digunakan untuk berbagai jenis usaha yang membutuhkan manajemen inventori yang efisien, seperti restoran, kafe, toko retail, dan lain-lain.

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (XAMPP, WAMP, atau Laragon)
- Browser modern (Chrome, Firefox, Edge)
- Composer (untuk instalasi library)

## Instalasi

### 1. Instalasi XAMPP

1. **Download XAMPP**
   - Kunjungi [website XAMPP](https://www.apachefriends.org/download.html)
   - Download versi terbaru yang sesuai dengan sistem operasi anda
   - Pilih versi dengan PHP 7.4 atau lebih tinggi

2. **Instalasi XAMPP**
   - Jalankan file installer
   - Ikuti petunjuk instalasi (disarankan gunakan pengaturan default)
   - Pastikan komponen Apache dan MySQL dipilih

3. **Jalankan XAMPP**
   - Buka XAMPP Control Panel
   - Nyalakan modul Apache dan MySQL dengan klik tombol "Start"

### 2. Mengunduh dan Menyiapkan Aplikasi

1. **Download Aplikasi**
   - Download file zip dari repositori atau clone
   - Ekstrak file ke direktori `htdocs` di folder instalasi XAMPP
     ```
     C:\xampp\htdocs\inventory
     ```

2. **Setup Database**
   - Buka browser dan ketik: `http://localhost/phpmyadmin`
   - Buat database baru dengan nama `inventori_db`
   - Pilih database `inventori_db`
   - Pilih tab "Import"
   - Pilih file `database/inventori_db.sql` dari folder aplikasi
   - Klik tombol "Go" untuk mengimpor database

3. **Konfigurasi Koneksi Database**
   - Buka file `config/database.php`
   - Pastikan pengaturan koneksi sesuai, biasanya untuk XAMPP:
     ```php
     $host = 'localhost';
     $user = 'root';
     $password = '';
     $database = 'inventori_db';
     ```

4. **Instalasi Dependencies**
   - Pastikan Composer sudah terinstal
   - Buka terminal atau command prompt
   - Arahkan ke direktori aplikasi
   - Jalankan perintah:
     ```
     composer install
     ```
   - Jika tidak bisa menggunakan Composer, kunjungi `http://localhost/inventory/install_dependencies.php` dan ikuti petunjuk

### 3. Menjalankan Aplikasi

1. **Akses Aplikasi**
   - Buka browser dan ketik: `http://localhost/inventory`
   - Anda akan diarahkan ke halaman login

2. **Login ke Sistem**
   - Username: `admin`
   - Password: `admin123`

3. **Reset Password Admin (Jika Diperlukan)**
   - Jika lupa password admin, akses: `http://localhost/inventory/reset_admin.php`
   - Ikuti petunjuk untuk reset password admin

## Struktur Database

Sistem ini menggunakan beberapa tabel utama dalam database:

### Tabel Utama

1. **users**
   - Menyimpan data pengguna sistem
   - Kolom utama: id_user, username, password, role_id, nama_lengkap

2. **barang**
   - Menyimpan data barang/produk
   - Kolom utama: id_barang, nama_barang, satuan, jenis, stok, harga, lokasi, periode

3. **supplier**
   - Menyimpan data supplier/vendor
   - Kolom utama: id_supplier, nama_supplier, alamat, kontak

4. **bahan_baku**
   - Menyimpan data bahan baku yang masuk
   - Kolom utama: id_bahan_baku, id_barang, qty, periode, harga_satuan, total, lokasi, status

5. **barang_masuk**
   - Mencatat setiap transaksi barang masuk
   - Kolom utama: id_masuk, id_barang, tanggal_masuk, id_user, qty_masuk

6. **barang_keluar**
   - Mencatat setiap transaksi barang keluar
   - Kolom utama: id_keluar, id_barang, tanggal_keluar, id_user, qty_keluar

7. **pesanan_barang**
   - Menyimpan data pesanan ke supplier
   - Kolom utama: id_pesanan, id_supplier, tanggal_pesan, status

8. **pesanan_detail**
   - Detail item dalam pesanan
   - Kolom utama: id_detail, id_pesanan, id_barang, qty, harga_satuan, total

9. **retur_barang**
   - Mencatat barang yang diretur ke supplier
   - Kolom utama: id_retur, id_barang, qty_retur, tanggal_retur, alasan_retur

10. **menu**
    - Menyimpan data menu (untuk restoran/kafe)
    - Kolom utama: id_menu, nama_menu, kategori, harga, bahan, harga_modal, keuntungan

11. **penjualan**
    - Mencatat transaksi penjualan
    - Kolom utama: id_penjualan, no_invoice, tanggal_penjualan, total_harga, total_modal, keuntungan

12. **penjualan_detail**
    - Detail item dalam penjualan
    - Kolom utama: id_penjualan_detail, id_penjualan, id_menu, jumlah, harga_satuan, subtotal

13. **laporan_masuk** dan **laporan_keluar**
    - Menyimpan data laporan barang masuk dan keluar
    - Kolom utama: id_laporan, tanggal_laporan, status

## Fitur Sistem

### 1. Manajemen Barang
- Tambah, edit, hapus data barang
- Lihat stok barang
- Filter barang berdasarkan kategori
- Atur stok minimum untuk notifikasi

### 2. Manajemen Supplier
- Tambah, edit, hapus data supplier
- Lihat riwayat transaksi dengan supplier

### 3. Penerimaan Barang
- Catat penerimaan barang dari supplier
- Verifikasi jumlah dan kualitas barang
- Otomatis update stok

### 4. Pengeluaran Barang
- Catat pengeluaran barang
- Pilih barang dan jumlah yang dikeluarkan
- Otomatis update stok

### 5. Pesanan Barang
- Buat pesanan ke supplier
- Input total harga dan sistem otomatis menghitung harga satuan
- Tracking status pesanan (pending, diproses, selesai, dibatalkan)

### 6. Bahan Baku
- Catat bahan baku yang masuk
- Perhitungan harga otomatis
- Verifikasi dan approval bahan baku

### 7. Stok Opname
- Verifikasi stok fisik vs stok sistem
- Hitung selisih stok
- Penyesuaian stok otomatis

### 8. Retur Barang
- Catat barang yang diretur ke supplier
- Alasan retur
- Penyesuaian stok otomatis

### 9. Menu (untuk restoran/kafe)
- Kelola menu makanan dan minuman
- Hitung harga modal dan keuntungan
- Upload gambar menu

### 10. Penjualan
- Catat transaksi penjualan
- Hitung total penjualan, modal, dan keuntungan
- Cetak invoice

### 11. Laporan
- Laporan barang masuk
- Laporan barang keluar
- Laporan penjualan
- Ekspor laporan ke PDF dan Excel

### 12. Manajemen Pengguna
- Tambah, edit, hapus pengguna
- Atur peran dan hak akses
- Log aktivitas pengguna

## Panduan Penggunaan

### 1. Dashboard
- Halaman utama berisi ringkasan data stok dan aktivitas terbaru
- Notifikasi stok menipis akan ditampilkan jika ada
- Grafik perbandingan penerimaan dan pengeluaran barang

### 2. Manajemen Barang
- **Menambah Barang**:
  - Klik menu "Barang" di sidebar
  - Klik tombol "Tambah Barang"
  - Isi form dengan lengkap
  - Klik "Simpan"

- **Mengedit/Menghapus Barang**:
  - Klik menu "Barang" di sidebar
  - Cari barang yang ingin diubah
  - Klik ikon edit (pensil) atau hapus (tempat sampah)

### 3. Manajemen Supplier
- **Menambah Supplier**:
  - Klik menu "Supplier" di sidebar
  - Klik tombol "Tambah Supplier"
  - Isi form dengan lengkap
  - Klik "Simpan"

- **Mengedit/Menghapus Supplier**:
  - Klik menu "Supplier" di sidebar
  - Cari supplier yang ingin diubah
  - Klik ikon edit (pensil) atau hapus (tempat sampah)

### 4. Pesanan Barang
- **Membuat Pesanan Baru**:
  - Klik menu "Pesan Barang" di sidebar
  - Klik tombol "Tambah Pesanan"
  - Pilih supplier dari dropdown
  - Masukkan tanggal pesanan
  - Tambahkan item dengan mengisi:
    - Pilih barang
    - Masukkan jumlah (qty)
    - Pilih periode
    - Masukkan total harga (sistem akan otomatis menghitung harga satuan)
    - Pilih lokasi
  - Klik "Simpan Pesanan"

- **Melihat Status Pesanan**:
  - Klik menu "Pesan Barang" di sidebar
  - Status pesanan akan ditampilkan:
    - Pending: Pesanan baru dibuat
    - Diproses: Sebagian item telah diapprove di Bahan Baku
    - Selesai: Semua item telah diapprove
    - Dibatalkan: Pesanan dibatalkan

### 5. Bahan Baku
- **Menambah Bahan Baku**:
  - Klik menu "Bahan Baku" di sidebar
  - Klik tombol "Tambah Bahan Baku"
  - Isi form dengan data yang diperlukan:
    - Pilih nama barang dari dropdown
    - Masukkan quantity
    - Pilih periode (1-4)
    - Masukkan harga satuan
    - Masukkan lokasi penyimpanan
  - Klik "Simpan"

- **Menyetujui Bahan Baku**:
  - Klik menu "Bahan Baku" di sidebar
  - Cari bahan baku dengan status "Pending"
  - Klik ikon edit (pensil)
  - Ubah status menjadi "Approved"
  - Klik "Simpan"

- **Retur Bahan Baku**:
  - Klik menu "Bahan Baku" di sidebar
  - Cari bahan baku yang ingin diretur
  - Klik ikon retur
  - Masukkan jumlah yang diretur dan alasan
  - Klik "Simpan"

### 6. Penerimaan Barang
- **Mencatat Penerimaan**:
  - Klik menu "Penerimaan" di sidebar
  - Klik tombol "Tambah Penerimaan"
  - Pilih supplier dan tanggal
  - Tambahkan barang dan jumlah yang diterima
  - Klik "Simpan"

- **Melihat Detail Penerimaan**:
  - Klik menu "Penerimaan" di sidebar
  - Cari data penerimaan
  - Klik ikon detail (mata) untuk melihat item yang diterima

### 7. Pengeluaran Barang
- **Mencatat Pengeluaran**:
  - Klik menu "Pengeluaran" di sidebar
  - Klik tombol "Tambah Pengeluaran"
  - Pilih tanggal dan isi keperluan
  - Tambahkan barang dan jumlah yang dikeluarkan
  - Klik "Simpan"

- **Melihat Detail Pengeluaran**:
  - Klik menu "Pengeluaran" di sidebar
  - Cari data pengeluaran
  - Klik ikon detail (mata) untuk melihat item yang dikeluarkan

### 8. Stok Opname
- **Melakukan Stok Opname**:
  - Klik menu "Stok Opname" di sidebar
  - Klik tombol "Tambah Stok Opname"
  - Pilih barang dan masukkan stok fisik
  - Sistem akan otomatis menghitung selisih
  - Klik "Simpan"

### 9. Menu (Restoran/Kafe)
- **Menambah Menu**:
  - Klik menu "Menu" di sidebar
  - Pilih "Menu Makanan" atau "Menu Minuman"
  - Klik tombol "Tambah Menu"
  - Isi form dengan data menu
  - Masukkan bahan-bahan dan jumlahnya
  - Sistem akan otomatis menghitung harga modal dan keuntungan
  - Klik "Simpan"

- **Mengedit/Menghapus Menu**:
  - Klik menu "Menu" di sidebar
  - Pilih "Menu Makanan" atau "Menu Minuman"
  - Cari menu yang ingin diubah
  - Klik ikon edit (pensil) atau hapus (tempat sampah)

### 10. Penjualan
- **Mencatat Penjualan**:
  - Klik menu "Penjualan" di sidebar
  - Klik tombol "Tambah Penjualan"
  - Pilih menu yang dijual dan jumlahnya
  - Sistem akan otomatis menghitung total
  - Klik "Simpan"

- **Melihat Laporan Penjualan**:
  - Klik menu "Laporan Penjualan" di sidebar
  - Filter berdasarkan tanggal jika diperlukan
  - Lihat total penjualan, modal, dan keuntungan

## Manajemen Akses (RBAC)

Sistem ini menggunakan 5 role dengan akses yang berbeda-beda:

### 1. Admin (role_id: 1)
- **Akses**: Semua fitur dengan akses penuh (view, add, edit, delete)

### 2. Purchasing (role_id: 2)
- **Akses Penuh**:
  - supplier.php - Data Supplier
  - pesan_barang.php - Pesan Barang
  - bahan_baku.php - Data Bahan Baku
  - laporan_masuk.php - Laporan Barang Masuk
- **Akses View Only**:
  - barang.php - Stok Barang
  - retur_barang.php - Retur Barang
  - barang_lost.php - Barang Lost

### 3. Kasir (role_id: 3)
- **Akses Penuh**:
  - barang.php - Stok Barang
  - bahan_baku.php - Data Bahan Baku
  - retur_barang.php - Retur Barang
  - menu_makanan.php - Menu Makanan
  - menu_minuman.php - Menu Minuman
  - penjualan.php - Transaksi Penjualan
  - laporan_penjualan.php - Laporan Penjualan
  - laporan_masuk.php - Laporan Barang Masuk
- **Akses View Only**:
  - supplier.php - Data Supplier

### 4. Head Produksi (role_id: 4)
- **Akses Penuh**:
  - barang.php - Stok Barang
  - retur_barang.php - Retur Barang
  - barang_lost.php - Barang Lost
- **Akses View Only**:
  - bahan_baku.php - Data Bahan Baku

### 5. Crew (role_id: 5)
- **Akses View Only** untuk SEMUA fitur (tidak bisa menambah, mengedit, atau menghapus data)
- Hanya bisa melihat dan mencetak laporan
- Pengecualian: memiliki akses penuh ke profile.php untuk mengubah data profil sendiri

## Fitur Ekspor Data

Sistem ini mendukung ekspor data tabel ke format PDF dan Excel. Fitur ini telah diimplementasikan untuk modul Bahan Baku (Raw Materials).

### Persyaratan Ekspor

Fitur ekspor memerlukan library PHP berikut:
- TCPDF untuk generasi PDF
- PhpSpreadsheet untuk generasi Excel

### Cara Menggunakan Fitur Ekspor

1. Buka halaman Bahan Baku (`bahan_baku.php`)
2. Klik tombol dropdown "Export"
3. Pilih format PDF atau Excel
4. File akan dihasilkan dan diunduh ke perangkat Anda

### Filter Ekspor

Ekspor akan menghormati filter yang telah Anda terapkan pada tabel data. Misalnya, jika Anda memfilter berdasarkan periode tertentu, hanya data dari periode tersebut yang akan disertakan dalam file yang diekspor.

## Modul Laporan

Modul Laporan menyediakan fitur untuk membuat dan melihat laporan barang masuk dan keluar.

### Fitur Laporan

1. **Laporan Barang Masuk**
   - Buat laporan untuk barang masuk
   - Pilih beberapa transaksi masuk untuk disertakan dalam laporan
   - Lihat laporan detail
   - Cetak laporan

2. **Laporan Barang Keluar**
   - Buat laporan untuk barang keluar
   - Pilih beberapa transaksi keluar untuk disertakan dalam laporan
   - Lihat laporan detail
   - Cetak laporan

### Cara Menggunakan Modul Laporan

#### Membuat Laporan Barang Masuk

1. Navigasi ke "Laporan Barang → Laporan Barang Masuk" di sidebar
2. Klik tombol "Buat Laporan Baru"
3. Pilih rentang tanggal untuk memfilter transaksi barang masuk
4. Centang transaksi yang ingin disertakan dalam laporan
5. Klik "Buat Laporan"
6. Laporan akan dibuat dan ditampilkan dalam daftar laporan

#### Melihat Detail Laporan

1. Di halaman Laporan Barang Masuk atau Keluar, klik ikon detail (mata) di samping laporan
2. Halaman detail akan menampilkan semua transaksi yang termasuk dalam laporan
3. Klik "Cetak" untuk mencetak laporan dalam format PDF

## Pemecahan Masalah

### 1. Error "Headers Already Sent"
- Pastikan file PHP tidak memiliki spasi atau karakter di awal file
- Pastikan `ob_start()` ada di awal file `header.php`
- Pastikan `ob_end_flush()` ada di akhir file `footer.php`
- Pastikan setiap file PHP yang menyertakan footer.php memiliki `ob_end_flush()` di akhir file

### 2. Koneksi Database Gagal
- Pastikan MySQL berjalan di XAMPP Control Panel
- Periksa pengaturan di `config/database.php`
- Pastikan nama database, username, dan password sudah benar

### 3. Halaman Tidak Ditemukan
- Pastikan URL yang diakses sudah benar
- Periksa apakah file yang diakses ada di folder yang benar
- Pastikan Apache sedang berjalan di XAMPP Control Panel

### 4. Gambar Tidak Muncul
- Periksa apakah folder `assets` sudah ada dan berisi file gambar
- Pastikan path relatif ke gambar sudah benar

### 5. Masalah Perhitungan Harga
- Pada halaman Bahan Baku dan Pesan Barang, sistem menggunakan perhitungan:
  - Total Harga = Qty × Harga Satuan
  - Jika input Total Harga langsung, maka Harga Satuan = Total Harga ÷ Qty
- Pastikan nilai yang dimasukkan valid dan tidak nol

### 6. Masalah Ekspor Data
- Pastikan semua dependencies terinstal dengan benar dengan mengunjungi `install_dependencies.php`
- Periksa bahwa konfigurasi PHP memungkinkan alokasi memori yang cukup
- Pastikan server web memiliki izin tulis yang sesuai

## Pengembangan Lanjutan

Jika Anda ingin mengembangkan aplikasi ini lebih lanjut:

### 1. Struktur Folder
- `assets/` - Berisi file CSS, JavaScript, dan gambar
- `config/` - Konfigurasi database dan fungsi helper
- `includes/` - Header dan footer yang digunakan di semua halaman
- `ajax/` - Endpoint untuk request AJAX
- `database/` - Berisi file SQL untuk impor database
- `vendor/` - Library pihak ketiga (dikelola oleh Composer)

### 2. Fitur yang Dapat Ditambahkan
- Barcode/QR code scanner untuk stok opname
- Notifikasi email untuk stok menipis
- Dashboard yang lebih interaktif
- Aplikasi mobile untuk akses lebih mudah
- Integrasi dengan sistem POS
- Sistem pembayaran online

### 3. Keamanan
- Selalu validasi input pengguna
- Gunakan prepared statements untuk mencegah SQL injection
- Update password secara berkala
- Batasi akses berdasarkan peran pengguna
- Implementasikan CSRF protection
- Aktifkan HTTPS

## Informasi Teknis

### Teknologi yang Digunakan
- PHP 8.0
- MySQL Database
- Tailwind CSS
- jQuery
- DataTables
- Font Awesome
- Select2
- TCPDF
- PhpSpreadsheet

### Keamanan
- Prepared Statements untuk mencegah SQL injection
- Password Hashing
- Validasi Input
- Session Management
- RBAC (Role-Based Access Control)

### Dukungan
Jika Anda memiliki pertanyaan atau masalah, silakan hubungi:
- Email: admin@example.com 