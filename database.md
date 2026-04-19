# 🗄️ Database SQL — Happy Snack

> Copy query di bawah ini ke **phpMyAdmin → SQL** atau jalankan via terminal MySQL.

---

## 1. Buat Database

```sql
CREATE DATABASE IF NOT EXISTS `happy_snack`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `happy_snack`;
```

---

## 2. Tabel `user`

```sql
CREATE TABLE `user` (
  `id_user`    INT(11)      NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `telepon`    VARCHAR(20)  DEFAULT NULL,
  `role`       ENUM('pembeli','admin') NOT NULL DEFAULT 'pembeli',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3. Tabel `kategori`

```sql
CREATE TABLE `kategori` (
  `id_kategori` INT(11)      NOT NULL AUTO_INCREMENT,
  `nama`        VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Tabel `produk`

```sql
CREATE TABLE `produk` (
  `id_produk`   INT(11)        NOT NULL AUTO_INCREMENT,
  `id_kategori` INT(11)        NOT NULL,
  `nama`        VARCHAR(150)   NOT NULL,
  `harga`       DECIMAL(10,2)  NOT NULL,
  `satuan`      VARCHAR(20)    NOT NULL DEFAULT 'pcs',
  `stok`        INT(11)        NOT NULL DEFAULT 0,
  `deskripsi`   TEXT           DEFAULT NULL,
  `gambar`      VARCHAR(255)   DEFAULT NULL,
  `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produk`),
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori`(`id_kategori`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Tabel `varian_produk`

```sql
CREATE TABLE `varian_produk` (
  `id_varian`  INT(11)      NOT NULL AUTO_INCREMENT,
  `id_produk`  INT(11)      NOT NULL,
  `nama`       VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_varian`),
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 6. Tabel `pesanan`

```sql
CREATE TABLE `pesanan` (
  `id_pesanan`  INT(11)       NOT NULL AUTO_INCREMENT,
  `id_user`     INT(11)       NOT NULL,
  `kode`        VARCHAR(20)   NOT NULL UNIQUE,
  `nama`        VARCHAR(100)  NOT NULL,
  `telepon`     VARCHAR(20)   NOT NULL,
  `alamat`      TEXT          NOT NULL,
  `kota`        VARCHAR(100)  NOT NULL,
  `kode_pos`    VARCHAR(10)   NOT NULL,
  `kurir`       VARCHAR(50)   NOT NULL,
  `pembayaran`  VARCHAR(50)   NOT NULL,
  `subtotal`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  `ongkir`      DECIMAL(10,2) NOT NULL DEFAULT 0,
  `diskon`      DECIMAL(10,2) NOT NULL DEFAULT 0,
  `total`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status`      ENUM('pending','diproses','dikemas','dikirim','selesai') NOT NULL DEFAULT 'pending',
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pesanan`),
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 7. Tabel `detail_pesanan`

```sql
CREATE TABLE `detail_pesanan` (
  `id_detail`  INT(11)       NOT NULL AUTO_INCREMENT,
  `id_pesanan` INT(11)       NOT NULL,
  `id_produk`  INT(11)       NOT NULL,
  `varian`     VARCHAR(100)  DEFAULT NULL,
  `jumlah`     INT(11)       NOT NULL DEFAULT 1,
  `harga`      DECIMAL(10,2) NOT NULL,
  `subtotal`   DECIMAL(12,2) NOT NULL,
  `catatan`    TEXT          DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan`(`id_pesanan`) ON DELETE CASCADE,
  FOREIGN KEY (`id_produk`)  REFERENCES `produk`(`id_produk`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 8. Tabel `keranjang`

```sql
CREATE TABLE `keranjang` (
  `id_keranjang` INT(11)      NOT NULL AUTO_INCREMENT,
  `id_user`      INT(11)      NOT NULL,
  `id_produk`    INT(11)      NOT NULL,
  `varian`       VARCHAR(100) DEFAULT NULL,
  `jumlah`       INT(11)      NOT NULL DEFAULT 1,
  `catatan`      TEXT         DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_keranjang`),
  FOREIGN KEY (`id_user`)   REFERENCES `user`(`id_user`)     ON DELETE CASCADE,
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 9. Tabel `ulasan`

```sql
CREATE TABLE `ulasan` (
  `id_ulasan`  INT(11)  NOT NULL AUTO_INCREMENT,
  `id_user`    INT(11)  NOT NULL,
  `id_produk`  INT(11)  NOT NULL,
  `id_pesanan` INT(11)  DEFAULT NULL,
  `rating`     TINYINT  NOT NULL DEFAULT 5,
  `komentar`   TEXT     DEFAULT NULL,
  `foto`       VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ulasan`),
  FOREIGN KEY (`id_user`)    REFERENCES `user`(`id_user`)       ON DELETE CASCADE,
  FOREIGN KEY (`id_produk`)  REFERENCES `produk`(`id_produk`)   ON DELETE CASCADE,
  FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan`(`id_pesanan`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 10. Tabel `voucher`

```sql
CREATE TABLE `voucher` (
  `id_voucher` INT(11)       NOT NULL AUTO_INCREMENT,
  `kode`       VARCHAR(50)   NOT NULL UNIQUE,
  `diskon`     DECIMAL(10,2) NOT NULL DEFAULT 0,
  `min_belanja`DECIMAL(12,2) NOT NULL DEFAULT 0,
  `aktif`      TINYINT(1)    NOT NULL DEFAULT 1,
  `expired_at` DATE          DEFAULT NULL,
  PRIMARY KEY (`id_voucher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 11. Data Awal (Seed)

### Insert Kategori

```sql
INSERT INTO `kategori` (`nama`) VALUES
  ('Snack Kering'),
  ('Kue Kering');
```

### Insert Admin

```sql
-- Password: admin123 (sudah di-hash dengan password_hash)
INSERT INTO `user` (`nama`, `email`, `password`, `telepon`, `role`) VALUES
  ('Admin', 'admin@happysnack.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '08123456789', 'admin');
```

> ⚠️ Hash di atas adalah untuk password `password`. Ganti dengan hash baru:
> ```php
> echo password_hash('admin123', PASSWORD_DEFAULT);
> ```

### Insert Produk

```sql
INSERT INTO `produk` (`id_kategori`, `nama`, `harga`, `satuan`, `stok`, `deskripsi`) VALUES
  (1, 'Basreng',         15000, '250g', 50, 'Basreng (Baso Goreng) renyah dengan bumbu pedas khas. Homemade tanpa pengawet.'),
  (1, 'Kripik Kaca',     10000, '250g', 50, 'Kripik Kaca tipis dan super renyah. Tanpa pengawet.'),
  (1, 'Seblak Kering',   15000, '250g', 50, 'Seblak Kering dengan cita rasa khas Bandung. Pedas gurih.'),
  (1, 'Makaroni Kering',  7000, '250g', 50, 'Makaroni Kering renyah dan gurih. Cocok untuk camilan.'),
  (2, 'Brownis',         20000, '250g', 50, 'Brownis lembut dengan coklat premium. Freshly baked, tanpa pengawet.'),
  (2, 'Cookies',          5000, 'pcs',  50, 'Soft Cookies tekstur lembut dan chewy. Freshly Baked, baru dipanggang ketika ada pesanan masuk.'),
  (2, 'Kastengel',       35000, '250g', 50, 'Kastengel keju premium, renyah dan gurih.'),
  (2, 'Nastar',          35000, '250g', 50, 'Nastar dengan selai nanas asli, lembut dan harum.');
```

### Insert Varian Produk

```sql
INSERT INTO `varian_produk` (`id_produk`, `nama`) VALUES
  (1, 'Original'), (1, 'Pedas'), (1, 'Pedas Daun Jeruk'),
  (2, 'Original'), (2, 'Balado'), (2, 'Keju'),
  (3, 'Level 1'),  (3, 'Level 2'), (3, 'Level 3'),
  (4, 'Original'), (4, 'Pedas'),  (4, 'BBQ'),
  (5, 'Dark Chocolate'), (5, 'Milk Chocolate'), (5, 'Matcha'),
  (6, 'Matcha'), (6, 'Chocolate'), (6, 'Redvelvet'),
  (7, 'Original'), (7, 'Extra Keju'),
  (8, 'Original');
```

### Insert Voucher

```sql
INSERT INTO `voucher` (`kode`, `diskon`, `min_belanja`, `aktif`, `expired_at`) VALUES
  ('HAPPYSNACK', 5000,  20000, 1, '2026-12-31'),
  ('NEWUSER',    10000, 30000, 1, '2026-06-30'),
  ('GRATIS',     15000, 50000, 1, '2026-03-31');
```

---

## 12. Query Berguna

### Lihat semua produk beserta kategori

```sql
SELECT p.id_produk, p.nama, k.nama AS kategori, p.harga, p.satuan, p.stok
FROM produk p
JOIN kategori k ON p.id_kategori = k.id_kategori
ORDER BY k.id_kategori, p.nama;
```

### Lihat pesanan beserta nama pembeli

```sql
SELECT ps.kode, u.nama AS pembeli, ps.total, ps.status, ps.created_at
FROM pesanan ps
JOIN user u ON ps.id_user = u.id_user
ORDER BY ps.created_at DESC;
```

### Total penjualan (pesanan selesai)

```sql
SELECT
  COUNT(*) AS total_pesanan,
  SUM(total) AS total_penjualan
FROM pesanan
WHERE status = 'selesai';
```

### Rating rata-rata per produk

```sql
SELECT p.nama, ROUND(AVG(u.rating), 1) AS rating, COUNT(u.id_ulasan) AS jumlah_ulasan
FROM produk p
LEFT JOIN ulasan u ON p.id_produk = u.id_produk
GROUP BY p.id_produk
ORDER BY rating DESC;
```

### Isi keranjang user tertentu

```sql
SELECT k.id_keranjang, p.nama, k.varian, k.jumlah, p.harga,
       (k.jumlah * p.harga) AS subtotal
FROM keranjang k
JOIN produk p ON k.id_produk = p.id_produk
WHERE k.id_user = 1;
```

---

## 13. Cara Import

### Via phpMyAdmin
1. Buka `http://localhost/phpmyadmin`
2. Klik tab **SQL**
3. Copy semua query dari bagian **1 s/d 11**
4. Klik **Go**

### Via Terminal (XAMPP)
```bash
mysql -u root -p happy_snack < database.sql
```

> Atau buat file `database.sql` terpisah berisi semua query di atas.

---

## 14. Struktur Relasi

```
user ──────────┬── pesanan ──── detail_pesanan ──── produk
               │                                      │
               ├── keranjang ──────────────────────── │
               │                                      │
               └── ulasan ────────────────────────────┘
                                                       │
                                              kategori ┘
                                              varian_produk
```
