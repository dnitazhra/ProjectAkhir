<?php
session_start();
include '../koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$kode = trim($data['kode'] ?? '');
$id_user = (int)$_SESSION['user']['id_user'];

if (!$kode) {
    echo json_encode(['success' => false, 'message' => 'Kode pesanan tidak valid']); exit;
}

// Hanya boleh selesaikan pesanan milik sendiri yang statusnya bukan 'selesai'
$stmt = mysqli_prepare($conn,
    "UPDATE pesanan SET status = 'selesai'
     WHERE kode = ? AND id_user = ? AND status != 'selesai'");
mysqli_stmt_bind_param($stmt, 'si', $kode, $id_user);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected > 0) {
    echo json_encode(['success' => true, 'message' => 'Pesanan ditandai selesai!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau status tidak valid']);
}
