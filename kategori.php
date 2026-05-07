<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;

// Filter & search
$kategori_aktif = $_GET['kategori'] ?? 'Semua';
$search         = trim($_GET['q'] ?? '');

// Dari DB — deduplicate kategori
$kategori_db   = getKategoriAll($conn);
$nama_kategori = array_unique(array_column($kategori_db, 'nama'));
$kategori_list = array_merge(['Semua'], array_values($nama_kategori));

// Ambil SEMUA produk untuk hitung count per kategori
$semua_produk_db = getProdukAll($conn);
$counts = ['Semua' => count($semua_produk_db)];
foreach ($semua_produk_db as $p) {
    $kat = $p['kategori'];
    $counts[$kat] = ($counts[$kat] ?? 0) + 1;
}

$produk_tampil = getProdukFilter($conn, $kategori_aktif, $search);
$cart_count    = $user ? getCartCount($conn, $user['id_user']) : 0;

// Ambil top 3 id produk best seller (berdasarkan total terjual dari pesanan selesai)
$res_bs = mysqli_query($conn,
    "SELECT dp.id_produk
     FROM detail_pesanan dp
     JOIN pesanan ps ON dp.id_pesanan = ps.id_pesanan
     WHERE ps.status = 'selesai'
     GROUP BY dp.id_produk
     ORDER BY SUM(dp.jumlah) DESC
     LIMIT 3");
$best_seller_ids = [];
while ($row = mysqli_fetch_assoc($res_bs)) {
    $best_seller_ids[] = $row['id_produk'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Produk - lavo.id</title>
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

    /* ---- Label Best Seller ---- */
    .produk-card { position: relative; }
    .badge-best-seller {
      position: absolute;
      top: 10px;
      left: 10px;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      font-size: 10px;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 9999px;
      display: flex;
      align-items: center;
      gap: 4px;
      z-index: 2;
      box-shadow: 0 2px 6px rgba(217,119,6,0.4);
      letter-spacing: 0.3px;
      text-transform: uppercase;
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

<?php include 'includes/navbar.php'; ?>

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
        <?php foreach ($produk_tampil as $p):
          $is_best_seller = in_array($p['id'], $best_seller_ids);
        ?>
        <a href="produk.php?id=<?= $p['id'] ?>" class="produk-card">
          <?php if ($is_best_seller): ?>
          <div class="badge-best-seller">
            <i class="fa fa-fire"></i> Best Seller
          </div>
          <?php endif; ?>
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
