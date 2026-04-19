<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header("Location: login.php"); exit; }

$keranjang = getKeranjang($conn, $user['id_user']);
if (empty($keranjang)) { header("Location: keranjang.php"); exit; }

$subtotal = array_sum(array_map(fn($i) => $i['harga'] * $i['jumlah'], $keranjang));
$ongkir   = 15000;
$diskon   = 0;

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar'])) {
    $nama      = trim($_POST['nama'] ?? '');
    $telepon   = trim($_POST['telepon'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');
    $kota      = trim($_POST['kota'] ?? '');
    $kode_pos  = trim($_POST['kodepos'] ?? '');
    $kurir     = $_POST['kurir'] ?? 'jne';
    $bayar     = $_POST['pembayaran'] ?? 'gopay';
    $ongkir    = $kurir === 'sicepat' ? 25000 : 15000;

    // Cek voucher
    $kode_voucher = trim($_POST['voucher'] ?? '');
    if ($kode_voucher) {
        $voucher = cekVoucher($conn, $kode_voucher, $subtotal);
        if ($voucher) $diskon = $voucher['diskon'];
    }

    $total = $subtotal + $ongkir - $diskon;

    if ($nama && $telepon && $alamat && $kota) {
        $data_pesanan = [
            'id_user'   => $user['id_user'],
            'nama'      => $nama,
            'telepon'   => $telepon,
            'alamat'    => $alamat,
            'kota'      => $kota,
            'kode_pos'  => $kode_pos,
            'kurir'     => $kurir,
            'pembayaran'=> $bayar,
            'subtotal'  => $subtotal,
            'ongkir'    => $ongkir,
            'diskon'    => $diskon,
            'total'     => $total,
        ];

        $pesanan = buatPesanan($conn, $data_pesanan);

        // Insert detail
        $items = array_map(fn($k) => [
            'id_produk' => $k['id_produk'],
            'varian'    => $k['varian'] ?? '',
            'jumlah'    => $k['jumlah'],
            'harga'     => $k['harga'],
            'catatan'   => $k['catatan'] ?? '',
        ], $keranjang);

        insertDetailPesanan($conn, $pesanan['id'], $items);
        kosongkanKeranjang($conn, $user['id_user']);

        $_SESSION['pesanan_kode']  = $pesanan['kode'];
        $_SESSION['pesanan_total'] = $total;
        $_SESSION['pesanan_alamat']= $alamat;
        header("Location: pesanan-berhasil.php");
        exit;
    }
}

$total      = $subtotal + $ongkir - $diskon;
$cart_count = count($keranjang);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran - Happy Snack</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .bayar-page {
      max-width: 900px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    .bayar-page-title {
      font-size: 22px;
      font-weight: 700;
      font-family: 'Times New Roman', serif;
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .bayar-page-title i { color: var(--primary); }

    /* Steps */
    .steps {
      display: flex;
      align-items: center;
      gap: 0;
      margin-bottom: 32px;
    }

    .step {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
    }

    .step-num {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: var(--border);
      color: var(--text-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
    }

    .step.active .step-num {
      background: var(--primary);
      color: white;
    }

    .step.active { color: var(--primary); }

    .step.done .step-num {
      background: var(--success);
      color: white;
    }

    .step-line {
      flex: 1;
      height: 2px;
      background: var(--border);
      margin: 0 8px;
    }

    /* Layout */
    .bayar-layout {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 24px;
      align-items: flex-start;
    }

    /* Section Card */
    .bayar-section {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 16px;
    }

    .bayar-section-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 20px;
      background: var(--bg-light);
      border-bottom: 1px solid var(--border);
    }

    .bayar-section-header .num {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .bayar-section-header h3 {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .bayar-section-body { padding: 20px; }

    /* Form grid */
    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    /* Metode pilihan */
    .metode-list { display: flex; flex-direction: column; gap: 10px; }

    .metode-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: all var(--transition);
    }

    .metode-item:hover { border-color: var(--primary); background: #fff5f0; }

    .metode-item.active {
      border-color: var(--primary);
      background: #fff5f0;
    }

    .metode-item input[type="radio"] { accent-color: var(--primary); width: 16px; height: 16px; }

    .metode-icon {
      width: 36px;
      height: 36px;
      border-radius: var(--radius-sm);
      background: var(--bg-card);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      color: var(--primary);
    }

    .metode-info { flex: 1; }
    .metode-nama { font-size: 14px; font-weight: 700; color: var(--text-dark); }
    .metode-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .metode-harga { font-size: 14px; font-weight: 700; color: var(--text-dark); }

    /* Ringkasan sidebar */
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
      padding: 8px 0;
      font-size: 14px;
      border-bottom: 1px solid var(--border);
    }

    .ringkasan-row:last-of-type { border-bottom: none; }
    .ringkasan-row .label { color: var(--text-muted); }
    .ringkasan-row.diskon .value { color: var(--success); font-weight: 600; }

    .ringkasan-total {
      display: flex;
      justify-content: space-between;
      padding: 14px 0 0;
      margin-top: 4px;
      border-top: 2px solid var(--border);
    }

    .ringkasan-total .label { font-size: 15px; font-weight: 700; }
    .ringkasan-total .value { font-size: 20px; font-weight: 700; color: var(--text-red); }

    .btn-bayar {
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
      border: none;
      cursor: pointer;
    }

    .btn-bayar:hover { background: var(--primary-dark); }

    .secure-note {
      text-align: center;
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    @media (max-width: 768px) {
      .bayar-layout { grid-template-columns: 1fr; }
      .ringkasan-card { position: static; }
      .form-grid-2 { grid-template-columns: 1fr; }
      .bayar-page { padding: 16px; }
      .steps { font-size: 11px; }
    }
  </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <span class="sidebar-logo">Happy Snack</span>
    <button class="sidebar-close" onclick="closeSidebar()"><i class="fa fa-times"></i></button>
  </div>
  <div class="sidebar-nav">
    <a href="index.php"><i class="fa fa-home"></i> Beranda</a>
    <a href="kategori.php"><i class="fa fa-th-large"></i> Kategori</a>
    <a href="keranjang.php"><i class="fa fa-shopping-cart"></i> Keranjang</a>
    <?php if ($user): ?>
      <a href="profil.php"><i class="fa fa-user"></i> Profil</a>
      <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
    <?php else: ?>
      <a href="login.php"><i class="fa fa-sign-in-alt"></i> Masuk</a>
    <?php endif; ?>
  </div>
</nav>

<header class="navbar">
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="Logo" onerror="this.style.display='none'">
    <h2>Happy Snack</h2>
  </div>
  <div class="navbar-search" style="pointer-events:none; opacity:0.5;">
    <i class="fa fa-search" style="color:var(--text-muted)"></i>
    <input type="text" placeholder="Cari produk..." disabled>
  </div>
  <div class="navbar-actions">
    <a href="keranjang.php" class="nav-btn">
      <i class="fa fa-shopping-cart"></i>
      <span class="badge"><?= $cart_count ?></span>
    </a>
    <button class="nav-btn btn-menu" onclick="openSidebar()"><i class="fa fa-bars"></i></button>
  </div>
</header>

<div class="bayar-page">

  <button class="btn-back" onclick="history.back()">
    <i class="fa fa-arrow-left"></i> Kembali ke Keranjang
  </button>
  <h1 class="bayar-page-title"><i class="fa fa-credit-card"></i> Checkout</h1>

  <!-- Steps -->
  <div class="steps">
    <div class="step done">
      <div class="step-num"><i class="fa fa-check"></i></div>
      <span>Keranjang</span>
    </div>
    <div class="step-line"></div>
    <div class="step active">
      <div class="step-num">2</div>
      <span>Pengiriman</span>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-num">3</div>
      <span>Selesai</span>
    </div>
  </div>

  <form action="pembayaran.php" method="POST" onsubmit="return validasiForm()">
  <div class="bayar-layout">

    <div>
      <!-- Informasi Kontak -->
      <div class="bayar-section">
        <div class="bayar-section-header">
          <div class="num">1</div>
          <h3>Informasi Kontak</h3>
        </div>
        <div class="bayar-section-body">
          <div class="form-grid-2">
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" class="form-control"
                     placeholder="Masukkan nama lengkap"
                     value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Nomor Telepon</label>
              <input type="tel" name="telepon" class="form-control"
                     placeholder="0812 XXXX XXXX" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Alamat Pengiriman -->
      <div class="bayar-section">
        <div class="bayar-section-header">
          <div class="num">2</div>
          <h3>Alamat Pengiriman</h3>
        </div>
        <div class="bayar-section-body">
          <div class="form-group">
            <label>Alamat Jalan</label>
            <input type="text" name="alamat" class="form-control"
                   placeholder="Nama jalan, nomor rumah, blok..." required>
          </div>
          <div class="form-grid-2">
            <div class="form-group">
              <label>Kota</label>
              <input type="text" name="kota" class="form-control"
                     placeholder="Jakarta Selatan" required>
            </div>
            <div class="form-group">
              <label>Kode Pos</label>
              <input type="text" name="kodepos" class="form-control"
                     placeholder="12345" maxlength="5" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Metode Pengiriman -->
      <div class="bayar-section">
        <div class="bayar-section-header">
          <div class="num">3</div>
          <h3>Metode Pengiriman</h3>
        </div>
        <div class="bayar-section-body">
          <div class="metode-list">
            <label class="metode-item active" onclick="pilihMetode(this)">
              <input type="radio" name="kurir" value="jne" checked>
              <div class="metode-icon"><i class="fa fa-truck"></i></div>
              <div class="metode-info">
                <div class="metode-nama">JNE Reguler</div>
                <div class="metode-desc">Estimasi tiba: 2-3 hari</div>
              </div>
              <div class="metode-harga">Rp 15.000</div>
            </label>
            <label class="metode-item" onclick="pilihMetode(this)">
              <input type="radio" name="kurir" value="sicepat">
              <div class="metode-icon"><i class="fa fa-bolt"></i></div>
              <div class="metode-info">
                <div class="metode-nama">SiCepat Best</div>
                <div class="metode-desc">Estimasi tiba: Esok hari</div>
              </div>
              <div class="metode-harga">Rp 25.000</div>
            </label>
          </div>
        </div>
      </div>

      <!-- Metode Pembayaran -->
      <div class="bayar-section">
        <div class="bayar-section-header">
          <div class="num">4</div>
          <h3>Metode Pembayaran</h3>
        </div>
        <div class="bayar-section-body">
          <div class="metode-list">
            <label class="metode-item active" onclick="pilihMetode(this)">
              <input type="radio" name="pembayaran" value="gopay" checked>
              <div class="metode-icon" style="color:#00AED6;"><i class="fa fa-wallet"></i></div>
              <div class="metode-info">
                <div class="metode-nama">GoPay</div>
                <div class="metode-desc">Bayar dengan saldo GoPay</div>
              </div>
            </label>
            <label class="metode-item" onclick="pilihMetode(this)">
              <input type="radio" name="pembayaran" value="transfer">
              <div class="metode-icon" style="color:#1a56db;"><i class="fa fa-university"></i></div>
              <div class="metode-info">
                <div class="metode-nama">Bank Transfer</div>
                <div class="metode-desc">BCA, Mandiri, BNI, BRI</div>
              </div>
            </label>
            <label class="metode-item" onclick="pilihMetode(this)">
              <input type="radio" name="pembayaran" value="kartu">
              <div class="metode-icon" style="color:#7c3aed;"><i class="fa fa-credit-card"></i></div>
              <div class="metode-info">
                <div class="metode-nama">Kartu Kredit / Debit</div>
                <div class="metode-desc">Visa, Mastercard</div>
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Ringkasan -->
    <div class="ringkasan-card">
      <div class="ringkasan-header"><i class="fa fa-receipt"></i> Ringkasan Pesanan</div>
      <div class="ringkasan-body">
        <div class="ringkasan-row">
          <span class="label">Subtotal</span>
          <span class="value">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
        </div>
        <div class="ringkasan-row">
          <span class="label">Pengiriman</span>
          <span class="value" id="hargaKurir">Rp <?= number_format($ongkir, 0, ',', '.') ?></span>
        </div>
        <div class="ringkasan-row diskon">
          <span class="label">Diskon</span>
          <span class="value">-Rp <?= number_format($diskon, 0, ',', '.') ?></span>
        </div>
        <div class="ringkasan-total">
          <span class="label">Total Tagihan</span>
          <span class="value" id="totalBayar">Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
        <button type="submit" name="bayar" class="btn-bayar">
          <i class="fa fa-lock"></i> Bayar Sekarang
        </button>
        <div class="secure-note">
          <i class="fa fa-shield-alt"></i> Transaksi aman & terenkripsi
        </div>
      </div>
    </div>

  </div>
  </form>
</div>

<?php include 'includes/footer.php'; ?>

<script>
const subtotal = <?= $subtotal ?>;
const diskon   = <?= $diskon ?>;
const kurirHarga = { jne: 15000, sicepat: 25000 };

function pilihMetode(el) {
  const group = el.closest('.metode-list');
  group.querySelectorAll('.metode-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');

  // Update ongkir jika kurir
  const radio = el.querySelector('input[name="kurir"]');
  if (radio) {
    const h = kurirHarga[radio.value] || 15000;
    document.getElementById('hargaKurir').textContent =
      'Rp ' + h.toLocaleString('id-ID');
    document.getElementById('totalBayar').textContent =
      'Rp ' + (subtotal + h - diskon).toLocaleString('id-ID');
  }
}

function validasiForm() {
  const nama    = document.querySelector('[name="nama"]').value.trim();
  const telepon = document.querySelector('[name="telepon"]').value.trim();
  const alamat  = document.querySelector('[name="alamat"]').value.trim();
  if (!nama || !telepon || !alamat) {
    alert('Lengkapi semua informasi pengiriman terlebih dahulu.');
    return false;
  }
  return true;
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
</script>
</body>
</html>
