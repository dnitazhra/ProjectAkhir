<?php
session_start();
include '../koneksi.php';
include '../includes/functions.php';

header('Content-Type: application/json');

$data     = json_decode(file_get_contents('php://input'), true);
$kode     = trim($data['kode'] ?? '');
$subtotal = (float)($data['subtotal'] ?? 0);

if (!$kode) {
    echo json_encode(['success' => false, 'message' => 'Kode voucher kosong']);
    exit;
}

$voucher = cekVoucher($conn, $kode, $subtotal);

if ($voucher) {
    echo json_encode([
        'success' => true,
        'diskon'  => (float)$voucher['diskon'],
        'message' => 'Voucher berhasil! Diskon Rp ' . number_format($voucher['diskon'], 0, ',', '.'),
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Voucher tidak valid atau sudah kadaluarsa']);
}
