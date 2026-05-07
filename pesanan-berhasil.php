<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || empty($_SESSION['pesanan_kode'])) {
    header("Location: index.php"); exit;
}

$kode        = $_SESSION['pesanan_kode'];
$total       = $_SESSION['pesanan_total'] ?? 0;
$alamat      = $_SESSION['pesanan_alamat'] ?? '';
$pesanan     = getPesananById($conn, $kode, $user['id_user']);
$no_pesanan  = '#' . $kode;
$tgl_pesanan = $pesanan ? date('d M Y', strtotime($pesanan['created_at'])) : date('d M Y');

// Bersihkan session pesanan
unset($_SESSION['pesanan_kode'], $_SESSION['pesanan_total'], $_SESSION['pesanan_alamat']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Berhasil - lavo.id</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .sukses-page {
      min-height: calc(100vh - 64px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 24px;
      background: var(--bg-light);
    }

    .sukses-card {
      background: white;
      border-radius: var(--radius-lg);
      padding: 40px 36px;
      max-width: 480px;
      width: 100%;
      box-shadow: var(--shadow-lg);
      text-align: center;
    }

    /* Animasi centang */
    .sukses-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--success), #16a34a);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes popIn {
      0%   { transform: scale(0); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }

    .sukses-icon i { font-size: 36px; color: white; }

    .sukses-card h2 {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 8px;
      font-family: 'Times New Roman', serif;
    }

    .sukses-card p {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 24px;
    }

    /* Info pesanan */
    .pesanan-info {
      background: var(--bg-light);
      border-radius: var(--radius-md);
      padding: 16px 20px;
      margin-bottom: 20px;
      text-align: left;
    }

    .pesanan-info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      font-size: 14px;
      border-bottom: 1px solid var(--border);
    }

    .pesanan-info-row:last-child { border-bottom: none; }
    .pesanan-info-row .label { color: var(--text-muted); }
    .pesanan-info-row .value { font-weight: 700; color: var(--text-dark); }
    .pesanan-info-row .value.no { color: var(--primary); font-family: monospace; }

    /* Alamat */
    .alamat-box {
      background: var(--bg-light);
      border-radius: var(--radius-md);
      padding: 16px 20px;
      margin-bottom: 24px;
      text-align: left;
    }

    .alamat-box-header {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .alamat-box-header i { color: var(--primary); }

    .alamat-box p {
      font-size: 13px;
      color: var(--text-muted);
      margin: 0;
      line-height: 1.6;
    }

    /* Ringkasan harga */
    .harga-summary {
      background: var(--bg-light);
      border-radius: var(--radius-md);
      padding: 16px 20px;
      margin-bottom: 24px;
      text-align: left;
    }

    .harga-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      padding: 5px 0;
      color: var(--text-muted);
    }

    .harga-row.total {
      border-top: 1.5px solid var(--border);
      margin-top: 8px;
      padding-top: 10px;
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .harga-row.total .val { color: var(--text-red); }

    /* Buttons */
    .sukses-actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .btn-lacak {
      background: var(--primary);
      color: white;
      border-radius: var(--radius-lg);
      padding: 13px;
      font-size: 14px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background var(--transition);
    }

    .btn-lacak:hover { background: var(--primary-dark); }

    .btn-beranda {
      background: transparent;
      color: var(--primary);
      border: 1.5px solid var(--primary);
      border-radius: var(--radius-lg);
      padding: 11px;
      font-size: 14px;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all var(--transition);
    }

    .btn-beranda:hover { background: var(--primary); color: white; }

    @media (max-width: 480px) {
      .sukses-card { padding: 28px 20px; }
    }
  </style>
</head>
<body>

<header class="navbar">
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="Logo" onerror="this.style.display='none'">
    <h2>lavo.id</h2>
  </div>
  <div class="navbar-actions">
    <a href="<?= $user ? ($user['role']==='admin' ? 'admin/dashboard.php' : 'profil.php') : 'login.php' ?>" class="nav-btn"><i class="fa fa-user"></i></a>
    <a href="keranjang.php" class="nav-btn"><i class="fa fa-shopping-cart"></i></a>
  </div>
</header>

<div class="sukses-page">
  <div style="position:absolute;top:80px;left:24px;">
    <a href="index.php" class="btn-back">
      <i class="fa fa-arrow-left"></i> Beranda
    </a>
  </div>
  <div class="sukses-card">

    <div class="sukses-icon">
      <i class="fa fa-check"></i>
    </div>

    <h2>Pesanan Berhasil!</h2>
    <p>
      <?php if (isset($_GET['transfer'])): ?>
        Bukti transfer kamu sudah kami terima. Pesanan akan diproses setelah pembayaran dikonfirmasi.
      <?php else: ?>
        Terima kasih atas pesanan Anda.<br>Kami akan segera memprosesnya.
      <?php endif; ?>
    </p>

    <!-- Info Pesanan -->
    <div class="pesanan-info">
      <div class="pesanan-info-row">
        <span class="label">Nomor Pesanan</span>
        <span class="value no"><?= $no_pesanan ?></span>
      </div>
      <div class="pesanan-info-row">
        <span class="label">Tanggal Pesanan</span>
        <span class="value"><?= $tgl_pesanan ?></span>
      </div>
      <div class="pesanan-info-row">
        <span class="label">Status</span>
        <span class="value" style="color:var(--accent);">⏳ Diproses</span>
      </div>
    </div>

    <!-- Alamat -->
    <div class="alamat-box">
      <div class="alamat-box-header">
        <i class="fa fa-map-marker-alt"></i> Alamat Pengiriman
      </div>
      <p><?= htmlspecialchars($alamat) ?></p>
    </div>

    <!-- Ringkasan Harga -->
    <div class="harga-summary">
      <div class="harga-row">
        <span>Subtotal</span><span>Rp 40.000</span>
      </div>
      <div class="harga-row">
        <span>Pengiriman</span><span>Rp 15.000</span>
      </div>
      <div class="harga-row">
        <span>Diskon</span><span style="color:var(--success);">-Rp 5.000</span>
      </div>
      <div class="harga-row total">
        <span>Total Pembayaran</span>
        <span class="val">Rp <?= number_format($total, 0, ',', '.') ?></span>
      </div>
    </div>

    <!-- Aksi -->
    <div class="sukses-actions">
      <a href="lacak-pesanan.php" class="btn-lacak">
        <i class="fa fa-map-marker-alt"></i> Lacak Pesanan
      </a>
      <a href="index.php" class="btn-beranda">
        <i class="fa fa-home"></i> Kembali ke Beranda
      </a>
    </div>

  </div>
</div>

</body>
</html>
