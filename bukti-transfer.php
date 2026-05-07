<?php
session_start();

$user  = $_SESSION['user'] ?? null;
$bukti = $_SESSION['bukti_transfer'] ?? null;

if (!$bukti) { header("Location: index.php"); exit; }

// Data rekening toko per bank
$rekening_toko = [
    'BCA'     => ['no' => '1234567890',   'nama' => 'lavo.id'],
    'Mandiri' => ['no' => '110000998877', 'nama' => 'lavo.id'],
    'BNI'     => ['no' => '0987654321',   'nama' => 'lavo.id'],
    'BRI'     => ['no' => '1122334455',   'nama' => 'lavo.id'],
    'BSI'     => ['no' => '7788990011',   'nama' => 'lavo.id'],
];

$bank        = $bukti['bank'] ?? $bukti['bank_pengirim'] ?? 'BCA';
$rek_tujuan  = $rekening_toko[$bank] ?? ['no' => '1234567890', 'nama' => 'lavo.id'];
$tanggal     = date('d-m-Y', strtotime(str_replace(' ', 'T', $bukti['waktu'])));
$waktu       = date('H:i:s', strtotime(str_replace(' ', 'T', $bukti['waktu']))) . ' WIB';

// Warna per bank
$bank_colors = [
    'BCA'     => ['bg' => '#003d82', 'text' => '#ffffff', 'logo_color' => '#003d82'],
    'Mandiri' => ['bg' => '#003087', 'text' => '#ffffff', 'logo_color' => '#003087'],
    'BNI'     => ['bg' => '#f37021', 'text' => '#ffffff', 'logo_color' => '#f37021'],
    'BRI'     => ['bg' => '#00529b', 'text' => '#ffffff', 'logo_color' => '#00529b'],
    'BSI'     => ['bg' => '#4caf50', 'text' => '#ffffff', 'logo_color' => '#4caf50'],
];
$color = $bank_colors[$bank] ?? ['bg' => '#1a56db', 'text' => '#ffffff', 'logo_color' => '#1a56db'];

// Bersihkan session bukti setelah ditampilkan
// (jangan dihapus dulu biar bisa di-print)
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bukti Transfer - lavo.id</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background: #f0f2f5; }

    .bukti-page {
      max-width: 420px;
      margin: 32px auto;
      padding: 0 16px 40px;
    }

    /* Kartu bukti */
    .bukti-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    }

    /* Header bank */
    .bukti-bank-header {
      background: <?= $color['bg'] ?>;
      padding: 20px 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .bukti-bank-logo {
      width: 48px;
      height: 48px;
      background: white;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 900;
      color: <?= $color['bg'] ?>;
      letter-spacing: -0.5px;
      flex-shrink: 0;
    }

    .bukti-bank-name {
      font-size: 22px;
      font-weight: 800;
      color: white;
      letter-spacing: 1px;
    }

    /* Status berhasil */
    .bukti-status {
      text-align: center;
      padding: 28px 24px 20px;
      border-bottom: 1px solid #f0f0f0;
    }

    .bukti-status-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #0d9488;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 14px;
    }

    .bukti-status-icon i { font-size: 28px; color: white; }

    .bukti-status h2 {
      font-size: 18px;
      font-weight: 700;
      color: #1a1a1a;
      margin: 0;
    }

    /* Detail rows */
    .bukti-detail { padding: 8px 0; }

    .bukti-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 12px 24px;
      border-bottom: 1px solid #f5f5f5;
      gap: 16px;
    }

    .bukti-row:last-child { border-bottom: none; }

    .bukti-row .lbl {
      font-size: 13px;
      color: <?= $color['logo_color'] ?>;
      font-weight: 600;
      flex-shrink: 0;
      min-width: 130px;
    }

    .bukti-row .val {
      font-size: 13px;
      color: #1a1a1a;
      font-weight: 500;
      text-align: right;
    }

    /* Divider */
    .bukti-divider {
      height: 8px;
      background: #f5f5f5;
      margin: 0;
    }

    /* Total row */
    .bukti-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 24px;
      background: #f0fdf4;
    }

    .bukti-total .lbl {
      font-size: 14px;
      font-weight: 700;
      color: #1a1a1a;
    }

    .bukti-total .val {
      font-size: 16px;
      font-weight: 800;
      color: #1a1a1a;
    }

    /* Keterangan */
    .bukti-keterangan {
      padding: 12px 24px 20px;
      border-top: 1px solid #f0f0f0;
    }

    .bukti-keterangan .lbl {
      font-size: 13px;
      color: <?= $color['logo_color'] ?>;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .bukti-keterangan .val {
      font-size: 12px;
      color: #6b7280;
      font-family: monospace;
    }

    /* Bukti foto */
    .bukti-foto {
      padding: 16px 24px;
      border-top: 1px solid #f0f0f0;
    }

    .bukti-foto p {
      font-size: 12px;
      color: #6b7280;
      margin-bottom: 8px;
    }

    .bukti-foto img {
      width: 100%;
      border-radius: 8px;
      border: 1px solid #e5e7eb;
    }

    /* Tombol aksi */
    .bukti-actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 20px;
    }

    .btn-lacak-pesanan {
      background: var(--primary);
      color: white;
      border-radius: 9999px;
      padding: 14px;
      font-size: 15px;
      font-weight: 700;
      text-align: center;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background 0.2s;
    }

    .btn-lacak-pesanan:hover { background: var(--primary-dark); }

    .btn-print {
      background: white;
      color: #374151;
      border: 1.5px solid #e5e7eb;
      border-radius: 9999px;
      padding: 12px;
      font-size: 14px;
      font-weight: 600;
      text-align: center;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s;
    }

    .btn-print:hover { border-color: var(--primary); color: var(--primary); }

    .btn-beranda {
      color: var(--primary);
      font-size: 14px;
      font-weight: 600;
      text-align: center;
      text-decoration: none;
      display: block;
      padding: 8px;
    }

    @media print {
      .bukti-actions, header, .btn-back { display: none !important; }
      body { background: white; }
      .bukti-page { margin: 0; padding: 0; max-width: 100%; }
      .bukti-card { box-shadow: none; border-radius: 0; }
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
    <a href="<?= $user ? ($user['role']==='admin' ? 'admin/dashboard.php' : 'profil.php') : 'login.php' ?>" class="nav-btn">
      <i class="fa fa-user"></i>
    </a>
  </div>
</header>

<div class="bukti-page">

  <button class="btn-back" onclick="history.back()" style="margin-bottom:16px;">
    <i class="fa fa-arrow-left"></i> Kembali
  </button>

  <!-- Kartu Bukti Transfer -->
  <div class="bukti-card" id="buktiCard">

    <!-- Header Bank -->
    <div class="bukti-bank-header">
      <div class="bukti-bank-logo"><?= strtoupper(substr($bank, 0, 3)) ?></div>
      <div class="bukti-bank-name"><?= htmlspecialchars($bank) ?></div>
    </div>

    <!-- Status -->
    <div class="bukti-status">
      <div class="bukti-status-icon">
        <i class="fa fa-check"></i>
      </div>
      <h2>Transaksi Berhasil</h2>
    </div>

    <!-- Detail Transfer -->
    <div class="bukti-detail">
      <div class="bukti-row">
        <span class="lbl">Rekening Tujuan</span>
        <span class="val"><?= $rek_tujuan['no'] ?></span>
      </div>
      <div class="bukti-row">
        <span class="lbl">Nama Penerima</span>
        <span class="val"><?= htmlspecialchars($rek_tujuan['nama']) ?></span>
      </div>
      <div class="bukti-row">
        <span class="lbl">Tanggal Transaksi</span>
        <span class="val"><?= $tanggal ?></span>
      </div>
      <div class="bukti-row">
        <span class="lbl">Waktu Transaksi</span>
        <span class="val"><?= $waktu ?></span>
      </div>
      <div class="bukti-row">
        <span class="lbl">Bank Tujuan</span>
        <span class="val"><?= htmlspecialchars($bank) ?></span>
      </div>
    </div>

    <div class="bukti-divider"></div>

    <!-- Info Pengirim -->
    <div class="bukti-detail">
      <div class="bukti-row">
        <span class="lbl">Nama Pengirim</span>
        <span class="val" style="font-weight:700;"><?= strtoupper(htmlspecialchars($bukti['nama_pengirim'])) ?></span>
      </div>
      <div class="bukti-row">
        <span class="lbl">No. Rekening</span>
        <span class="val"><?= htmlspecialchars($bukti['no_rek']) ?></span>
      </div>
      <div class="bukti-row">
        <span class="lbl">Nominal</span>
        <span class="val"><?= number_format($bukti['total'], 0, ',', '.') ?></span>
      </div>
      <div class="bukti-row">
        <span class="lbl">Fee</span>
        <span class="val">0</span>
      </div>
    </div>

    <!-- Total -->
    <div class="bukti-total">
      <span class="lbl">Total</span>
      <span class="val"><?= number_format($bukti['total'], 0, ',', '.') ?></span>
    </div>

    <!-- Keterangan -->
    <div class="bukti-keterangan">
      <div class="lbl">Keterangan</div>
      <div class="val">Pembayaran pesanan <?= htmlspecialchars($bukti['kode']) ?></div>
    </div>

    <!-- Foto Bukti -->
    <?php if (!empty($bukti['bukti'])): ?>
    <div class="bukti-foto">
      <p><i class="fa fa-image"></i> Screenshot bukti transfer yang diupload:</p>
      <img src="uploads/<?= htmlspecialchars($bukti['bukti']) ?>" alt="Bukti Transfer">
    </div>
    <?php endif; ?>

  </div>

  <!-- Aksi -->
  <div class="bukti-actions">
    <a href="lacak-pesanan.php" class="btn-lacak-pesanan">
      <i class="fa fa-map-marker-alt"></i> Lacak Pesanan
    </a>
    <button class="btn-print" onclick="window.print()">
      <i class="fa fa-print"></i> Cetak / Simpan Bukti
    </button>
    <a href="index.php" class="btn-beranda">
      Kembali ke Beranda
    </a>
  </div>

</div>

<?php
// Bersihkan session setelah ditampilkan
unset($_SESSION['bukti_transfer']);
?>

</body>
</html>
