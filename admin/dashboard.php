<?php
session_start();
include '../koneksi.php';
include '../includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') { header("Location: ../login.php"); exit; }

// Proses tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_produk']) && !isset($_POST['edit_produk'])) {
    $nama_p   = trim($_POST['nama_produk']);
    $kat_nama = trim($_POST['kategori']);
    $harga    = (float)$_POST['harga'];
    $satuan   = trim($_POST['satuan']);
    $stok     = (int)$_POST['stok'];
    $deskripsi= trim($_POST['deskripsi'] ?? '');

    // Upload gambar dengan path absolut
    $gambar = null;
    if (!empty($_FILES['gambar']['tmp_name']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && $_FILES['gambar']['size'] <= 5 * 1024 * 1024) {
            $upload_dir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $nama_file   = uniqid('img_') . '.' . $ext;
            $target_path = $upload_dir . $nama_file;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path)) {
                $gambar = $nama_file;
            }
        }
    }

    // Cari id_kategori
    $sk = mysqli_prepare($conn, "SELECT id_kategori FROM kategori WHERE nama = ?");
    mysqli_stmt_bind_param($sk, 's', $kat_nama);
    mysqli_stmt_execute($sk);
    mysqli_stmt_bind_result($sk, $id_kat);
    mysqli_stmt_fetch($sk);
    mysqli_stmt_close($sk);

    if ($id_kat) {
        $sp = mysqli_prepare($conn,
            "INSERT INTO produk (id_kategori, nama, harga, satuan, stok, deskripsi, gambar)
             VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($sp, 'isdsiss', $id_kat, $nama_p, $harga, $satuan, $stok, $deskripsi, $gambar);
        mysqli_stmt_execute($sp);
        mysqli_stmt_close($sp);
    }
    header("Location: dashboard.php"); exit;
}

// Proses edit produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_produk'])) {
    $id_edit   = (int)$_POST['id_produk'];
    $nama_p    = trim($_POST['nama_produk']);
    $kat_nama  = trim($_POST['kategori']);
    $harga     = (float)$_POST['harga'];
    $satuan    = trim($_POST['satuan']);
    $stok      = (int)$_POST['stok'];
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    // Cari id_kategori
    $sk = mysqli_prepare($conn, "SELECT id_kategori FROM kategori WHERE nama = ?");
    mysqli_stmt_bind_param($sk, 's', $kat_nama);
    mysqli_stmt_execute($sk);
    mysqli_stmt_bind_result($sk, $id_kat);
    mysqli_stmt_fetch($sk);
    mysqli_stmt_close($sk);

    // Upload gambar baru dengan path absolut
    $upload_dir  = dirname(__DIR__) . '/uploads/';
    $gambar_baru = null;
    if (!empty($_FILES['gambar']['tmp_name']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && $_FILES['gambar']['size'] <= 5 * 1024 * 1024) {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $nama_file   = uniqid('img_') . '.' . $ext;
            $target_path = $upload_dir . $nama_file;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path)) {
                $gambar_baru = $nama_file;
            }
        }
    }

    if ($id_kat) {
        // Update dengan kategori baru
        if ($gambar_baru) {
            $sp = mysqli_prepare($conn,
                "UPDATE produk SET id_kategori=?, nama=?, harga=?, satuan=?, stok=?, deskripsi=?, gambar=?
                 WHERE id_produk=?");
            mysqli_stmt_bind_param($sp, 'isdsissi',
                $id_kat, $nama_p, $harga, $satuan, $stok, $deskripsi, $gambar_baru, $id_edit);
        } else {
            $sp = mysqli_prepare($conn,
                "UPDATE produk SET id_kategori=?, nama=?, harga=?, satuan=?, stok=?, deskripsi=?
                 WHERE id_produk=?");
            mysqli_stmt_bind_param($sp, 'isdsisi',
                $id_kat, $nama_p, $harga, $satuan, $stok, $deskripsi, $id_edit);
        }
    } else {
        // Kategori tidak ditemukan — update tanpa mengubah id_kategori
        if ($gambar_baru) {
            $sp = mysqli_prepare($conn,
                "UPDATE produk SET nama=?, harga=?, satuan=?, stok=?, deskripsi=?, gambar=?
                 WHERE id_produk=?");
            mysqli_stmt_bind_param($sp, 'sdsissi',
                $nama_p, $harga, $satuan, $stok, $deskripsi, $gambar_baru, $id_edit);
        } else {
            $sp = mysqli_prepare($conn,
                "UPDATE produk SET nama=?, harga=?, satuan=?, stok=?, deskripsi=?
                 WHERE id_produk=?");
            mysqli_stmt_bind_param($sp, 'sdsisi',
                $nama_p, $harga, $satuan, $stok, $deskripsi, $id_edit);
        }
    }
    mysqli_stmt_execute($sp);
    mysqli_stmt_close($sp);
    header("Location: dashboard.php"); exit;
}

// Proses restock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
    $id_restock  = (int)$_POST['id_produk'];
    $tambah_stok = (int)$_POST['tambah_stok'];
    if ($id_restock > 0 && $tambah_stok > 0) {
        $sr = mysqli_prepare($conn,
            "UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
        mysqli_stmt_bind_param($sr, 'ii', $tambah_stok, $id_restock);
        mysqli_stmt_execute($sr);
        mysqli_stmt_close($sr);
    }
    header("Location: dashboard.php"); exit;
}

// Proses hapus produk
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    $sh  = mysqli_prepare($conn, "DELETE FROM produk WHERE id_produk = ?");
    mysqli_stmt_bind_param($sh, 'i', $hid);
    mysqli_stmt_execute($sh);
    mysqli_stmt_close($sh);
    header("Location: dashboard.php"); exit;
}

$stats       = getStatsAdmin($conn);
$produk_list = getAllProdukAdmin($conn);

// Ambil varian untuk setiap produk (untuk modal detail)
$varian_map = [];
foreach ($produk_list as $p) {
    $varian_map[$p['id_produk']] = getVarianProduk($conn, $p['id_produk']);
}

// ── Best Seller: produk dengan total terjual terbanyak ──
$res_bs = mysqli_query($conn,
    "SELECT p.id_produk, p.nama, p.harga, p.satuan, p.gambar, k.nama AS kategori,
            COALESCE(SUM(dp.jumlah), 0) AS total_terjual,
            COALESCE(COUNT(DISTINCT dp.id_pesanan), 0) AS total_pesanan,
            COALESCE(ROUND(AVG(u.rating),1), 0) AS avg_rating
     FROM produk p
     JOIN kategori k ON p.id_kategori = k.id_kategori
     LEFT JOIN detail_pesanan dp ON p.id_produk = dp.id_produk
     LEFT JOIN pesanan ps ON dp.id_pesanan = ps.id_pesanan AND ps.status = 'selesai'
     LEFT JOIN ulasan u ON p.id_produk = u.id_produk
     GROUP BY p.id_produk
     ORDER BY total_terjual DESC
     LIMIT 5");
$best_seller = [];
while ($row = mysqli_fetch_assoc($res_bs)) $best_seller[] = $row;

// ── Ulasan terbaru ──
$res_ul = mysqli_query($conn,
    "SELECT u.id_ulasan, u.rating, u.komentar, u.foto, u.created_at,
            usr.nama AS nama_user,
            p.nama AS nama_produk, p.id_produk
     FROM ulasan u
     JOIN user usr ON u.id_user = usr.id_user
     JOIN produk p ON u.id_produk = p.id_produk
     ORDER BY u.created_at DESC
     LIMIT 6");
$ulasan_terbaru = [];
while ($row = mysqli_fetch_assoc($res_ul)) $ulasan_terbaru[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - lavo.id</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }

    /* Sidebar Admin */
    .admin-sidebar {
      width: 240px;
      background: #1e1e2e;
      color: white;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0;
      height: 100vh;
      z-index: 50;
      transition: left 0.3s;
    }

    .admin-sidebar-logo {
      padding: 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .admin-sidebar-logo span {
      font-size: 18px;
      font-weight: 700;
      color: var(--accent);
      font-family: 'Times New Roman', serif;
    }

    .admin-sidebar-logo small {
      display: block;
      font-size: 10px;
      color: rgba(255,255,255,0.5);
      font-family: sans-serif;
    }

    .admin-nav { padding: 12px 0; flex: 1; }

    .admin-nav-section {
      padding: 8px 16px 4px;
      font-size: 10px;
      font-weight: 700;
      color: rgba(255,255,255,0.4);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .admin-nav a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 20px;
      font-size: 14px;
      color: rgba(255,255,255,0.7);
      transition: all 0.2s;
      border-left: 3px solid transparent;
      text-decoration: none;
    }

    .admin-nav a:hover { background: rgba(255,255,255,0.07); color: white; }
    .admin-nav a.active { background: rgba(171,53,0,0.3); color: white; border-left-color: var(--primary); }
    .admin-nav a i { width: 18px; text-align: center; }

    .admin-sidebar-footer {
      padding: 16px 20px;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    .admin-sidebar-footer a {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: rgba(255,255,255,0.6);
      transition: color 0.2s;
      text-decoration: none;
    }

    .admin-sidebar-footer a:hover { color: white; }

    /* Main */
    .admin-main {
      margin-left: 240px;
      flex: 1;
      background: #f4f6f9;
      min-height: 100vh;
    }

    /* Topbar */
    .admin-topbar {
      background: white;
      border-bottom: 1px solid var(--border);
      padding: 0 24px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 40;
    }

    .admin-topbar h1 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .admin-topbar-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .admin-user {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-dark);
    }

    .admin-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 700;
    }

    .btn-menu-admin {
      display: none;
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: var(--text-dark);
    }

    /* Content */
    .admin-content { padding: 24px; }

    /* Best Seller & Ulasan */
    .two-col-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; }
    .section-card { background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden; }
    .section-card-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--border); }
    .section-card-header h3 { font-size: 15px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }
    .section-card-header h3 i { color: var(--primary); }
    .section-card-body { padding: 0; }

    /* Best Seller rows */
    .bs-row { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--border); transition: background 0.15s; }
    .bs-row:last-child { border-bottom: none; }
    .bs-row:hover { background: var(--bg-light); }
    .bs-rank { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
    .bs-rank.gold   { background: #fef3c7; color: #92400e; }
    .bs-rank.silver { background: #f1f5f9; color: #475569; }
    .bs-rank.bronze { background: #fef9ee; color: #b45309; }
    .bs-rank.other  { background: var(--bg-light); color: var(--text-muted); }
    .bs-img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; }
    .bs-img-ph { width: 40px; height: 40px; border-radius: 8px; background: var(--bg-light); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); flex-shrink: 0; }
    .bs-info { flex: 1; min-width: 0; }
    .bs-nama { font-size: 13px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bs-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; display: flex; gap: 8px; }
    .bs-terjual { font-size: 13px; font-weight: 700; color: var(--primary); flex-shrink: 0; text-align: right; }

    /* Ulasan rows */
    .ul-row { padding: 12px 20px; border-bottom: 1px solid var(--border); }
    .ul-row:last-child { border-bottom: none; }
    .ul-row-top { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
    .ul-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ul-user { font-size: 13px; font-weight: 700; color: var(--text-dark); flex: 1; }
    .ul-stars { color: #f59e0b; font-size: 12px; letter-spacing: 1px; }
    .ul-tgl { font-size: 11px; color: var(--text-muted); }
    .ul-produk { font-size: 11px; color: var(--primary); font-weight: 600; margin-bottom: 4px; }
    .ul-komentar { font-size: 12px; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .ul-foto { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; border: 1px solid var(--border); margin-top: 6px; cursor: pointer; }

    @media (max-width: 900px) { .two-col-grid { grid-template-columns: 1fr; } }

    /* Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: white;
      border-radius: var(--radius-md);
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: var(--shadow-sm);
    }

    .stat-icon {
      width: 52px;
      height: 52px;
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      color: white;
      flex-shrink: 0;
    }

    .stat-info .val {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-dark);
      line-height: 1;
    }

    .stat-info .lbl {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* Table card */
    .table-card {
      background: white;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }

    .table-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    .table-card-header h3 {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .admin-table { width: 100%; border-collapse: collapse; }

    .admin-table th {
      text-align: left;
      padding: 11px 16px;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: var(--bg-light);
      border-bottom: 1px solid var(--border);
    }

    .admin-table td {
      padding: 12px 16px;
      font-size: 14px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    .admin-table tr:last-child td { border-bottom: none; }
    .admin-table tr:hover td { background: var(--bg-light); }

    .btn-edit {
      background: #dbeafe;
      color: #1d4ed8;
      border: none;
      padding: 5px 12px;
      border-radius: var(--radius-full);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      margin-right: 4px;
      transition: background 0.2s;
    }

    .btn-edit:hover { background: #bfdbfe; }

    .btn-restock {
      background: #dcfce7;
      color: #16a34a;
      border: none;
      padding: 5px 12px;
      border-radius: var(--radius-full);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      margin-right: 4px;
      transition: background 0.2s;
    }

    .btn-restock:hover { background: #bbf7d0; }

    .btn-hapus {
      background: #fee2e2;
      color: #dc2626;
      border: none;
      padding: 5px 12px;
      border-radius: var(--radius-full);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-hapus:hover { background: #fecaca; }

    /* Modal Tambah Produk */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 200;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.active { display: flex; }

    .modal {
      background: white;
      border-radius: var(--radius-lg);
      padding: 28px;
      width: 100%;
      max-width: 480px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-header h3 { font-size: 18px; font-weight: 700; }

    .modal-close {
      font-size: 20px;
      color: var(--text-muted);
      cursor: pointer;
      padding: 4px;
    }

    /* Modal Detail Produk */
    .modal-detail-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.55); z-index: 300;
      align-items: center; justify-content: center; padding: 16px;
    }
    .modal-detail-overlay.open { display: flex; }
    .modal-detail-box {
      background: white; border-radius: 16px; width: 580px;
      max-width: 100%; max-height: 90vh; overflow-y: auto;
      box-shadow: 0 24px 64px rgba(0,0,0,0.3);
      animation: mdIn 0.2s ease;
    }
    @keyframes mdIn { from{transform:scale(0.93);opacity:0} to{transform:scale(1);opacity:1} }
    .md-header {
      background: linear-gradient(135deg, var(--primary), #c0392b);
      padding: 20px 24px; border-radius: 16px 16px 0 0;
      display: flex; align-items: center; justify-content: space-between;
    }
    .md-header h2 { font-size: 18px; font-weight: 700; color: white; font-family: 'Times New Roman', serif; }
    .md-header-close { background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; }
    .md-header-close:hover { background: rgba(255,255,255,0.35); }
    .md-body { display: grid; grid-template-columns: 200px 1fr; gap: 0; }
    .md-img-col { padding: 20px; display: flex; align-items: flex-start; justify-content: center; border-right: 1px solid var(--border); }
    .md-img { width: 160px; height: 160px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border); }
    .md-img-placeholder { width: 160px; height: 160px; border-radius: 12px; background: var(--bg-light); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); }
    .md-info-col { padding: 20px; }
    .md-section-title { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; margin-top: 16px; padding-bottom: 6px; border-bottom: 1px solid var(--border); }
    .md-section-title:first-child { margin-top: 0; }
    .md-row { display: flex; gap: 10px; padding: 5px 0; font-size: 13px; }
    .md-row .lbl { color: var(--text-muted); min-width: 90px; flex-shrink: 0; }
    .md-row .val { font-weight: 600; color: var(--text-dark); }
    .md-desc { font-size: 13px; color: var(--text-muted); line-height: 1.7; white-space: pre-line; }
    .md-varian-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
    .md-varian-tag { background: #fff5f0; color: var(--primary); border: 1px solid #fecaca; padding: 3px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
    .md-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; gap: 10px; justify-content: flex-end; }
    .btn-detail-produk { background: #eff6ff; color: #2563eb; border: none; padding: 5px 12px; border-radius: var(--radius-full); font-size: 12px; font-weight: 600; cursor: pointer; margin-right: 4px; }
    .btn-detail-produk:hover { background: #dbeafe; }
    @media (max-width: 540px) {
      .md-body { grid-template-columns: 1fr; }
      .md-img-col { border-right: none; border-bottom: 1px solid var(--border); }
    }

    @media (max-width: 900px) {
      .admin-sidebar { left: -240px; }
      .admin-sidebar.open { left: 0; }
      .admin-main { margin-left: 0; }
      .btn-menu-admin { display: block; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 480px) {
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .admin-content { padding: 16px; }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-logo">
      <div style="width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">L</div>
      <div>
        <span>lavo.id</span>
        <small>Admin Panel</small>
      </div>
    </div>
    <nav class="admin-nav">
      <div class="admin-nav-section">Main</div>
      <a href="dashboard.php" class="active"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <div class="admin-nav-section">Kelola</div>
      <a href="manajemen-pengguna.php"><i class="fa fa-users"></i> Manajemen Pengguna</a>
      <a href="riwayat-pesanan.php"><i class="fa fa-receipt"></i> Riwayat Pesanan</a>
      <div class="admin-nav-section">Lainnya</div>
      <a href="../index.php"><i class="fa fa-store"></i> Lihat Toko</a>
      <a href="profil.php"><i class="fa fa-user-shield"></i> Profil Admin</a>
    </nav>
    <div class="admin-sidebar-footer">
      <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
    </div>
  </aside>

  <!-- Main -->
  <div class="admin-main">

    <!-- Topbar -->
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button class="btn-menu-admin" onclick="toggleAdminSidebar()"><i class="fa fa-bars"></i></button>
        <h1>Dashboard Admin</h1>
      </div>
      <div class="admin-topbar-right">
        <a href="profil.php" class="admin-user" style="text-decoration:none;color:inherit;" title="Lihat Profil Admin">
          <div class="admin-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
          <span><?= htmlspecialchars($user['nama']) ?></span>
        </a>
      </div>
    </div>

    <div class="admin-content">

      <!-- Stats -->
      <div class="stats-grid">
        <?php
        $stats_display = [
            ['label'=>'Total Penjualan', 'value'=> 'Rp '.number_format($stats['total_penjualan'],0,',','.'), 'icon'=>'fa-money-bill-wave','color'=>'#22c55e'],
            ['label'=>'Pesanan Baru',    'value'=> $stats['pesanan_baru'],    'icon'=>'fa-box',            'color'=>'#3b82f6'],
            ['label'=>'Produk Aktif',    'value'=> $stats['produk_aktif'],    'icon'=>'fa-store',          'color'=>'#f59e0b'],
            ['label'=>'Pelanggan',       'value'=> $stats['total_user'],      'icon'=>'fa-users',          'color'=>'#8b5cf6'],
        ];
        foreach ($stats_display as $s): ?>
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $s['color'] ?>;">
            <i class="fa <?= $s['icon'] ?>"></i>
          </div>
          <div class="stat-info">
            <div class="val"><?= $s['value'] ?></div>
            <div class="lbl"><?= $s['label'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Tabel Produk -->
      <div class="table-card">
        <div class="table-card-header">
          <h3><i class="fa fa-box" style="color:var(--primary);margin-right:8px;"></i>Daftar Produk lavo.id</h3>
          <button class="btn btn-primary" style="padding:8px 16px;font-size:13px;" onclick="bukaModal()">
            <i class="fa fa-plus"></i> Tambah Produk
          </button>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Satuan</th>
                <th>Stok</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($produk_list as $i => $p): ?>
              <tr style="cursor:pointer;" onclick="lihatDetailProduk(<?= $p['id_produk'] ?>)">
                <td style="color:var(--text-muted);"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <?php if (!empty($p['gambar'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($p['gambar']) ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;">
                    <?php else: ?>
                    <div style="width:36px;height:36px;border-radius:8px;background:var(--bg-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--border);">
                      <i class="fa fa-image" style="color:#d1d5db;font-size:14px;"></i>
                    </div>
                    <?php endif; ?>
                    <span style="font-weight:600;"><?= htmlspecialchars($p['nama']) ?></span>
                  </div>
                </td>
                <td style="color:var(--text-red);font-weight:700;">Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                <td><?= $p['satuan'] ?></td>
                <td>
                  <span style="background:<?= $p['stok'] > 10 ? '#dcfce7' : ($p['stok'] > 0 ? '#fef3c7' : '#fee2e2') ?>;color:<?= $p['stok'] > 10 ? '#16a34a' : ($p['stok'] > 0 ? '#92400e' : '#dc2626') ?>;padding:3px 10px;border-radius:9999px;font-size:12px;font-weight:700;">
                    <?= $p['stok'] ?><?= $p['stok'] == 0 ? ' (Habis)' : ($p['stok'] <= 10 ? ' ⚠️' : '') ?>
                  </span>
                </td>
                <td onclick="event.stopPropagation()">
                  <button class="btn-detail-produk" onclick="lihatDetailProduk(<?= $p['id_produk'] ?>)">
                    <i class="fa fa-eye"></i> Detail
                  </button>
                  <button class="btn-edit"
                    onclick="bukaModalEdit(
                      <?= $p['id_produk'] ?>,
                      '<?= addslashes($p['nama']) ?>',
                      '<?= addslashes($p['kategori']) ?>',
                      <?= $p['harga'] ?>,
                      '<?= addslashes($p['satuan']) ?>',
                      <?= $p['stok'] ?>,
                      '<?= addslashes($p['deskripsi'] ?? '') ?>'
                    )">
                    <i class="fa fa-pen"></i> Edit
                  </button>
                  <button class="btn-restock"
                    onclick="bukaModalRestock(<?= $p['id_produk'] ?>, '<?= addslashes($p['nama']) ?>', <?= $p['stok'] ?>)">
                    <i class="fa fa-plus-circle"></i> Restock
                  </button>
                  <a href="dashboard.php?hapus=<?= $p['id_produk'] ?>"
                     class="btn-hapus"
                     onclick="return confirm('Hapus produk ini?')">
                    <i class="fa fa-trash"></i> Hapus
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Best Seller & Ulasan Terbaru ── -->
      <div class="two-col-grid">

        <!-- Best Seller -->
        <div class="section-card">
          <div class="section-card-header">
            <h3><i class="fa fa-fire"></i> Best Seller</h3>
            <span style="font-size:12px;color:var(--text-muted);">Berdasarkan pesanan selesai</span>
          </div>
          <div class="section-card-body">
            <?php if (empty($best_seller)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:13px;">
              <i class="fa fa-box-open" style="font-size:28px;opacity:0.3;display:block;margin-bottom:8px;"></i>
              Belum ada data penjualan
            </div>
            <?php else: ?>
            <?php foreach ($best_seller as $idx => $bs):
              $rank_class = match($idx) { 0 => 'gold', 1 => 'silver', 2 => 'bronze', default => 'other' };
              $rank_icon  = match($idx) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => ($idx+1) };
            ?>
            <div class="bs-row">
              <div class="bs-rank <?= $rank_class ?>"><?= $rank_icon ?></div>
              <?php if (!empty($bs['gambar'])): ?>
                <img src="../uploads/<?= htmlspecialchars($bs['gambar']) ?>" class="bs-img"
                     onerror="this.style.display='none'">
              <?php else: ?>
                <div class="bs-img-ph"><i class="fa fa-image" style="color:#d1d5db;font-size:14px;"></i></div>
              <?php endif; ?>
              <div class="bs-info">
                <div class="bs-nama"><?= htmlspecialchars($bs['nama']) ?></div>
                <div class="bs-meta">
                  <span><?= htmlspecialchars($bs['kategori']) ?></span>
                  <?php if ($bs['avg_rating'] > 0): ?>
                  <span>⭐ <?= $bs['avg_rating'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="bs-terjual">
                <?= number_format($bs['total_terjual']) ?>
                <div style="font-size:10px;color:var(--text-muted);font-weight:400;">terjual</div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Ulasan Terbaru -->
        <div class="section-card">
          <div class="section-card-header">
            <h3><i class="fa fa-star"></i> Ulasan Terbaru</h3>
            <span style="font-size:12px;color:var(--text-muted);"><?= count($ulasan_terbaru) ?> ulasan</span>
          </div>
          <div class="section-card-body">
            <?php if (empty($ulasan_terbaru)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:13px;">
              <i class="fa fa-star" style="font-size:28px;opacity:0.3;display:block;margin-bottom:8px;"></i>
              Belum ada ulasan
            </div>
            <?php else: ?>
            <?php foreach ($ulasan_terbaru as $ul):
              $bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
              $tgl   = new DateTime($ul['created_at']);
              $tgl_str = $tgl->format('d') . ' ' . $bulan[(int)$tgl->format('m')-1];
            ?>
            <div class="ul-row">
              <div class="ul-row-top">
                <div class="ul-avatar"><?= strtoupper(substr($ul['nama_user'], 0, 1)) ?></div>
                <div class="ul-user"><?= htmlspecialchars($ul['nama_user']) ?></div>
                <div class="ul-stars">
                  <?php for ($i = 1; $i <= 5; $i++) echo $i <= $ul['rating'] ? '★' : '☆'; ?>
                </div>
                <div class="ul-tgl"><?= $tgl_str ?></div>
              </div>
              <div class="ul-produk">
                <i class="fa fa-box" style="margin-right:4px;"></i><?= htmlspecialchars($ul['nama_produk']) ?>
              </div>
              <div class="ul-komentar"><?= htmlspecialchars($ul['komentar']) ?></div>
              <?php if (!empty($ul['foto'])): ?>
              <img src="../uploads/<?= htmlspecialchars($ul['foto']) ?>"
                   class="ul-foto"
                   onclick="bukaFotoUlasan(this.src)"
                   title="Foto ulasan">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div><!-- end two-col-grid -->

    </div>
  </div>
</div>

<!-- Modal Tambah Produk -->
<div class="modal-overlay" id="modalTambah">
  <div class="modal">
    <div class="modal-header">
      <h3>Tambah Produk Baru</h3>
      <span class="modal-close" onclick="tutupModal()"><i class="fa fa-times"></i></span>
    </div>
    <form method="POST" action="dashboard.php" enctype="multipart/form-data">
      <div class="form-group">
        <label>Nama Produk</label>
        <input type="text" name="nama_produk" class="form-control" placeholder="Nama produk" required>
      </div>
      <div class="form-group">
        <label>Kategori</label>
        <select name="kategori" class="form-control">
          <option>Snack Kering</option>
          <option>Kue Kering</option>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label>Harga (Rp)</label>
          <input type="number" name="harga" class="form-control" placeholder="15000" required>
        </div>
        <div class="form-group">
          <label>Satuan</label>
          <input type="text" name="satuan" class="form-control" placeholder="250g / pcs">
        </div>
      </div>
      <div class="form-group">
        <label>Stok</label>
        <input type="number" name="stok" class="form-control" placeholder="50" required>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi produk..."></textarea>
      </div>
      <div class="form-group">
        <label>Gambar Produk</label>
        <input type="file" name="gambar" class="form-control" accept="image/*">
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-primary btn-full">
          <i class="fa fa-save"></i> Simpan Produk
        </button>
        <button type="button" class="btn btn-outline" onclick="tutupModal()" style="flex:1;">
          Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Restock -->
<div class="modal-overlay" id="modalRestock">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <h3><i class="fa fa-plus-circle" style="color:#16a34a;margin-right:8px;"></i>Restock Produk</h3>
      <span class="modal-close" onclick="tutupModalRestock()"><i class="fa fa-times"></i></span>
    </div>
    <form method="POST">
      <input type="hidden" name="restock" value="1">
      <input type="hidden" name="id_produk" id="restock_id">

      <div style="background:var(--bg-light);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:16px;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Produk</div>
        <div style="font-size:15px;font-weight:700;" id="restock_nama">—</div>
        <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
          <span style="font-size:13px;color:var(--text-muted);">Stok saat ini:</span>
          <span style="font-weight:700;color:var(--primary);" id="restock_stok_now">—</span>
        </div>
      </div>

      <div class="form-group">
        <label>Tambah Stok</label>
        <div style="display:flex;align-items:center;gap:10px;">
          <button type="button" onclick="ubahRestok(-10)" style="width:36px;height:36px;border:1.5px solid var(--border);border-radius:50%;background:var(--bg-light);font-size:14px;cursor:pointer;">−</button>
          <input type="number" name="tambah_stok" id="tambah_stok_input"
                 class="form-control" value="10" min="1" max="9999"
                 style="text-align:center;font-size:18px;font-weight:700;width:100px;">
          <button type="button" onclick="ubahRestok(10)" style="width:36px;height:36px;border:1.5px solid var(--border);border-radius:50%;background:var(--bg-light);font-size:14px;cursor:pointer;">+</button>
        </div>
      </div>

      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;color:#15803d;margin-bottom:16px;">
        <i class="fa fa-info-circle"></i>
        Stok setelah restock: <strong id="stok_preview">—</strong>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary btn-full" style="background:#16a34a;">
          <i class="fa fa-check"></i> Simpan Restock
        </button>
        <button type="button" class="btn btn-outline" onclick="tutupModalRestock()" style="flex:1;">
          Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Produk -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa fa-pen" style="color:var(--primary);margin-right:8px;"></i>Edit Produk</h3>
      <span class="modal-close" onclick="tutupModal()"><i class="fa fa-times"></i></span>
    </div>
    <form method="POST" action="dashboard.php" enctype="multipart/form-data">
      <input type="hidden" name="edit_produk" value="1">
      <input type="hidden" name="id_produk" id="edit_id_produk">

      <div class="form-group">
        <label>Nama Produk</label>
        <input type="text" name="nama_produk" id="edit_nama" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Kategori</label>
        <select name="kategori" id="edit_kategori" class="form-control">
          <?php foreach (getKategoriAll($conn) as $k): ?>
          <option value="<?= htmlspecialchars($k['nama']) ?>"><?= htmlspecialchars($k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label>Harga (Rp)</label>
          <input type="number" name="harga" id="edit_harga" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Satuan</label>
          <input type="text" name="satuan" id="edit_satuan" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label>Stok</label>
        <input type="number" name="stok" id="edit_stok" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label>Ganti Gambar <small style="color:var(--text-muted);">(kosongkan jika tidak diganti)</small></label>
        <input type="file" name="gambar" class="form-control" accept="image/*">
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-primary btn-full">
          <i class="fa fa-save"></i> Simpan Perubahan
        </button>
        <button type="button" class="btn btn-outline" onclick="tutupModal()" style="flex:1;">
          Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Lightbox foto ulasan -->
<div id="fotoLightbox" onclick="this.classList.remove('open')"
  style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;">
  <img id="fotoLightboxImg" src="" style="max-width:90vw;max-height:85vh;border-radius:8px;">
  <div onclick="document.getElementById('fotoLightbox').classList.remove('open')"
    style="position:absolute;top:20px;right:24px;color:white;font-size:28px;cursor:pointer;background:rgba(0,0,0,0.4);width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
    <i class="fa fa-times"></i>
  </div>
</div>

<script>
function bukaFotoUlasan(src) {
  const lb = document.getElementById('fotoLightbox');
  document.getElementById('fotoLightboxImg').src = src;
  lb.style.display = 'flex';
}
document.getElementById('fotoLightbox').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.getElementById('fotoLightbox').style.display = 'none';
});
</script>
<div class="modal-detail-overlay" id="modalDetailProduk" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal-detail-box">
    <div class="md-header">
      <div>
        <div style="font-size:11px;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;" id="mdpKategori">—</div>
        <h2 id="mdpNama">—</h2>
      </div>
      <button class="md-header-close" onclick="document.getElementById('modalDetailProduk').classList.remove('open')">
        <i class="fa fa-times"></i>
      </button>
    </div>
    <div class="md-body">
      <!-- Kolom Gambar -->
      <div class="md-img-col">
        <div id="mdpImgWrap">
          <div class="md-img-placeholder">
            <i class="fa fa-image" style="font-size:40px;color:#d1d5db;"></i>
          </div>
        </div>
      </div>
      <!-- Kolom Info -->
      <div class="md-info-col">
        <div class="md-section-title"><i class="fa fa-tag" style="margin-right:5px;color:var(--primary);"></i>Info Produk</div>
        <div class="md-row">
          <span class="lbl">Harga</span>
          <span class="val" id="mdpHarga" style="color:#dc2626;font-size:15px;">—</span>
        </div>
        <div class="md-row">
          <span class="lbl">Satuan</span>
          <span class="val" id="mdpSatuan">—</span>
        </div>
        <div class="md-row">
          <span class="lbl">Stok</span>
          <span class="val" id="mdpStok">—</span>
        </div>
        <div class="md-row">
          <span class="lbl">Kategori</span>
          <span class="val" id="mdpKategoriVal">—</span>
        </div>

        <div class="md-section-title" style="margin-top:14px;"><i class="fa fa-list" style="margin-right:5px;color:var(--primary);"></i>Varian</div>
        <div class="md-varian-list" id="mdpVarianList">
          <span style="font-size:13px;color:var(--text-muted);">Tidak ada varian</span>
        </div>

        <div class="md-section-title" style="margin-top:14px;"><i class="fa fa-align-left" style="margin-right:5px;color:var(--primary);"></i>Deskripsi</div>
        <div class="md-desc" id="mdpDeskripsi">—</div>
      </div>
    </div>
    <div class="md-footer">
      <button onclick="document.getElementById('modalDetailProduk').classList.remove('open')"
        style="padding:8px 20px;border-radius:9999px;border:1.5px solid var(--border);background:white;font-size:13px;font-weight:600;cursor:pointer;">
        Tutup
      </button>
      <button id="mdpBtnEdit"
        style="padding:8px 20px;border-radius:9999px;background:var(--primary);color:white;font-size:13px;font-weight:600;border:none;cursor:pointer;">
        <i class="fa fa-pen"></i> Edit Produk
      </button>
    </div>
  </div>
</div>

<?php
// Data produk + varian sebagai JSON untuk JS
$produk_json = [];
foreach ($produk_list as $p) {
    $produk_json[$p['id_produk']] = [
        'id'        => $p['id_produk'],
        'nama'      => $p['nama'],
        'kategori'  => $p['kategori'],
        'harga'     => (float)$p['harga'],
        'satuan'    => $p['satuan'],
        'stok'      => (int)$p['stok'],
        'deskripsi' => $p['deskripsi'] ?? '',
        'gambar'    => $p['gambar'] ?? '',
        'varian'    => $varian_map[$p['id_produk']] ?? [],
    ];
}
?>

<script>
const produkData = <?= json_encode($produk_json, JSON_UNESCAPED_UNICODE) ?>;

function lihatDetailProduk(id) {
  const p = produkData[id];
  if (!p) return;

  document.getElementById('mdpNama').textContent       = p.nama;
  document.getElementById('mdpKategori').textContent   = p.kategori;
  document.getElementById('mdpKategoriVal').textContent= p.kategori;
  document.getElementById('mdpHarga').textContent      = 'Rp ' + Number(p.harga).toLocaleString('id-ID');
  document.getElementById('mdpSatuan').textContent     = p.satuan;
  document.getElementById('mdpDeskripsi').textContent  = p.deskripsi || 'Tidak ada deskripsi.';

  // Stok badge
  const stokEl = document.getElementById('mdpStok');
  const stokColor = p.stok > 10 ? '#16a34a' : (p.stok > 0 ? '#92400e' : '#dc2626');
  const stokBg    = p.stok > 10 ? '#dcfce7' : (p.stok > 0 ? '#fef3c7' : '#fee2e2');
  stokEl.innerHTML = `<span style="background:${stokBg};color:${stokColor};padding:2px 10px;border-radius:9999px;font-size:12px;font-weight:700;">${p.stok} ${p.satuan}${p.stok === 0 ? ' (Habis)' : (p.stok <= 10 ? ' ⚠️' : '')}</span>`;

  // Gambar
  const imgWrap = document.getElementById('mdpImgWrap');
  if (p.gambar) {
    imgWrap.innerHTML = `<img src="../uploads/${p.gambar}" class="md-img" onerror="this.parentNode.innerHTML='<div class=\\'md-img-placeholder\\'><i class=\\'fa fa-image\\' style=\\'font-size:40px;color:#d1d5db;\\'></i></div>'">`;
  } else {
    imgWrap.innerHTML = `<div class="md-img-placeholder"><i class="fa fa-image" style="font-size:40px;color:#d1d5db;"></i></div>`;
  }

  // Varian
  const varianEl = document.getElementById('mdpVarianList');
  if (p.varian && p.varian.length > 0) {
    varianEl.innerHTML = p.varian.map(v =>
      `<span class="md-varian-tag">${v}</span>`
    ).join('');
  } else {
    varianEl.innerHTML = '<span style="font-size:13px;color:var(--text-muted);">Tidak ada varian</span>';
  }

  // Tombol Edit
  document.getElementById('mdpBtnEdit').onclick = () => {
    document.getElementById('modalDetailProduk').classList.remove('open');
    bukaModalEdit(p.id, p.nama, p.kategori, p.harga, p.satuan, p.stok, p.deskripsi);
  };

  document.getElementById('modalDetailProduk').classList.add('open');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.getElementById('modalDetailProduk').classList.remove('open');
    tutupModal();
    tutupModalRestock();
  }
});
</script>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
}
function bukaModal() {
  document.getElementById('modalTambah').classList.add('active');
}
function tutupModal() {
  document.getElementById('modalTambah').classList.remove('active');
  document.getElementById('modalEdit').classList.remove('active');
}
function bukaModalEdit(id, nama, kategori, harga, satuan, stok, deskripsi) {
  document.getElementById('edit_id_produk').value   = id;
  document.getElementById('edit_nama').value        = nama;
  document.getElementById('edit_kategori').value    = kategori;
  document.getElementById('edit_harga').value       = harga;
  document.getElementById('edit_satuan').value      = satuan;
  document.getElementById('edit_stok').value        = stok;
  document.getElementById('edit_deskripsi').value   = deskripsi;
  document.getElementById('modalEdit').classList.add('active');
}
document.getElementById('modalTambah').addEventListener('click', function(e) {
  if (e.target === this) tutupModal();
});
document.getElementById('modalEdit').addEventListener('click', function(e) {
  if (e.target === this) tutupModal();
});

let _restokStokNow = 0;
function bukaModalRestock(id, nama, stok) {
  document.getElementById('restock_id').value        = id;
  document.getElementById('restock_nama').textContent = nama;
  document.getElementById('restock_stok_now').textContent = stok;
  document.getElementById('tambah_stok_input').value = 10;
  _restokStokNow = stok;
  document.getElementById('stok_preview').textContent = stok + 10;
  document.getElementById('modalRestock').classList.add('active');
}
function tutupModalRestock() {
  document.getElementById('modalRestock').classList.remove('active');
}
function ubahRestok(delta) {
  const input = document.getElementById('tambah_stok_input');
  let val = parseInt(input.value) + delta;
  if (val < 1) val = 1;
  input.value = val;
  document.getElementById('stok_preview').textContent = _restokStokNow + val;
}
document.getElementById('tambah_stok_input').addEventListener('input', function() {
  const val = parseInt(this.value) || 0;
  document.getElementById('stok_preview').textContent = _restokStokNow + val;
});
document.getElementById('modalRestock').addEventListener('click', function(e) {
  if (e.target === this) tutupModalRestock();
});
</script>
</body>
</html>
