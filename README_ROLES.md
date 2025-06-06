# Sistem Manajemen Akses Berbasis Role (RBAC)

## Daftar Role dan Akses

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

## Implementasi Sistem RBAC

### Struktur Database
- Tabel `roles`: Menyimpan daftar role yang tersedia dalam sistem
- Tabel `users`: Memiliki kolom `role_id` yang terhubung ke tabel `roles`

### File Konfigurasi
- `config/functions.php`: Berisi fungsi `checkPermission()` untuk memeriksa hak akses
- `role_permission_check.php`: File yang harus disertakan di setiap halaman untuk memeriksa akses

### Cara Penggunaan
1. Sertakan file `role_permission_check.php` di awal setiap halaman:
   ```php
   <?php
   require_once 'role_permission_check.php';
   ?>
   ```

2. Untuk membatasi akses ke tombol edit/hapus pada halaman view-only:
   ```php
   <?php if (!isset($VIEW_ONLY) || $VIEW_ONLY !== true): ?>
   <!-- Tombol edit/hapus di sini -->
   <?php endif; ?>
   ```

3. Untuk menjalankan update role pengguna:
   - Akses file `update_roles.php` untuk mengupdate role_id pada tabel users

### Penanganan Error
- Jika pengguna mencoba mengakses halaman yang tidak diizinkan, mereka akan diarahkan ke halaman dashboard dengan pesan error.

## Cara Menggunakan

### Menambahkan Cek Permission di Halaman Baru

```php
<?php
// Include role permission check
require_once 'role_permission_check.php';

// Jika hanya view only, sembunyikan tombol add/edit/delete
if (isset($VIEW_ONLY) && $VIEW_ONLY === true) {
    // Kode untuk menyembunyikan tombol add/edit/delete
}
?>
```

### Mengupdate Role User

Untuk mengupdate role user, jalankan file `update_roles.php` atau update manual di database:

```sql
UPDATE users SET role_id = [id_role] WHERE id_user = [user_id];
```

## Catatan Penting

- Pastikan setiap halaman baru menyertakan `role_permission_check.php`
- Gunakan fungsi `hasEditPermission()` untuk mengecek akses edit/delete di halaman
- Jika menambahkan role baru, update fungsi `checkPermission()` di `functions.php` 