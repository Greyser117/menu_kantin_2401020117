USE menu_kantin_2401020117;

-- Insert Data Master Kategori
INSERT INTO kategori_menu (nama_kategori) VALUES
('Makanan'),
('Minuman');

-- Insert Data Awal Wajib
INSERT INTO menu (kode_menu, nama_menu, barcode, harga, kategori_menu_id) VALUES
('MN00000001', 'Nasi Goreng', 'BR-MKN-0001', 18000, 1),
('MN00000002', 'Es Teh', 'BR-MNM-0001', 5000, 2),
('MN00000003', 'Mie Rebus', 'BR-MKN-0002', 15000, 1);