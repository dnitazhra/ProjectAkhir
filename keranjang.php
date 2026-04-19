<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header("Location: login.php"); exit; }

$keranjang  = getKeranjang($conn, $user['id_user']);
$subtotal   = array_sum(array_map(fn($i) => $i['harga'] * $i['jumlah'], $keranjang));
$ongkir     = 15000;
$diskon     = 0;
$total      = $subtotal + $ongkir - $diskon;
$cart_count = count($keranjang);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keranjang Saya - Happy Snack</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .keranjang-page {
      max-width: 1100px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    .keranjang-page-title {
      font-size: 22px;
      font-weight: 700;
      font-family: 'Times New Roman', serif;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .keranjang-page-title i { color: var(--primary); }

    /* ---- Layout ---- */
    .keranjang-layout {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: flex-start;
    }

    /* ---- Item List ---- */
    .keranjang-list { display: flex; flex-direction: column; gap: 16px; }

    .keranjang-item {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 16px;
      display: flex;
      gap: 14px;
      align-items: flex-start;
      transition: box-shadow var(--transition);
    }

    .keranjang-item:hover { box-shadow: var(--shadow-sm); }

    .item-img {
      width: 80px;
      height: 80px;
      border-radius: var(--radius-sm);
      background: var(--bg-card);
      flex-shrink: 0;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .item-img img { width: 100%; height: 100%; object-fit: cover; }
    .item-img i { font-size: 28px; color: var(--border); }

    .item-body { flex: 1; min-width: 0; }

    .item-nama {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 3px;
    }

    .item-varian {
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 10px;
    }

    .item-bottom {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }

    .item-harga {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-red);
    }

    .item-controls {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .qty-control {
      display: flex;
      align-items: center;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-full);
      overflow: hidden;
    }

    .qty-btn {
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      color: var(--text-dark);
      background: var(--bg-light);
      transition: background var(--transition);
    }

    .qty-btn:hover { background: var(--border); }

    .qty-val {
      width: 36px;
      text-align: center;
      font-size: 14px;
      font-weight: 700;
      border: none;
      outline: none;
      background: white;
    }

    .btn-hapus {
      color: #ef4444;
      font-size: 16px;
      padding: 4px 8px;
      border-radius: var(--radius-sm);
      transition: background var(--transition);
    }

    .btn-hapus:hover { background: #fef2f2; }

    /* Catatan */
    .item-catatan {
      margin-top: 10px;
      border-top: 1px dashed var(--border);
      padding-top: 10px;
    }

    .catatan-toggle {
      font-size: 12px;
      color: var(--primary);
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .catatan-input {
      margin-top: 8px;
      width: 100%;
      padding: 8px 12px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 13px;
      outline: none;
      resize: none;
      font-family: inherit;
      display: none;
    }

    .catatan-input:focus { border-color: var(--primary); }
    .catatan-input.show { display: block; }

    /* ---- Ringkasan ---- */
    .ringkasan-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      position: sticky;
      top: 80px;
    }

    .ringkasan-header {
      background: var(--primary);
      color: white;
      padding: 14px 20px;
      font-size: 15px;
      font-weight: 700;
    }

    .ringkasan-body { padding: 20px; }

    .ringkasan-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      font-size: 14px;
      color: var(--text-dark);
      border-bottom: 1px solid var(--border);
    }

    .ringkasan-row:last-of-type { border-bottom: none; }

    .ringkasan-row .label { color: var(--text-muted); }

    .ringkasan-row.diskon .value { color: var(--success); font-weight: 600; }

    .ringkasan-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 0 0;
      margin-top: 4px;
      border-top: 2px solid var(--border);
    }

    .ringkasan-total .label {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .ringkasan-total .value {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-red);
    }

    /* Voucher */
    .voucher-row {
      display: flex;
      gap: 8px;
      margin: 16px 0;
    }

    .voucher-row input {
      flex: 1;
      padding: 9px 12px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 13px;
      outline: none;
    }

    .voucher-row input:focus { border-color: var(--primary); }

    .btn-voucher {
      background: var(--bg-light);
      color: var(--primary);
      border: 1.5px solid var(--primary);
      border-radius: var(--radius-sm);
      padding: 9px 14px;
      font-size: 13px;
      font-weight: 600;
      transition: all var(--transition);
    }

    .btn-voucher:hover { background: var(--primary); color: white; }

    .btn-checkout {
      width: 100%;
      background: var(--primary);
      color: white;
      border-radius: var(--radius-lg);
      padding: 14px;
      font-size: 15px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 16px;
      transition: background var(--transition);
    }

    .btn-checkout:hover { background: var(--primary-dark); }

    .btn-lanjut-belanja {
      width: 100%;
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
      margin-top: 10px;
      transition: all var(--transition);
    }

    .btn-lanjut-belanja:hover { background: var(--primary); color: white; }

    /* Empty cart */
    .empty-cart {
      text-align: center;
      padding: 80px 20px;
      color: var(--text-muted);
    }

    .empty-cart i { font-size: 64px; opacity: 0.2; margin-bottom: 16px; }
    .empty-cart h3 { font-size: 20px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
    .empty-cart p { font-size: 14px; margin-bottom: 24px; }

    @media (max-width: 768px) {
      .keranjang-layout { grid-template-columns: 1fr; }
      .ringkasan-card { position: static; }
      .keranjang-page { padding: 16px; }
    }
  </style>
</head>
<body>

<!-- Sidebar & Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <span class="sidebar-logo">Happy Snack</span>
    <button class="sidebar-close" onclick="closeSidebar()"><i class="fa fa-times"></i></button>
  </div>
  <div class="sidebar-nav">
    <a href="index.php"><i class="fa fa-home"></i> Beranda</a>
    <a href="kategori.php"><i class="fa fa-th-large"></i> Kategori</a>
    <a href="keranjang.php" class="active"><i class="fa fa-shopping-cart"></i> Keranjang</a>
    <?php if ($user): ?>
      <a href="profil.php"><i class="fa fa-user"></i> Profil</a>
      <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
    <?php else: ?>
      <a href="login.php"><i class="fa fa-sign-in-alt"></i> Masuk</a>
      <a href="register.php"><i class="fa fa-user-plus"></i> Daftar</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Navbar -->
<header class="navbar">
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="Logo" onerror="this.style.display='none'">
    <h2>Happy Snack</h2>
  </div>
  <form class="navbar-search" action="kategori.php" method="GET">
    <button type="submit"><i class="fa fa-search"></i></button>
    <input type="text" name="q" placeholder="Cari produk...">
  </form>
  <div class="navbar-actions">
    <a href="<?= $user ? 'profil.php' : 'login.php' ?>" class="nav-btn"><i class="fa fa-user"></i></a>
    <a href="keranjang.php" class="nav-btn">
      <i class="fa fa-shopping-cart"></i>
      <?php if ($cart_count > 0): ?><span class="badge"><?= $cart_count ?></span><?php endif; ?>
    </a>
    <button class="nav-btn btn-menu" onclick="openSidebar()"><i class="fa fa-bars"></i></button>
  </div>
</header>

<!-- Konten -->
<div class="keranjang-page">

  <button class="btn-back" onclick="history.back()">
    <i class="fa fa-arrow-left"></i> Kembali
  </button>
  <h1 class="keranjang-page-title">
    <i class="fa fa-shopping-cart"></i> Keranjang Saya
  </h1>

  <?php if (empty($keranjang)): ?>
    <div class="empty-cart">
      <i class="fa fa-shopping-cart"></i>
      <h3>Keranjang masih kosong</h3>
      <p>Yuk, tambahkan produk favoritmu!</p>
      <a href="kategori.php" class="btn btn-primary">Mulai Belanja</a>
    </div>

  <?php else: ?>
  <div class="keranjang-layout">

    <!-- List Item -->
    <div class="keranjang-list" id="keranjangList">
      <?php foreach ($keranjang as $item): ?>
      <div class="keranjang-item" id="item-<?= $item['id_keranjang'] ?>">

        <!-- Gambar -->
        <div class="item-img">
          <?php if (!empty($item['gambar'])): ?>
            <img src="uploads/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>">
          <?php else: ?>
            <i class="fa fa-image"></i>
          <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="item-body">
          <div class="item-nama"><?= htmlspecialchars($item['nama']) ?></div>
          <div class="item-varian">Varian: <?= htmlspecialchars($item['varian'] ?? '-') ?></div>

          <div class="item-bottom">
            <div class="item-harga" id="harga-<?= $item['id_keranjang'] ?>">
              Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
            </div>
            <div class="item-controls">
              <div class="qty-control">
                <button class="qty-btn" onclick="ubahQty(<?= $item['id_keranjang'] ?>, -1)">
                  <i class="fa fa-minus"></i>
                </button>
                <input type="number"
                       class="qty-val"
                       id="qty-<?= $item['id_keranjang'] ?>"
                       value="<?= $item['jumlah'] ?>"
                       min="1"
                       data-harga="<?= $item['harga'] ?>"
                       onchange="updateHarga(<?= $item['id_keranjang'] ?>)">
                <button class="qty-btn" onclick="ubahQty(<?= $item['id_keranjang'] ?>, 1)">
                  <i class="fa fa-plus"></i>
                </button>
              </div>
              <button class="btn-hapus" onclick="hapusItem(<?= $item['id_keranjang'] ?>)" title="Hapus">
                <i class="fa fa-trash"></i>
              </button>
            </div>
          </div>

          <!-- Catatan -->
          <div class="item-catatan">
            <span class="catatan-toggle" onclick="toggleCatatan(<?= $item['id_keranjang'] ?>)">
              <i class="fa fa-sticky-note"></i> Tambah catatan
            </span>
            <textarea class="catatan-input"
                      id="catatan-<?= $item['id_keranjang'] ?>"
                      rows="2"
                      placeholder="Tulis catatan untuk produk ini..."><?= htmlspecialchars($item['catatan'] ?? '') ?></textarea>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

    <!-- Ringkasan -->
    <div class="ringkasan-card">
      <div class="ringkasan-header">
        <i class="fa fa-receipt"></i> Ringkasan Pembayaran
      </div>
      <div class="ringkasan-body">

        <div class="ringkasan-row">
          <span class="label">Subtotal (<?= count($keranjang) ?> item)</span>
          <span class="value" id="subtotal">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
        </div>
        <div class="ringkasan-row">
          <span class="label">Biaya Pengiriman</span>
          <span class="value">Rp <?= number_format($ongkir, 0, ',', '.') ?></span>
        </div>
        <div class="ringkasan-row diskon">
          <span class="label">Diskon Voucher</span>
          <span class="value">-Rp <?= number_format($diskon, 0, ',', '.') ?></span>
        </div>

        <!-- Voucher -->
        <div class="voucher-row">
          <input type="text" id="voucherInput" placeholder="Kode voucher...">
          <button class="btn-voucher" onclick="pakaiVoucher()">Pakai</button>
        </div>

        <div class="ringkasan-total">
          <span class="label">Total Pembayaran</span>
          <span class="value" id="total">Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>

        <a href="pembayaran.php" class="btn-checkout">
          <i class="fa fa-lock"></i> Lanjut ke Pembayaran
        </a>
        <a href="kategori.php" class="btn-lanjut-belanja">
          <i class="fa fa-arrow-left"></i> Lanjut Belanja
        </a>

      </div>
    </div>

  </div>
  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

<script>
const ongkir  = <?= $ongkir ?>;
const diskon  = <?= $diskon ?>;

function ubahQty(id, delta) {
  const input = document.getElementById('qty-' + id);
  let val = parseInt(input.value) + delta;
  if (val < 1) val = 1;
  input.value = val;
  updateHarga(id);
  // Sync ke DB
  fetch('ajax/update-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id_keranjang: id, jumlah: val })
  });
}

function updateHarga(id) {
  const input  = document.getElementById('qty-' + id);
  const harga  = parseInt(input.dataset.harga);
  const qty    = parseInt(input.value);
  document.getElementById('harga-' + id).textContent =
    'Rp ' + (harga * qty).toLocaleString('id-ID');
  hitungTotal();
  fetch('ajax/update-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id_keranjang: id, jumlah: qty })
  });
}

function hitungTotal() {
  let sub = 0;
  document.querySelectorAll('.qty-val').forEach(input => {
    sub += parseInt(input.dataset.harga) * parseInt(input.value);
  });
  document.getElementById('subtotal').textContent =
    'Rp ' + sub.toLocaleString('id-ID');
  document.getElementById('total').textContent =
    'Rp ' + (sub + ongkir - diskon).toLocaleString('id-ID');
}

function hapusItem(id) {
  if (!confirm('Hapus produk dari keranjang?')) return;
  fetch('ajax/hapus-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id_keranjang: id })
  }).then(r => r.json()).then(() => {
    const el = document.getElementById('item-' + id);
    el.style.opacity = '0';
    el.style.transform = 'translateX(20px)';
    el.style.transition = 'all 0.3s ease';
    setTimeout(() => { el.remove(); hitungTotal(); }, 300);
  });
}

function toggleCatatan(id) {
  const el = document.getElementById('catatan-' + id);
  el.classList.toggle('show');
  if (el.classList.contains('show')) el.focus();
}

function pakaiVoucher() {
  const kode     = document.getElementById('voucherInput').value.trim();
  const subtotal = <?= $subtotal ?>;
  if (!kode) return;
  fetch('ajax/cek-voucher.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ kode, subtotal })
  })
  .then(r => r.json())
  .then(data => {
    showToast(data.message);
    if (data.success) {
      diskon = data.diskon;
      document.getElementById('total').textContent =
        'Rp ' + (subtotal + ongkir - diskon).toLocaleString('id-ID');
    }
  });
}

function openSidebar() {
  document.getElementById('sidebar').classList.add('active');
  document.getElementById('sidebarOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('active');
  document.getElementById('sidebarOverlay').classList.remove('active');
  document.body.style.overflow = '';
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
