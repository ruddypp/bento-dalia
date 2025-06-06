# Alur Sistem Inventori

## Alur Proses Barang
Berikut adalah alur proses barang di sistem inventori:

### 1. Pemesanan Barang (pesan_barang.php)
- Admin membuat pesanan baru dengan memilih supplier.
- Admin menambahkan barang yang ingin dipesan beserta jumlah, periode, harga, dan lokasi.
- Setelah pesanan dibuat, status pesanan secara otomatis menjadi "pending".
- Sistem membuat entri di tabel bahan_baku dengan status "pending" untuk setiap barang yang dipesan, dengan referensi ke id_pesanan.

### 2. Verifikasi Bahan Baku (bahan_baku.php)
- Admin melihat daftar bahan baku yang perlu diverifikasi (status "pending").
- Saat admin mengubah status bahan baku menjadi "approved":
  - Sistem menambahkan jumlah barang ke stok di tabel barang.
  - Sistem mencatat barang masuk di tabel barang_masuk.
  - Sistem membuat atau mengupdate laporan barang masuk untuk hari tersebut.
  - Status pesanan terkait berubah menjadi "diproses" jika masih ada barang yang belum diverifikasi, atau "selesai" jika semua barang telah diverifikasi.

### 3. Retur Barang (retur_barang.php)
- Jika ada barang yang perlu diretur, admin dapat membuat retur dari bahan baku yang memiliki status "pending".
- Admin mengisi jumlah barang yang diretur dan alasan retur.
- Sistem:
  - Mengubah status bahan baku menjadi "retur".
  - Mencatat jumlah barang yang diretur (jumlah_retur) dan yang masuk ke stok (jumlah_masuk = qty - jumlah_retur).
  - Hanya menambahkan jumlah barang yang tidak diretur (jumlah_masuk) ke stok barang.
  - Membuat entri di tabel retur_barang untuk mencatat retur.
  - Membuat entri di tabel barang_masuk dan laporan_masuk untuk barang yang masuk ke stok (jika ada).
  - Mengupdate status pesanan terkait menjadi "diproses" atau "selesai" tergantung pada status bahan baku lainnya.

### 4. Laporan Barang Masuk (laporan_masuk.php)
- Sistem secara otomatis membuat laporan barang masuk harian.
- Setiap hari memiliki satu laporan untuk masing-masing periode.
- Laporan menampilkan ringkasan barang yang masuk pada hari tersebut:
  - Total transaksi
  - Total kuantitas
  - Total jenis barang
  - Detail per barang (nama, satuan, jumlah, harga rata-rata, lokasi, supplier)

## Status Pesanan
1. **pending** - Pesanan baru dibuat, belum diproses.
2. **diproses** - Beberapa barang dalam pesanan telah diverifikasi/diretur, tapi masih ada yang pending.
3. **selesai** - Semua barang dalam pesanan telah diverifikasi/diretur.
4. **dibatalkan** - Pesanan dibatalkan oleh admin.

## Status Bahan Baku
1. **pending** - Bahan baku baru ditambahkan, belum diverifikasi, belum masuk stok.
2. **approved** - Bahan baku telah diverifikasi dan ditambahkan ke stok.
3. **retur** - Sebagian atau seluruh bahan baku diretur, tidak masuk ke stok.

## Hubungan Antar Tabel
- **pesanan_barang** - Tabel utama untuk pesanan.
- **pesanan_detail** - Detail barang yang dipesan (terkait dengan pesanan_barang).
- **bahan_baku** - Status verifikasi barang masuk (terkait dengan pesanan_barang dan barang).
- **barang** - Stok barang yang tersedia.
- **barang_masuk** - Catatan barang yang masuk ke stok.
- **laporan_masuk** - Laporan harian barang masuk.
- **retur_barang** - Catatan barang yang diretur. 