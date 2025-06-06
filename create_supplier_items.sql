-- Create supplier_items table
CREATE TABLE IF NOT EXISTS supplier_items (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_supplier INT(11) NOT NULL,
    nama_item VARCHAR(100) NOT NULL,
    satuan VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_supplier) REFERENCES supplier(id_supplier) ON DELETE CASCADE
); 