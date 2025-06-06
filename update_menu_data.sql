-- Query untuk mengupdate data menu yang sudah ada
UPDATE menu SET 
  harga_modal = CASE 
    WHEN id_menu = 1 THEN 8000.00  -- Mie Ayam
    WHEN id_menu = 2 THEN 12000.00 -- Kopi
    ELSE harga_modal 
  END,
  keuntungan = harga - CASE 
    WHEN id_menu = 1 THEN 8000.00  -- Mie Ayam
    WHEN id_menu = 2 THEN 12000.00 -- Kopi
    ELSE harga_modal 
  END;

-- Query untuk melihat data menu setelah diupdate
SELECT id_menu, nama_menu, kategori, harga, harga_modal, keuntungan, 
       ROUND((keuntungan/harga_modal)*100, 2) AS persentase_keuntungan 
FROM menu;

-- Query untuk mengupdate bahan dengan format yang benar
UPDATE menu SET 
  bahan = CASE 
    WHEN id_menu = 1 THEN 'mie:1, ayam:0.5, sayur:0.25'  -- Mie Ayam
    WHEN id_menu = 2 THEN 'kopi:0.5, susu:0.3, gula:0.2' -- Kopi
    ELSE bahan 
  END;

-- Query untuk menambahkan menu baru dengan harga modal dan keuntungan
INSERT INTO menu (nama_menu, kategori, harga, bahan, deskripsi, foto, harga_modal, keuntungan) VALUES
('Nasi Goreng', 'makanan', 15000.00, 'nasi:1, telur:1, bumbu:0.5', 'Nasi goreng spesial dengan telur', NULL, 7500.00, 7500.00),
('Es Teh', 'minuman', 5000.00, 'teh:0.1, gula:0.2', 'Es teh manis segar', NULL, 2000.00, 3000.00);

-- Query untuk menghitung ulang keuntungan berdasarkan harga jual dan harga modal
UPDATE menu SET keuntungan = harga - harga_modal WHERE keuntungan != harga - harga_modal;