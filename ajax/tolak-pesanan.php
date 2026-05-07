<?php
session_start();
include '../koneksi.php';

header('Content-Type: application/json');

// Hanya admin
$admin = $_SESSION['user'] ?? null;
if (!$admin || $admin['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$kode   = trim($input['kode'] ?? '');
$alasan = trim($input['alasan'] ?? '');

if (!$kode) {
    echo json_encode(['success' => false, 'message' => 'Kode pesanan tidak valid']);
    exit;
}

$stmt = mysqli_prepare($conn,
    "UPDATE pesanan SET status = 'ditolak' WHERE kode = ? AND status != 'selesai' AND status != 'ditolak'");
mysqli_stmt_bind_param($stmt, 's', $kode);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected > 0) {
    echo json_encode(['success' => true, 'message' => 'Pesanan berhasil ditolak']);
} else {
    // Cek apakah pesanan ada
    $cek = mysqli_prepare($conn, "SELECT status FROM pesanan WHERE kode = ?");
    mysqli_stmt_bind_param($cek, 's', $kode);
    mysqli_stmt_execute($cek);
    $res = mysqli_stmt_get_result($cek);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($cek);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Status pesanan sudah ' . $row['status']]);
    }
}
