-- Check if the laporan_masuk_detail table exists
CREATE TABLE IF NOT EXISTS `laporan_masuk_detail` (
  `id_detail_masuk` int(11) NOT NULL AUTO_INCREMENT,
  `id_laporan` int(11) DEFAULT NULL,
  `id_masuk` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_detail_masuk`),
  KEY `id_laporan` (`id_laporan`),
  KEY `id_masuk` (`id_masuk`),
  CONSTRAINT `laporan_masuk_detail_ibfk_1` FOREIGN KEY (`id_laporan`) REFERENCES `laporan_masuk` (`id_laporan_masuk`) ON DELETE CASCADE,
  CONSTRAINT `laporan_masuk_detail_ibfk_2` FOREIGN KEY (`id_masuk`) REFERENCES `barang_masuk` (`id_masuk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert records for existing laporan_masuk entries
INSERT INTO laporan_masuk_detail (id_laporan, id_masuk)
SELECT lm.id_laporan_masuk, bm.id_masuk
FROM laporan_masuk lm
JOIN barang_masuk bm ON DATE(lm.tanggal_laporan) = DATE(bm.tanggal_masuk)
WHERE NOT EXISTS (
    SELECT 1 FROM laporan_masuk_detail lmd 
    WHERE lmd.id_laporan = lm.id_laporan_masuk AND lmd.id_masuk = bm.id_masuk
);

-- Create a PHP script to run this SQL
-- Save this as fix_laporan_masuk_tables.php and run it from your browser 