<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;

// Filter & search
$kategori_aktif = $_GET['kategori'] ?? 'Semua';
$search         = trim($_GET['q'] ?? '');

// Dari DB
$kategori_db   = getKategoriAll($conn);
$kategori_list = array_merge(['Semua'], array_column($kategori_db, 'nama'));
$produk_tampil = getProdukFilter($conn, $kategori_aktif, $search);
$counts        = getKategoriCount($conn);
$cart_count    = $user ? getCartCount($conn, $user['id_user']) : 0;
$search         = trim($_GET['q'] ?? '');

// Data dummy (nanti diganti query DB)
$semua_produk = [
    ['id' => 1, 'nama' => 'Basreng',         'harga' => 15000, 'kategori' => 'Snack Kering', 'gambar' => '', 'satuan' => '250g', 'stok' => 50],
    ['id' => 2, 'nama' => 'Kripik Kaca',     'harga' => 10000, 'kategori' => 'Snack Kering', 'gambar' => '', 'satuan' => '250g', 'stok' => 50],
    ['id' => 3, 'nama' => 'Seblak Kering',   'harga' => 15000, 'kategori' => 'Snack Kering', 'gambar' => '', 'satuan' => '250g', 'stok' => 50],
    ['id' => 4, 'nama' => 'Makaroni Kering', 'harga' => 7000,  'kategori' => 'Snack Kering', 'gambar' => '', 'satuan' => '250g', 'stok' => 50],
    ['id' => 5, 'nama' => 'Brownis',         'harga' => 20000, 'kategori' => 'Kue Kering',   'gambar' => '', 'satuan' => '250g', 'stok' => 50],
    ['id' => 6, 'nama' => 'Cookies',         'harga' => 5000,  'kategori' => 'Kue Kering',   'gambar' => '', 'satuan' => 'pcs',  'stok' => 50],
    ['id' => 7, 'nama' => 'Kastengel',       'harga' => 35000, 'kategori' => 'Kue Kering',   'gambar' => '', 'satuan' => '250g', 'stok' => 50],
    ['id' => 8, 'nama' => 'Nastar',          'harga' => 35000, 'kategori' => 'Kue Kering',   'gambar' => '', 'satuan' => '250g', 'stok' => 50],
];

// Filter
$produk_tampil = array_filter($semua_produk, function($p) use ($kategori_aktif, $search) {
    $cocok_kategori = ($kategori_aktif === 'Semua') || ($p['kategori'] === $kategori_aktif);
    $cocok_search   = empty($search) || stripos($p['nama'], $search) !== false;
    return $cocok_kategori && $cocok_search;
});

$kategori_list = ['Semua', 'Snack Kering', 'Kue Kering'];
$cart_count    = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Produk - Happy Snack</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* ---- Layout Kategori ---- */
    .kategori-page {
      display: flex;
      max-width: 1200px;
      margin: 0 auto;
      padding: 32px 24px;
      gap: 28px;
      align-items: flex-start;
    }

    /* ---- Sidebar Filter ---- */
    .filter-sidebar {
      width: 220px;
      flex-shrink: 0;
      position: sticky;
      top: 80px;
    }

    .filter-card {
      background: var(--bg-white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
    }

    .filter-card-header {
      background: var(--primary);
      color: white;
      padding: 14px 18px;
      font-size: 14px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-list {
      padding: 8px 0;
    }

    .filter-item {
      display: block;
      padding: 11px 18px;
      font-size: 14px;
      font-weight: 500;
      color: var(--text-dark);
      cursor: pointer;
      transition: background var(--transition), color var(--transition);
      border-left: 3px solid transparent;
      text-decoration: none;
    }

    .filter-item:hover {
      background: var(--bg-light);
      color: var(--primary);
    }

    .filter-item.active {
      background: #fff5f0;
      color: var(--primary);
      border-left-color: var(--primary);
      font-weight: 700;
    }

    .filter-item .count {
      float: right;
      background: var(--bg-card);
      color: var(--text-muted);
      font-size: 11px;
      padding: 2px 7px;
      border-radius: var(--radius-full);
    }

    .filter-item.active .count {
      background: var(--primary);
      color: white;
    }

    /* ---- Main Content ---- */
    .produk-main {
      flex: 1;
      min-width: 0;
    }

    .produk-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .produk-toolbar-left h2 {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-dark);
      font-family: 'Times New Roman', serif;
    }

    .produk-toolbar-left p {
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .toolbar-search {
      display: flex;
      align-items: center;
      background: var(--bg-light);
      border: 1px solid var(--border);
      border-radius: var(--radius-full);
      padding: 0 14px;
      gap: 8px;
      height: 40px;
    }

    .toolbar-search input {
      border: none;
      background: transparent;
      outline: none;
      font-size: 13px;
      width: 180px;
      color: var(--text-dark);
    }

    .toolbar-search input::placeholder { color: var(--text-muted); }
    .toolbar-search i { color: var(--text-muted); font-size: 13px; }

    /* ---- Breadcrumb ---- */
    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 24px;
    }

    .breadcrumb a {
      color: var(--primary);
      font-weight: 500;
    }

    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }

    /* ---- Empty State ---- */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 16px;
      opacity: 0.3;
    }

    .empty-state h3 {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .empty-state p { font-size: 14px; }

    /* ---- Mobile Filter Toggle ---- */
    .btn-filter-toggle {
      display: none;
      align-items: center;
      gap: 8px;
      background: var(--primary);
      color: white;
      padding: 9px 16px;
      border-radius: var(--radius-full);
      font-size: 13px;
      font-weight: 600;
    }

    @media (max-width: 768px) {
      .kategori-page { flex-direction: column; padding: 16px; gap: 16px; }
      .filter-sidebar { width: 100%; position: static; display: none; }
      .filter-sidebar.open { display: block; }
      .btn-filter-toggle { display: flex; }
      .produk-toolbar { flex-direction: column; align-items: flex-start; }
      .toolbar-search input { width: 140px; }
    }
  </style>
</head>
<body>

<!-- ===== SIDEBAR OVERLAY ===== -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===== SIDEBAR MOBILE ===== -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <span class="sidebar-logo">Happy Snack</span>
    <button class="sidebar-close" onclick="closeSidebar()"><i class="fa fa-times"></i></button>
  </div>
  <div class="sidebar-nav">
    <a href="index.php"><i class="fa fa-home"></i> Beranda</a>
    <a href="kategori.php" class="active"><i class="fa fa-th-large"></i> Kategori</a>
    <a href="keranjang.php"><i class="fa fa-shopping-cart"></i> Keranjang</a>
    <?php if ($user): ?>
      <a href="profil.php"><i class="fa fa-user"></i> Profil</a>
      <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
    <?php else: ?>
      <a href="login.php"><i class="fa fa-sign-in-alt"></i> Masuk</a>
      <a href="register.php"><i class="fa fa-user-plus"></i> Daftar</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ===== NAVBAR ===== -->
<header class="navbar">
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="Logo" onerror="this.style.display='none'">
    <h2>Happy Snack</h2>
  </div>
  <form class="navbar-search" action="kategori.php" method="GET">
    <?php if ($kategori_aktif !== 'Semua'): ?>
      <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_aktif) ?>">
    <?php endif; ?>
    <button type="submit"><i class="fa fa-search"></i></button>
    <input type="text" name="q" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
  </form>
  <div class="navbar-actions">
    <a href="<?= $user ? 'profil.php' : 'login.php' ?>" class="nav-btn">
      <i class="fa fa-user"></i>
    </a>
    <a href="keranjang.php" class="nav-btn">
      <i class="fa fa-shopping-cart"></i>
      <?php if ($cart_count > 0): ?>
        <span class="badge"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>
    <button class="nav-btn btn-menu" onclick="openSidebar()">
      <i class="fa fa-bars"></i>
    </button>
  </div>
</header>

<!-- ===== KONTEN ===== -->
<div class="kategori-page">

  <!-- Sidebar Filter -->
  <aside class="filter-sidebar" id="filterSidebar">
    <div class="filter-card">
      <div class="filter-card-header">
        <i class="fa fa-filter"></i> Kategori Produk
      </div>
      <div class="filter-list">
        <?php
        $counts = ['Semua' => count($semua_produk)];
        foreach ($semua_produk as $p) {
            $counts[$p['kategori']] = ($counts[$p['kategori']] ?? 0) + 1;
        }
        foreach ($kategori_list as $kat):
          $is_active = ($kategori_aktif === $kat);
          $url = 'kategori.php?kategori=' . urlencode($kat);
          if ($search) $url .= '&q=' . urlencode($search);
        ?>
        <a href="<?= $url ?>" class="filter-item <?= $is_active ? 'active' : '' ?>">
          <?= htmlspecialchars($kat) ?>
          <span class="count"><?= $counts[$kat] ?? 0 ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>

  <!-- Main Produk -->
  <div class="produk-main">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="index.php"><i class="fa fa-chevron-left"></i> Beranda</a>
      <i class="fa fa-chevron-right"></i>
      <span>Produk</span>
      <?php if ($kategori_aktif !== 'Semua'): ?>
        <i class="fa fa-chevron-right"></i>
        <span><?= htmlspecialchars($kategori_aktif) ?></span>
      <?php endif; ?>
    </div>

    <!-- Toolbar -->
    <div class="produk-toolbar">
      <div class="produk-toolbar-left">
        <h2>
          <?= $kategori_aktif === 'Semua' ? 'Semua Produk' : htmlspecialchars($kategori_aktif) ?>
        </h2>
        <p>
          <?= count($produk_tampil) ?> produk ditemukan
          <?= $search ? ' untuk "<strong>' . htmlspecialchars($search) . '</strong>"' : '' ?>
        </p>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <button class="btn-filter-toggle" onclick="toggleFilter()">
          <i class="fa fa-filter"></i> Filter
        </button>
        <form class="toolbar-search" action="kategori.php" method="GET">
          <?php if ($kategori_aktif !== 'Semua'): ?>
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_aktif) ?>">
          <?php endif; ?>
          <i class="fa fa-search"></i>
          <input type="text" name="q" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
        </form>
      </div>
    </div>

    <!-- Grid Produk -->
    <?php if (empty($produk_tampil)): ?>
      <div class="empty-state">
        <i class="fa fa-box-open"></i>
        <h3>Produk tidak ditemukan</h3>
        <p>Coba kata kunci lain atau pilih kategori berbeda</p>
        <a href="kategori.php" class="btn btn-outline" style="margin-top:16px; display:inline-flex;">
          Lihat Semua Produk
        </a>
      </div>
    <?php else: ?>
      <div class="produk-grid">
        <?php foreach ($produk_tampil as $p): ?>
        <a href="produk.php?id=<?= $p['id'] ?>" class="produk-card">
          <div class="produk-card-img">
            <?php if (!empty($p['gambar'])): ?>
              <img src="uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
            <?php else: ?>
              <span class="img-placeholder"><i class="fa fa-image"></i></span>
            <?php endif; ?>
          </div>
          <div class="produk-card-body">
            <div class="produk-card-kategori"><?= htmlspecialchars($p['kategori']) ?></div>
            <div class="produk-card-nama"><?= htmlspecialchars($p['nama']) ?></div>
            <div class="produk-card-harga">
              Rp <?= number_format($p['harga'], 0, ',', '.') ?>
              <small style="font-size:11px; font-weight:400; color:var(--text-muted);">/ <?= $p['satuan'] ?></small>
            </div>
            <button class="btn-cart" onclick="addToCart(event, <?= $p['id'] ?>)">
              <i class="fa fa-cart-plus"></i> Add to Cart
            </button>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
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
function toggleFilter() {
  document.getElementById('filterSidebar').classList.toggle('open');
}
function addToCart(e, id) {
  e.preventDefault();
  e.stopPropagation();
  fetch('ajax/add-to-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id_produk: id, jumlah: 1 })
  })
  .then(r => r.json())
  .then(data => { if (data.success) showToast('Produk ditambahkan ke keranjang!'); })
  .catch(() => { window.location.href = 'login.php'; });
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
