-- ============================================
-- Happy Snack - Database Setup
-- Import file ini ke phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS `happy_snack`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `happy_snack`;

-- ============================================
-- Tabel kategori
-- ============================================
CREATE TABLE IF NOT EXISTS `kategori` (
  `id_kategori` INT(11)      NOT NULL AUTO_INCREMENT,
  `nama`        VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel produk
-- ============================================
CREATE TABLE IF NOT EXISTS `produk` (
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

-- ============================================
-- Tabel user
-- ============================================
CREATE TABLE IF NOT EXISTS `user` (
  `id_user`    INT(11)      NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `telepon`    VARCHAR(20)  DEFAULT NULL,
  `role`       ENUM('pembeli','admin') NOT NULL DEFAULT 'pembeli',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel pesanan
-- ============================================
CREATE TABLE IF NOT EXISTS `pesanan` (
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
  `status`      ENUM('pending','diproses','dikemas','dikirim','selesai','ditolak') NOT NULL DEFAULT 'pending',
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pesanan`),
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel detail_pesanan
-- ============================================
CREATE TABLE IF NOT EXISTS `detail_pesanan` (
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

-- ============================================
-- Tabel keranjang
-- ============================================
CREATE TABLE IF NOT EXISTS `keranjang` (
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

-- ============================================
-- Tabel varian_produk
-- ============================================
CREATE TABLE IF NOT EXISTS `varian_produk` (
  `id_varian`  INT(11)      NOT NULL AUTO_INCREMENT,
  `id_produk`  INT(11)      NOT NULL,
  `nama`       VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_varian`),
  FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id_produk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel ulasan
-- ============================================
CREATE TABLE IF NOT EXISTS `ulasan` (
  `id_ulasan`  INT(11)      NOT NULL AUTO_INCREMENT,
  `id_user`    INT(11)      NOT NULL,
  `id_produk`  INT(11)      NOT NULL,
  `id_pesanan` INT(11)      DEFAULT NULL,
  `rating`     TINYINT      NOT NULL DEFAULT 5,
  `komentar`   TEXT         DEFAULT NULL,
  `foto`       VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ulasan`),
  FOREIGN KEY (`id_user`)    REFERENCES `user`(`id_user`)       ON DELETE CASCADE,
  FOREIGN KEY (`id_produk`)  REFERENCES `produk`(`id_produk`)   ON DELETE CASCADE,
  FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan`(`id_pesanan`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel voucher
-- ============================================
CREATE TABLE IF NOT EXISTS `voucher` (
  `id_voucher`  INT(11)       NOT NULL AUTO_INCREMENT,
  `kode`        VARCHAR(50)   NOT NULL UNIQUE,
  `diskon`      DECIMAL(10,2) NOT NULL DEFAULT 0,
  `min_belanja` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `aktif`       TINYINT(1)    NOT NULL DEFAULT 1,
  `expired_at`  DATE          DEFAULT NULL,
  PRIMARY KEY (`id_voucher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel pembayaran
-- ============================================
CREATE TABLE IF NOT EXISTS `pembayaran` (
  `id_pembayaran`  INT(11)       NOT NULL AUTO_INCREMENT,
  `id_pesanan`     INT(11)       NOT NULL,
  `metode`         ENUM('transfer_bank','cod','e_wallet') NOT NULL DEFAULT 'transfer_bank',
  `bank`           VARCHAR(50)   DEFAULT NULL,
  `no_rekening`    VARCHAR(50)   DEFAULT NULL,
  `nama_pengirim`  VARCHAR(100)  DEFAULT NULL,
  `jumlah_bayar`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `bukti_transfer` VARCHAR(255)  DEFAULT NULL,
  `status`         ENUM('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  `catatan`        TEXT          DEFAULT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at`    TIMESTAMP     NULL DEFAULT NULL,
  PRIMARY KEY (`id_pembayaran`),
  UNIQUE KEY `uk_pesanan` (`id_pesanan`),
  FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan`(`id_pesanan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel chat_messages (Live Chat)
-- ============================================
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `id_user`    INT(11)      NOT NULL,
  `pengirim`   ENUM('user','admin') NOT NULL DEFAULT 'user',
  `pesan`      TEXT         NOT NULL,
  `dibaca`     TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Data Awal: Kategori
-- ============================================
INSERT INTO `kategori` (`nama`) VALUES
  ('Snack Kering'),
  ('Kue Kering');

-- ============================================
-- Data Awal: Admin
-- Password: admin123
-- ============================================
INSERT INTO `user` (`nama`, `email`, `password`, `telepon`, `role`) VALUES
  ('Admin', 'admin@happysnack.com',
   '$2y$10$TKh8H1.PfunDStrTRBi0OeGHBHFBHBHFBHBHFBHBHFBHBHFBHBHFB',
   '08123456789', 'admin');

-- ============================================
-- Data Awal: Produk
-- ============================================
INSERT INTO `produk` (`id_kategori`, `nama`, `harga`, `satuan`, `stok`, `deskripsi`) VALUES
  (1, 'Basreng',         15000, '250g', 50, 'Basreng (Baso Goreng) renyah dengan bumbu pedas khas. Homemade tanpa pengawet.'),
  (1, 'Kripik Kaca',     10000, '250g', 50, 'Kripik Kaca tipis dan super renyah. Tanpa pengawet.'),
  (1, 'Seblak Kering',   15000, '250g', 50, 'Seblak Kering dengan cita rasa khas Bandung. Pedas gurih.'),
  (1, 'Makaroni Kering',  7000, '250g', 50, 'Makaroni Kering renyah dan gurih. Cocok untuk camilan.'),
  (2, 'Brownis',         20000, '250g', 50, 'Brownis lembut dengan coklat premium. Freshly baked, tanpa pengawet.'),
  (2, 'Cookies',          5000, 'pcs',  50, 'Soft Cookies tekstur lembut dan chewy. Freshly Baked setiap hari.'),
  (2, 'Kastengel',       35000, '250g', 50, 'Kastengel keju premium, renyah dan gurih.'),
  (2, 'Nastar',          35000, '250g', 50, 'Nastar dengan selai nanas asli, lembut dan harum.');

-- ============================================
-- Data Awal: Varian Produk
-- ============================================
INSERT INTO `varian_produk` (`id_produk`, `nama`) VALUES
  (1, 'Original'), (1, 'Pedas'), (1, 'Pedas Daun Jeruk'),
  (2, 'Original'), (2, 'Balado'), (2, 'Keju'),
  (3, 'Level 1'),  (3, 'Level 2'), (3, 'Level 3'),
  (4, 'Original'), (4, 'Pedas'),  (4, 'BBQ'),
  (5, 'Dark Chocolate'), (5, 'Milk Chocolate'), (5, 'Matcha'),
  (6, 'Matcha'), (6, 'Chocolate'), (6, 'Redvelvet'),
  (7, 'Original'), (7, 'Extra Keju'),
  (8, 'Original');

-- ============================================
-- Data Awal: Voucher
-- ============================================
INSERT INTO `voucher` (`kode`, `diskon`, `min_belanja`, `aktif`, `expired_at`) VALUES
  ('HAPPYSNACK', 5000,  20000, 1, '2026-12-31'),
  ('NEWUSER',    10000, 30000, 1, '2026-12-31'),
  ('GRATIS',     15000, 50000, 1, '2026-12-31');
