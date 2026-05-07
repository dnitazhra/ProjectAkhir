<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header("Location: login.php"); exit; }

$cart_count = getCartCount($conn, $user['id_user']);
$kode       = $_GET['kode'] ?? '';

// Ambil pesanan dari DB
if ($kode) {
    $pesanan_db = getPesananById($conn, $kode, $user['id_user']);
} else {
    // Ambil pesanan terbaru user
    $list = getPesananUser($conn, $user['id_user']);
    $pesanan_db = $list[0] ?? null;
}

if (!$pesanan_db) { header("Location: profil.php"); exit; }

$items_db = getDetailPesanan($conn, $pesanan_db['id_pesanan']);

$pesanan = [
    'no'      => $pesanan_db['kode'],
    'status'  => $pesanan_db['status'],
    'est'     => 'Hari Ini',
    'kurir'   => $pesanan_db['kurir'],
    'resi'    => 'SCPT' . rand(100000000, 999999999),
    'alamat'  => $pesanan_db['alamat'] . ', ' . $pesanan_db['kota'] . ' ' . $pesanan_db['kode_pos'],
    'telepon' => $pesanan_db['telepon'],
    'nama'    => $pesanan_db['nama'],
    'bayar'   => $pesanan_db['pembayaran'],
    'items'   => array_map(fn($i) => [
        'nama'   => $i['nama'],
        'varian' => $i['varian'] ?? '-',
        'jumlah' => $i['jumlah'],
        'harga'  => $i['harga'],
    ], $items_db),
    'subtotal' => $pesanan_db['subtotal'],
    'ongkir'   => $pesanan_db['ongkir'],
    'diskon'   => $pesanan_db['diskon'],
    'total'    => $pesanan_db['total'],
];

$steps = [
    'pending'   => ['label'=>'Pending',   'icon'=>'fa-clock'],
    'diproses'  => ['label'=>'Diproses',  'icon'=>'fa-cog'],
    'dikemas'   => ['label'=>'Dikemas',   'icon'=>'fa-box'],
    'dikirim'   => ['label'=>'Dikirim',   'icon'=>'fa-truck'],
    'selesai'   => ['label'=>'Selesai',   'icon'=>'fa-check-circle'],
];

$step_keys  = array_keys($steps);
$step_aktif = array_search($pesanan['status'], $step_keys);
if ($step_aktif === false) $step_aktif = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lacak Pesanan - lavo.id</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .lacak-page {
      max-width: 800px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    .lacak-page-title {
      font-size: 22px;
      font-weight: 700;
      font-family: 'Times New Roman', serif;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .lacak-page-title i { color: var(--primary); }

    /* ---- Status Card ---- */
    .status-card {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      border-radius: var(--radius-md);
      padding: 20px 24px;
      color: white;
      margin-bottom: 20px;
    }

    .status-card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 16px;
    }

    .status-no { font-size: 13px; opacity: 0.85; margin-bottom: 4px; }

    .status-label {
      font-size: 20px;
      font-weight: 700;
    }

    .status-est {
      background: rgba(255,255,255,0.2);
      padding: 6px 14px;
      border-radius: var(--radius-full);
      font-size: 12px;
      font-weight: 700;
      text-align: center;
    }

    /* Progress Steps */
    .progress-steps {
      display: flex;
      align-items: center;
      gap: 0;
      margin-top: 8px;
    }

    .progress-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      flex: 1;
    }

    .progress-step-icon {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(255,255,255,0.25);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      color: rgba(255,255,255,0.7);
      transition: all 0.3s;
    }

    .progress-step.done .progress-step-icon,
    .progress-step.active .progress-step-icon {
      background: white;
      color: var(--primary);
    }

    .progress-step-label {
      font-size: 10px;
      font-weight: 600;
      color: rgba(255,255,255,0.7);
      text-align: center;
    }

    .progress-step.done .progress-step-label,
    .progress-step.active .progress-step-label {
      color: white;
    }

    .progress-line {
      flex: 1;
      height: 2px;
      background: rgba(255,255,255,0.25);
      margin-bottom: 22px;
    }

    .progress-line.done { background: white; }

    /* ---- Section Card ---- */
    .info-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 16px;
    }

    .info-card-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 20px;
      background: var(--bg-light);
      border-bottom: 1px solid var(--border);
      font-size: 14px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .info-card-header i { color: var(--primary); }
    .info-card-body { padding: 16px 20px; }

    /* Item pesanan */
    .order-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
      gap: 12px;
    }

    .order-item:last-child { border-bottom: none; }

    .order-item-left { flex: 1; }
    .order-item-nama { font-size: 14px; font-weight: 600; color: var(--text-dark); }
    .order-item-varian { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    .order-item-right { text-align: right; flex-shrink: 0; }
    .order-item-qty { font-size: 12px; color: var(--text-muted); }
    .order-item-harga { font-size: 14px; font-weight: 700; color: var(--text-red); }

    /* Info pengiriman */
    .info-row {
      display: flex;
      gap: 12px;
      padding: 8px 0;
      border-bottom: 1px solid var(--border);
      font-size: 14px;
    }

    .info-row:last-child { border-bottom: none; }
    .info-row .lbl { color: var(--text-muted); min-width: 110px; flex-shrink: 0; }
    .info-row .val { font-weight: 600; color: var(--text-dark); }

    .resi-copy {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--bg-light);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 4px 10px;
      font-size: 13px;
      font-weight: 700;
      font-family: monospace;
      cursor: pointer;
      transition: background var(--transition);
    }

    .resi-copy:hover { background: var(--border); }

    /* Ringkasan bayar */
    .bayar-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      padding: 6px 0;
      color: var(--text-muted);
    }

    .bayar-row.total {
      border-top: 1.5px solid var(--border);
      margin-top: 6px;
      padding-top: 10px;
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .bayar-row.total .val { color: var(--text-red); }

    /* Aksi bawah */
    .lacak-actions {
      display: flex;
      gap: 12px;
      margin-top: 8px;
      flex-wrap: wrap;
    }

    .btn-ulasan {
      flex: 1;
      background: var(--primary);
      color: white;
      border-radius: var(--radius-lg);
      padding: 12px;
      font-size: 14px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background var(--transition);
      min-width: 140px;
    }

    .btn-ulasan:hover { background: var(--primary-dark); }

    .btn-selesai {
      flex: 1;
      background: var(--success);
      color: white;
      border-radius: var(--radius-lg);
      padding: 12px;
      font-size: 14px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background var(--transition);
      min-width: 140px;
    }

    .btn-selesai:hover { background: #16a34a; }

    @media (max-width: 600px) {
      .lacak-page { padding: 16px; }
      .progress-step-label { font-size: 9px; }
      .lacak-actions { flex-direction: column; }
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="lacak-page">

  <button class="btn-back" onclick="history.back()">
    <i class="fa fa-arrow-left"></i> Kembali
  </button>
  <h1 class="lacak-page-title"><i class="fa fa-map-marker-alt"></i> Lacak Pesanan</h1>

  <!-- Status Card -->
  <?php if ($pesanan['status'] === 'ditolak'): ?>
  <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-md);padding:20px 24px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:48px;height:48px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fa fa-ban" style="color:#dc2626;font-size:20px;"></i>
      </div>
      <div>
        <div style="font-size:16px;font-weight:700;color:#dc2626;">Pesanan Ditolak</div>
        <div style="font-size:13px;color:#6b7280;margin-top:2px;">
          Pesanan <strong><?= $pesanan['no'] ?></strong> telah ditolak oleh admin.
          Kemungkinan karena bukti transfer tidak valid atau alasan lainnya.
        </div>
      </div>
    </div>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid #fecaca;">
      <a href="index.php" style="background:#dc2626;color:white;padding:10px 20px;border-radius:9999px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <i class="fa fa-home"></i> Kembali ke Beranda
      </a>
      <a href="keranjang.php" style="margin-left:10px;background:white;color:#dc2626;border:1px solid #fecaca;padding:10px 20px;border-radius:9999px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <i class="fa fa-shopping-cart"></i> Pesan Ulang
      </a>
    </div>
  </div>
  <?php else: ?>
  <div class="status-card">
    <div class="status-card-top">
      <div>
        <div class="status-no">No. Pesanan: <?= $pesanan['no'] ?></div>
        <div class="status-label">
          <?= $steps[$pesanan['status'] ?? 'pending']['label'] ?? 'Pending' ?>
        </div>
      </div>
      <div class="status-est">EST. <?= strtoupper($pesanan['est']) ?></div>
    </div>

    <!-- Progress -->
    <div class="progress-steps">
      <?php foreach ($step_keys as $i => $key):
        $is_done   = $i < $step_aktif;
        $is_active = $i === $step_aktif;
        $class     = $is_done ? 'done' : ($is_active ? 'active' : '');
      ?>
        <?php if ($i > 0): ?>
          <div class="progress-line <?= $is_done ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <div class="progress-step <?= $class ?>">
          <div class="progress-step-icon">
            <i class="fa <?= $steps[$key]['icon'] ?>"></i>
          </div>
          <div class="progress-step-label"><?= $steps[$key]['label'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Item Pesanan -->
  <div class="info-card">
    <div class="info-card-header">
      <i class="fa fa-box"></i> Item Pesanan
    </div>
    <div class="info-card-body">
      <?php foreach ($pesanan['items'] as $item): ?>
      <div class="order-item">
        <div class="order-item-left">
          <div class="order-item-nama"><?= htmlspecialchars($item['nama']) ?></div>
          <div class="order-item-varian">Varian: <?= htmlspecialchars($item['varian']) ?></div>
        </div>
        <div class="order-item-right">
          <div class="order-item-qty"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
          <div class="order-item-harga">Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Info Pengiriman -->
  <div class="info-card">
    <div class="info-card-header">
      <i class="fa fa-truck"></i> Informasi Pengiriman
    </div>
    <div class="info-card-body">
      <div class="info-row">
        <span class="lbl">Kurir</span>
        <span class="val"><?= htmlspecialchars($pesanan['kurir']) ?></span>
      </div>
      <div class="info-row">
        <span class="lbl">No. Resi</span>
        <span class="val">
          <span class="resi-copy" onclick="copyResi('<?= $pesanan['resi'] ?>')">
            <?= $pesanan['resi'] ?> <i class="fa fa-copy"></i>
          </span>
        </span>
      </div>
      <div class="info-row">
        <span class="lbl">Alamat</span>
        <span class="val"><?= htmlspecialchars($pesanan['nama']) ?><br>
          <span style="font-weight:400; color:var(--text-muted);"><?= htmlspecialchars($pesanan['alamat']) ?><br><?= $pesanan['telepon'] ?></span>
        </span>
      </div>
    </div>
  </div>

  <!-- Ringkasan Pembayaran -->
  <div class="info-card">
    <div class="info-card-header">
      <i class="fa fa-receipt"></i> Ringkasan Pembayaran
    </div>
    <div class="info-card-body">
      <div class="bayar-row">
        <span>Subtotal (<?= count($pesanan['items']) ?> item)</span>
        <span>Rp <?= number_format($pesanan['subtotal'], 0, ',', '.') ?></span>
      </div>
      <div class="bayar-row">
        <span>Ongkos Kirim</span>
        <span>Rp <?= number_format($pesanan['ongkir'], 0, ',', '.') ?></span>
      </div>
      <div class="bayar-row">
        <span>Diskon Voucher</span>
        <span style="color:var(--success);">-Rp <?= number_format($pesanan['diskon'], 0, ',', '.') ?></span>
      </div>
      <div class="bayar-row total">
        <span>Total Pembayaran</span>
        <span class="val">Rp <?= number_format($pesanan['total'], 0, ',', '.') ?></span>
      </div>
      <div style="margin-top:10px; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:6px;">
        <i class="fa fa-wallet" style="color:var(--primary);"></i>
        Dibayar menggunakan <?= htmlspecialchars($pesanan['bayar']) ?>
      </div>
    </div>
  </div>

  <!-- Aksi -->
  <?php if ($pesanan['status'] === 'dikirim' || $pesanan['status'] === 'selesai'): ?>
  <div class="lacak-actions">
    <?php
    // Ambil produk pertama dari pesanan untuk link ulasan
    $first_item = $pesanan['items'][0] ?? null;
    $ulasan_url = 'ulasan.php?id_pesanan=' . ($pesanan_db['id_pesanan'] ?? 0);
    if ($first_item) {
        // Cari id_produk dari detail
        $ulasan_url .= '&id_produk=' . ($items_db[0]['id_produk'] ?? 0);
        $ulasan_url .= '&varian=' . urlencode($first_item['varian'] ?? '');
    }
    ?>
    <a href="<?= $ulasan_url ?>" class="btn-ulasan">
      <i class="fa fa-star"></i> Tulis Ulasan
    </a>
    <button class="btn-selesai" onclick="pesananSelesai()">
      <i class="fa fa-check"></i> Pesanan Selesai
    </button>
  </div>
  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

<script>
function copyResi(resi) {
  navigator.clipboard.writeText(resi).then(() => showToast('No. resi disalin!'));
}

function pesananSelesai() {
  if (!confirm('Konfirmasi pesanan sudah diterima dan selesai?')) return;
  const kode = '<?= $pesanan['no'] ?>';
  fetch('ajax/selesai-pesanan.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ kode: kode })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('Pesanan ditandai selesai! Terima kasih 🎉');
      setTimeout(() => window.location.reload(), 1500);
    } else {
      showToast(data.message);
    }
  });
}

function showToast(msg) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
    background:#1a1a1a;color:white;padding:12px 24px;border-radius:9999px;
    font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.3)`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2500);
}
</script>
</body>
</html>
