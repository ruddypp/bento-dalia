# Sistem Manajemen Inventori

Aplikasi Sistem Inventori untuk mengelola stok barang, supplier, penerimaan, pengeluaran, stok opname, dan retur barang. Dibangun menggunakan PHP native, MySQL, dan Tailwind CSS.

## Panduan Lengkap untuk Pemula

### Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (XAMPP, WAMP, atau Laragon)
- Browser modern (Chrome, Firefox, Edge)

### Instalasi untuk Pemula

#### 1. Instalasi XAMPP

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

#### 2. Mengunduh dan Menyiapkan Aplikasi

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

#### 3. Menjalankan Aplikasi

1. **Akses Aplikasi**
   - Buka browser dan ketik: `http://localhost/inventory`
   - Anda akan diarahkan ke halaman login

2. **Login ke Sistem**
   - Username: `admin`
   - Password: `admin123`

3. **Reset Password Admin (Jika Diperlukan)**
   - Jika lupa password admin, akses: `http://localhost/inventory/reset_admin.php`
   - Ikuti petunjuk untuk reset password admin

### Penggunaan Aplikasi

#### 1. Dashboard
- Halaman utama berisi ringkasan data stok dan aktivitas terbaru
- Notifikasi stok menipis akan ditampilkan jika ada
- Grafik perbandingan penerimaan dan pengeluaran barang

#### 2. Manajemen Barang
- **Menambah Barang**:
  - Klik menu "Barang" di sidebar
  - Klik tombol "Tambah Barang"
  - Isi form dengan lengkap
  - Klik "Simpan"

- **Mengedit/Menghapus Barang**:
  - Klik menu "Barang" di sidebar
  - Cari barang yang ingin diubah
  - Klik ikon edit (pensil) atau hapus (tempat sampah)

#### 3. Manajemen Supplier
- **Menambah Supplier**:
  - Klik menu "Supplier" di sidebar
  - Klik tombol "Tambah Supplier"
  - Isi form dengan lengkap
  - Klik "Simpan"

- **Mengedit/Menghapus Supplier**:
  - Klik menu "Supplier" di sidebar
  - Cari supplier yang ingin diubah
  - Klik ikon edit (pensil) atau hapus (tempat sampah)

#### 4. Penerimaan Barang
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

#### 5. Pengeluaran Barang
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

#### 6. Stok Opname
- **Melakukan Stok Opname**:
  - Klik menu "Stok Opname" di sidebar
  - Klik tombol "Tambah Stok Opname"
  - Pilih barang dan masukkan stok fisik
  - Sistem akan otomatis menghitung selisih
  - Klik "Simpan"

#### 7. Retur Barang
- **Mencatat Retur**:
  - Klik menu "Retur Barang" di sidebar
  - Klik tombol "Tambah Retur"
  - Pilih penerimaan yang akan diretur
  - Isi alasan retur
  - Klik "Simpan"

#### 8. Manajemen Pengguna
- **Menambah Pengguna**:
  - Klik menu "Pengguna" di sidebar
  - Klik tombol "Tambah Pengguna"
  - Isi form lengkap dengan nama, username, password, email, dan peran
  - Klik "Simpan"

- **Mengubah Peran Pengguna**:
  - Klik menu "Pengguna" di sidebar
  - Cari pengguna yang ingin diubah
  - Klik ikon edit (pensil)
  - Ubah peran pengguna
  - Klik "Simpan"

#### 9. Log Aktivitas
- Klik menu "Log Aktivitas" di sidebar
- Lihat semua aktivitas pengguna yang tercatat di sistem
- Filter berdasarkan tanggal atau pengguna

#### 10. Data Toko
- Klik menu "Data Toko" di sidebar
- Update informasi toko seperti nama, alamat, dan kontak
- Klik "Simpan" untuk menyimpan perubahan

### Pemecahan Masalah Umum

#### 1. Error "Headers Already Sent"
- Pastikan file PHP tidak memiliki spasi atau karakter di awal file
- Pastikan `ob_start()` ada di awal file `header.php`
- Pastikan `ob_end_flush()` ada di akhir file `footer.php`
- Pastikan setiap file PHP yang menyertakan footer.php memiliki `ob_end_flush()` di akhir file

#### 2. Koneksi Database Gagal
- Pastikan MySQL berjalan di XAMPP Control Panel
- Periksa pengaturan di `config/database.php`
- Pastikan nama database, username, dan password sudah benar

#### 3. Halaman Tidak Ditemukan
- Pastikan URL yang diakses sudah benar
- Periksa apakah file yang diakses ada di folder yang benar
- Pastikan Apache sedang berjalan di XAMPP Control Panel

#### 4. Gambar Tidak Muncul
- Periksa apakah folder `assets` sudah ada dan berisi file gambar
- Pastikan path relatif ke gambar sudah benar

### Pengembangan Lanjutan

Jika Anda ingin mengembangkan aplikasi ini lebih lanjut:

1. **Struktur Folder**
   - `assets/` - Berisi file CSS, JavaScript, dan gambar
   - `config/` - Konfigurasi database dan fungsi helper
   - `includes/` - Header dan footer yang digunakan di semua halaman
   - `ajax/` - Endpoint untuk request AJAX
   - `database/` - Berisi file SQL untuk impor database

2. **Fitur yang Dapat Ditambahkan**
   - Laporan dalam format PDF
   - Export data ke Excel
   - Barcode/QR code scanner untuk stok opname
   - Notifikasi email untuk stok menipis
   - Dashboard yang lebih interaktif

3. **Keamanan**
   - Selalu validasi input pengguna
   - Gunakan prepared statements untuk mencegah SQL injection
   - Update password secara berkala
   - Batasi akses berdasarkan peran pengguna

## Informasi Teknis

### Teknologi yang Digunakan
- PHP 8.0
- MySQL Database
- Tailwind CSS
- jQuery
- DataTables
- Font Awesome
- Select2

### Keamanan
- Prepared Statements untuk mencegah SQL Injection
- Password Hashing
- Validasi Input
- Session Management
- CSRF Protection

### Dukungan
Jika Anda memiliki pertanyaan atau masalah, silakan hubungi:
- Email: admin@example.com

# Inventory Management System - Export Features

## Export to PDF and Excel

This system now supports exporting data tables to PDF and Excel formats. The feature has been implemented for the Bahan Baku (Raw Materials) module.

## Requirements

The export functionality requires the following PHP libraries:
- TCPDF for PDF generation
- PhpSpreadsheet for Excel generation

## Installation

### Using Composer (Recommended)

1. Make sure you have Composer installed. If not, download it from [getcomposer.org](https://getcomposer.org/).

2. Navigate to the project directory and run:
   ```
   composer install
   ```

3. This will install the required dependencies based on the composer.json file.

### Manual Installation

If you can't use Composer, you can:

1. Visit the installation helper page at `http://localhost/inventory/install_dependencies.php`
2. Follow the instructions to set up the required libraries.

## Usage

1. Go to the Bahan Baku page (`bahan_baku.php`).
2. Click on the "Export" dropdown button.
3. Choose either PDF or Excel format.
4. The file will be generated and downloaded to your device.

## Filtering

The export will respect any filters you have applied to the data table. For example, if you filter by a specific period, only data from that period will be included in the exported file.

## Troubleshooting

If you encounter issues with the export feature:

1. Make sure all dependencies are properly installed by visiting `install_dependencies.php`.
2. Check that your PHP configuration allows for large memory allocation, as generating large reports may require additional memory.
3. Ensure the web server has proper write permissions.
4. If PDF export has issues with special characters, check that the TCPDF library is correctly handling your character encoding.

## Developer Notes

The export functionality is implemented in `export_bahan_baku.php`. If you need to customize the export format or add additional fields, modify this file.

To add export functionality to other modules:
1. Create a similar export handler for the specific module
2. Add the export dropdown to the module's page
3. Ensure the query and formatting are appropriate for that module's data structure