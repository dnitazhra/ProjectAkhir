<?php
session_start();
include '../koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'redirect' => '../login.php']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$id_produk = (int)($data['id_produk'] ?? 0);
$jumlah    = max(1, (int)($data['jumlah'] ?? 1));
$varian    = trim($data['varian'] ?? '');
$id_user   = (int)$_SESSION['user']['id_user'];

if (!$id_produk) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak valid']);
    exit;
}

// Cek stok
$stmt = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id_produk = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_produk);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $stok);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($stok < $jumlah) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
    exit;
}

// Cek apakah sudah ada di keranjang (produk + varian sama)
$stmt2 = mysqli_prepare($conn,
    "SELECT id_keranjang, jumlah FROM keranjang WHERE id_user = ? AND id_produk = ? AND varian = ?");
mysqli_stmt_bind_param($stmt2, 'iis', $id_user, $id_produk, $varian);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$existing = mysqli_fetch_assoc($res2);
mysqli_stmt_close($stmt2);

if ($existing) {
    // Cek total qty (yang di keranjang + yang ditambah) tidak melebihi stok
    $new_qty = $existing['jumlah'] + $jumlah;
    if ($new_qty > $stok) {
        echo json_encode([
            'success' => false,
            'message' => "Stok tidak mencukupi. Stok tersedia: $stok, sudah di keranjang: {$existing['jumlah']}"
        ]);
        exit;
    }
    $stmt3 = mysqli_prepare($conn, "UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ?");
    mysqli_stmt_bind_param($stmt3, 'ii', $new_qty, $existing['id_keranjang']);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);
} else {
    $stmt4 = mysqli_prepare($conn,
        "INSERT INTO keranjang (id_user, id_produk, varian, jumlah) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt4, 'iisi', $id_user, $id_produk, $varian, $jumlah);
    mysqli_stmt_execute($stmt4);
    mysqli_stmt_close($stmt4);
}

// Hitung total item di keranjang
$stmt5 = mysqli_prepare($conn, "SELECT COALESCE(SUM(jumlah),0) FROM keranjang WHERE id_user = ?");
mysqli_stmt_bind_param($stmt5, 'i', $id_user);
mysqli_stmt_execute($stmt5);
mysqli_stmt_bind_result($stmt5, $total_cart);
mysqli_stmt_fetch($stmt5);
mysqli_stmt_close($stmt5);

echo json_encode(['success' => true, 'cart_count' => (int)$total_cart]);
