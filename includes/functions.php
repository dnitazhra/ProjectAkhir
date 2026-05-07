<?php
/**
 * Helper functions — lavo.id
 */

// ── Produk ──────────────────────────────────────────────

function getProdukAll($conn, $limit = 0) {
    $sql = "SELECT p.id_produk AS id, p.nama, p.harga, p.satuan, p.stok, p.gambar,
                   k.nama AS kategori
            FROM produk p
            JOIN kategori k ON p.id_kategori = k.id_kategori
            ORDER BY k.id_kategori, p.nama";
    if ($limit > 0) $sql .= " LIMIT $limit";
    $res = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    return $data;
}

function getProdukById($conn, $id) {
    $stmt = mysqli_prepare($conn,
        "SELECT p.*, k.nama AS kategori
         FROM produk p
         JOIN kategori k ON p.id_kategori = k.id_kategori
         WHERE p.id_produk = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row;
}

function getVarianProduk($conn, $id_produk) {
    $stmt = mysqli_prepare($conn,
        "SELECT nama FROM varian_produk WHERE id_produk = ? ORDER BY id_varian");
    mysqli_stmt_bind_param($stmt, 'i', $id_produk);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row['nama'];
    mysqli_stmt_close($stmt);
    return $data;
}

function getProdukFilter($conn, $kategori = 'Semua', $search = '') {
    $where  = [];
    $params = [];
    $types  = '';

    if ($kategori !== 'Semua') {
        $where[]  = "k.nama = ?";
        $params[] = $kategori;
        $types   .= 's';
    }
    if ($search !== '') {
        $like     = "%$search%";
        $where[]  = "p.nama LIKE ?";
        $params[] = $like;
        $types   .= 's';
    }

    $sql = "SELECT p.id_produk AS id, p.nama, p.harga, p.satuan, p.stok, p.gambar,
                   k.nama AS kategori
            FROM produk p
            JOIN kategori k ON p.id_kategori = k.id_kategori";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY k.id_kategori, p.nama";

    $stmt = mysqli_prepare($conn, $sql);
    if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    mysqli_stmt_close($stmt);
    return $data;
}

function getUlasanProduk($conn, $id_produk, $limit = 5) {
    $stmt = mysqli_prepare($conn,
        "SELECT u.rating, u.komentar, u.foto, u.created_at, usr.nama
         FROM ulasan u
         JOIN user usr ON u.id_user = usr.id_user
         WHERE u.id_produk = ?
         ORDER BY u.created_at DESC
         LIMIT ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id_produk, $limit);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    mysqli_stmt_close($stmt);
    return $data;
}

function getRatingRataRata($conn, $id_produk) {
    $stmt = mysqli_prepare($conn,
        "SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total
         FROM ulasan WHERE id_produk = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_produk);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row;
}

// ── Kategori ────────────────────────────────────────────

function getKategoriAll($conn) {
    $res  = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id_kategori");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    return $data;
}

function getKategoriCount($conn) {
    $res    = mysqli_query($conn,
        "SELECT k.nama, COUNT(p.id_produk) AS jml
         FROM kategori k
         LEFT JOIN produk p ON k.id_kategori = p.id_kategori
         GROUP BY k.id_kategori");
    $counts = ['Semua' => 0];
    while ($row = mysqli_fetch_assoc($res)) {
        $counts[$row['nama']] = (int)$row['jml'];
        $counts['Semua']     += (int)$row['jml'];
    }
    return $counts;
}

// ── Keranjang ───────────────────────────────────────────

function getKeranjang($conn, $id_user) {
    $stmt = mysqli_prepare($conn,
        "SELECT k.id_keranjang, k.varian, k.jumlah, k.catatan,
                p.id_produk, p.nama, p.harga, p.satuan, p.gambar
         FROM keranjang k
         JOIN produk p ON k.id_produk = p.id_produk
         WHERE k.id_user = ?
         ORDER BY k.created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    mysqli_stmt_close($stmt);
    return $data;
}

function getCartCount($conn, $id_user) {
    $stmt = mysqli_prepare($conn,
        "SELECT COALESCE(SUM(jumlah),0) FROM keranjang WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (int)$count;
}

// ── Pesanan ─────────────────────────────────────────────

function generateKodePesanan() {
    return 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function buatPesanan($conn, $data) {
    $kode = generateKodePesanan();
    // 13 placeholder: i s s s s s s s s d d d d
    // id_user, kode, nama, telepon, alamat, kota, kode_pos,
    // kurir, pembayaran, subtotal, ongkir, diskon, total
    $stmt = mysqli_prepare($conn,
        "INSERT INTO pesanan
         (id_user, kode, nama, telepon, alamat, kota, kode_pos,
          kurir, pembayaran, subtotal, ongkir, diskon, total, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
    mysqli_stmt_bind_param($stmt, 'issssssssdddd',
        $data['id_user'], $kode,          $data['nama'],      $data['telepon'],
        $data['alamat'],  $data['kota'],  $data['kode_pos'],
        $data['kurir'],   $data['pembayaran'],
        $data['subtotal'],$data['ongkir'],$data['diskon'],     $data['total']
    );
    mysqli_stmt_execute($stmt);
    $id_pesanan = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return ['id' => $id_pesanan, 'kode' => $kode];
}

function insertDetailPesanan($conn, $id_pesanan, $items) {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO detail_pesanan (id_pesanan, id_produk, varian, jumlah, harga, subtotal, catatan)
         VALUES (?,?,?,?,?,?,?)");
    foreach ($items as $item) {
        // id_pesanan(i), id_produk(i), varian(s), jumlah(i), harga(d), subtotal(d), catatan(s)
        $subtotal = $item['harga'] * $item['jumlah'];
        mysqli_stmt_bind_param($stmt, 'iisidds',
            $id_pesanan, $item['id_produk'], $item['varian'],
            $item['jumlah'], $item['harga'], $subtotal, $item['catatan']
        );
        mysqli_stmt_execute($stmt);
        // Kurangi stok
        $s2 = mysqli_prepare($conn,
            "UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");
        mysqli_stmt_bind_param($s2, 'iii', $item['jumlah'], $item['id_produk'], $item['jumlah']);
        mysqli_stmt_execute($s2);
        mysqli_stmt_close($s2);
    }
    mysqli_stmt_close($stmt);
}

function kosongkanKeranjang($conn, $id_user) {
    $stmt = mysqli_prepare($conn, "DELETE FROM keranjang WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function getPesananUser($conn, $id_user) {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM pesanan WHERE id_user = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    mysqli_stmt_close($stmt);
    return $data;
}

function getPesananById($conn, $kode, $id_user = null) {
    if ($id_user) {
        $stmt = mysqli_prepare($conn,
            "SELECT * FROM pesanan WHERE kode = ? AND id_user = ?");
        mysqli_stmt_bind_param($stmt, 'si', $kode, $id_user);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM pesanan WHERE kode = ?");
        mysqli_stmt_bind_param($stmt, 's', $kode);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row;
}

function getDetailPesanan($conn, $id_pesanan) {
    $stmt = mysqli_prepare($conn,
        "SELECT dp.*, p.nama, p.gambar
         FROM detail_pesanan dp
         JOIN produk p ON dp.id_produk = p.id_produk
         WHERE dp.id_pesanan = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_pesanan);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    mysqli_stmt_close($stmt);
    return $data;
}

// ── Voucher ─────────────────────────────────────────────

function cekVoucher($conn, $kode, $subtotal) {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM voucher
         WHERE kode = ? AND aktif = 1
         AND (expired_at IS NULL OR expired_at >= CURDATE())
         AND min_belanja <= ?");
    mysqli_stmt_bind_param($stmt, 'sd', $kode, $subtotal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row;
}

// ── Admin ───────────────────────────────────────────────

function getStatsAdmin($conn) {
    $total_penjualan = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COALESCE(SUM(total),0) FROM pesanan WHERE status='selesai'"))[0];
    $pesanan_baru    = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM pesanan WHERE status='pending'"))[0];
    $produk_aktif    = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM produk WHERE stok > 0"))[0];
    $total_user      = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM user WHERE role='pembeli'"))[0];
    return [
        'total_penjualan' => (float)$total_penjualan,
        'pesanan_baru'    => (int)$pesanan_baru,
        'produk_aktif'    => (int)$produk_aktif,
        'total_user'      => (int)$total_user,
    ];
}

function getAllPesananAdmin($conn) {
    $res  = mysqli_query($conn,
        "SELECT ps.*, u.nama AS nama_pembeli, u.email
         FROM pesanan ps
         JOIN user u ON ps.id_user = u.id_user
         ORDER BY ps.created_at DESC");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    return $data;
}

function getAllUserAdmin($conn) {
    $res  = mysqli_query($conn,
        "SELECT u.*, COUNT(p.id_pesanan) AS total_pesanan
         FROM user u
         LEFT JOIN pesanan p ON u.id_user = p.id_user
         GROUP BY u.id_user
         ORDER BY u.created_at ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    return $data;
}

function getAllProdukAdmin($conn) {
    $res  = mysqli_query($conn,
        "SELECT p.*, k.nama AS kategori
         FROM produk p
         JOIN kategori k ON p.id_kategori = k.id_kategori
         ORDER BY k.id_kategori, p.nama");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) $data[] = $row;
    return $data;
}

// ── Upload Gambar ────────────────────────────────────────

function uploadGambar($file, $folder = 'uploads/') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) return null;
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // max 5MB
    $nama    = uniqid('img_') . '.' . $ext;
    $target  = $folder . $nama;
    if (!is_dir($folder)) mkdir($folder, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $target)) return $nama;
    return null;
}

// ── Format ──────────────────────────────────────────────

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
