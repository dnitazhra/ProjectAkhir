<?php
session_start();
include '../koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false]); exit;
}

$data         = json_decode(file_get_contents('php://input'), true);
$id_keranjang = (int)($data['id_keranjang'] ?? 0);
$jumlah       = (int)($data['jumlah'] ?? 1);
$id_user      = (int)$_SESSION['user']['id_user'];

if ($jumlah < 1) {
    // Hapus item
    $stmt = mysqli_prepare($conn,
        "DELETE FROM keranjang WHERE id_keranjang = ? AND id_user = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id_keranjang, $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    $stmt = mysqli_prepare($conn,
        "UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ? AND id_user = ?");
    mysqli_stmt_bind_param($stmt, 'iii', $jumlah, $id_keranjang, $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Hitung ulang total
$stmt2 = mysqli_prepare($conn, "SELECT COALESCE(SUM(jumlah),0) FROM keranjang WHERE id_user = ?");
mysqli_stmt_bind_param($stmt2, 'i', $id_user);
mysqli_stmt_execute($stmt2);
mysqli_stmt_bind_result($stmt2, $total_cart);
mysqli_stmt_fetch($stmt2);
mysqli_stmt_close($stmt2);

echo json_encode(['success' => true, 'cart_count' => (int)$total_cart]);
