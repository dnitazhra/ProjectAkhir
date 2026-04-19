<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
$id   = (int)($_GET['id'] ?? 0);

// Dari DB
$produk = getProdukById($conn, $id);
if (!$produk) { header("Location: kategori.php"); exit; }

$produk['id']     = $produk['id_produk'];
$varian           = getVarianProduk($conn, $id);
$produk['varian'] = $varian;

// Rating
$rating_data      = getRatingRataRata($conn, $id);
$produk['rating'] = $rating_data['avg_rating'] ?? 0;

// Ulasan dari DB
$reviews_db = getUlasanProduk($conn, $id);
$reviews    = array_map(fn($r) => [
    'nama'     => $r['nama'],
    'rating'   => $r['rating'],
    'komentar' => $r['komentar'],
    'foto'     => $r['foto'] ?? null,
    'tanggal'  => date('d M Y', strtotime($r['created_at'])),
], $reviews_db);

// Produk lain
$produk_lain = array_filter(getProdukAll($conn, 8), fn($p) => $p['id'] != $id);
$produk_lain = array_slice($produk_lain, 0, 4);

$cart_count = $user ? getCartCount($conn, $user['id_user']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($produk['nama']) ?> - Happy Snack</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .produk-detail-page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    /* ---- Breadcrumb ---- */
    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 28px;
    }
    .breadcrumb a { color: var(--primary); font-weight: 500; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }

    /* ---- Detail Layout ---- */
    .produk-detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      margin-bottom: 48px;
    }

    /* ---- Gambar ---- */
    .produk-img-wrap {
      background: var(--bg-card);
      border-radius: var(--radius-md);
      overflow: hidden;
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .produk-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .produk-img-placeholder {
      font-size: 80px;
      color: var(--border);
    }

    /* ---- Info ---- */
    .produk-info { display: flex; flex-direction: column; gap: 16px; }

    .produk-kategori-tag {
      display: inline-block;
      background: #fff5f0;
      color: var(--primary);
      font-size: 12px;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: var(--radius-full);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .produk-nama {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-dark);
      font-family: 'Times New Roman', serif;
      line-height: 1.2;
    }

    .produk-rating-row {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .stars { color: #f59e0b; font-size: 16px; letter-spacing: 1px; }

    .rating-val {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .rating-count {
      font-size: 13px;
      color: var(--text-muted);
    }

    .produk-harga-row {
      display: flex;
      align-items: baseline;
      gap: 8px;
    }

    .produk-harga {
      font-size: 30px;
      font-weight: 700;
      color: var(--text-red);
    }

    .produk-satuan {
      font-size: 14px;
      color: var(--text-muted);
    }

    .produk-stok {
      font-size: 13px;
      color: var(--success);
      font-weight: 600;
    }

    .produk-stok i { margin-right: 4px; }

    /* ---- Varian ---- */
    .varian-label {
      font-size: 13px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .varian-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .varian-btn {
      padding: 7px 16px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-full);
      font-size: 13px;
      font-weight: 500;
      color: var(--text-dark);
      background: white;
      cursor: pointer;
      transition: all var(--transition);
    }

    .varian-btn:hover,
    .varian-btn.active {
      border-color: var(--primary);
      background: #fff5f0;
      color: var(--primary);
      font-weight: 700;
    }

    /* ---- Qty & Cart ---- */
    .qty-cart-row {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .qty-control {
      display: flex;
      align-items: center;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-full);
      overflow: hidden;
    }

    .qty-btn {
      width: 38px;
      height: 38px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: var(--text-dark);
      background: var(--bg-light);
      transition: background var(--transition);
    }

    .qty-btn:hover { background: var(--border); }

    .qty-val {
      width: 44px;
      text-align: center;
      font-size: 15px;
      font-weight: 700;
      border: none;
      outline: none;
      background: white;
    }

    .btn-add-cart {
      flex: 1;
      background: var(--primary);
      color: white;
      border-radius: var(--radius-full);
      padding: 12px 24px;
      font-size: 15px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background var(--transition);
      min-width: 160px;
    }

    .btn-add-cart:hover { background: var(--primary-dark); }

    /* ---- Deskripsi ---- */
    .produk-desc-box {
      background: var(--bg-light);
      border-radius: var(--radius-md);
      padding: 20px;
      border: 1px solid var(--border);
    }

    .produk-desc-box h4 {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--text-dark);
    }

    .produk-desc-box p {
      font-size: 13px;
      color: var(--text-muted);
      white-space: pre-line;
      line-height: 1.8;
    }

    /* ---- Reviews ---- */
    .reviews-section { margin-top: 48px; }

    .reviews-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--border);
    }

    .reviews-header h3 {
      font-size: 18px;
      font-weight: 700;
      font-family: 'Times New Roman', serif;
    }

    .reviews-header a {
      font-size: 13px;
      color: var(--primary);
      font-weight: 600;
    }

    .review-card {
      padding: 16px 0;
      border-bottom: 1px solid var(--border);
    }

    .review-card:last-child { border-bottom: none; }

    .review-top {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 8px;
    }

    .review-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .review-meta { flex: 1; }

    .review-nama {
      font-size: 14px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .review-stars { color: #f59e0b; font-size: 13px; }

    .review-tanggal {
      font-size: 12px;
      color: var(--text-muted);
    }

    .review-komentar {
      font-size: 14px;
      color: var(--text-dark);
      line-height: 1.6;
      padding-left: 50px;
    }

    /* ---- Produk Lain ---- */
    .produk-lain { margin-top: 48px; }

    @media (max-width: 768px) {
      .produk-detail-grid { grid-template-columns: 1fr; gap: 24px; }
      .produk-nama { font-size: 22px; }
      .produk-harga { font-size: 24px; }
      .review-komentar { padding-left: 0; margin-top: 8px; }
      .produk-detail-page { padding: 16px; }
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
<div class="produk-detail-page">

  <!-- Back + Breadcrumb -->
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <button class="btn-back" onclick="history.back()">
      <i class="fa fa-arrow-left"></i> Kembali
    </button>
    <div class="breadcrumb" style="margin-bottom:0;">
      <a href="index.php">Beranda</a>
      <i class="fa fa-chevron-right"></i>
      <a href="kategori.php?kategori=<?= urlencode($produk['kategori']) ?>"><?= htmlspecialchars($produk['kategori']) ?></a>
      <i class="fa fa-chevron-right"></i>
      <span><?= htmlspecialchars($produk['nama']) ?></span>
    </div>
  </div>

  <!-- Detail Grid -->
  <div class="produk-detail-grid">

    <!-- Gambar -->
    <div class="produk-img-wrap">
      <?php if (!empty($produk['gambar'])): ?>
        <img src="uploads/<?= htmlspecialchars($produk['gambar']) ?>" alt="<?= htmlspecialchars($produk['nama']) ?>">
      <?php else: ?>
        <span class="produk-img-placeholder"><i class="fa fa-image"></i></span>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="produk-info">
      <span class="produk-kategori-tag"><?= htmlspecialchars($produk['kategori']) ?></span>

      <h1 class="produk-nama"><?= htmlspecialchars($produk['nama']) ?></h1>

      <div class="produk-rating-row">
        <div class="stars">
          <?php
          $r = round($produk['rating']);
          for ($i = 1; $i <= 5; $i++) echo $i <= $r ? '★' : '☆';
          ?>
        </div>
        <span class="rating-val"><?= $produk['rating'] ?></span>
        <span class="rating-count">(<?= count($reviews) ?> ulasan)</span>
      </div>

      <div class="produk-harga-row">
        <span class="produk-harga">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></span>
        <span class="produk-satuan">/ <?= $produk['satuan'] ?></span>
      </div>

      <div class="produk-stok">
        <i class="fa fa-check-circle"></i> Stok Tersedia: <?= $produk['stok'] ?> <?= $produk['satuan'] ?>
      </div>

      <!-- Varian -->
      <?php if (!empty($produk['varian'])): ?>
      <div>
        <div class="varian-label">Pilih Varian:</div>
        <div class="varian-list">
          <?php foreach ($produk['varian'] as $i => $v): ?>
            <button class="varian-btn <?= $i === 0 ? 'active' : '' ?>"
                    onclick="pilihVarian(this, '<?= htmlspecialchars($v) ?>')">
              <?= htmlspecialchars($v) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Qty & Cart -->
      <div class="qty-cart-row">
        <div class="qty-control">
          <button class="qty-btn" onclick="ubahQty(-1)"><i class="fa fa-minus"></i></button>
          <input type="number" class="qty-val" id="qty" value="1" min="1" max="<?= $produk['stok'] ?>">
          <button class="qty-btn" onclick="ubahQty(1)"><i class="fa fa-plus"></i></button>
        </div>
        <button class="btn-add-cart" onclick="addToCart(<?= $produk['id'] ?>)">
          <i class="fa fa-cart-plus"></i> Add to Cart
        </button>
      </div>

      <!-- Deskripsi -->
      <div class="produk-desc-box">
        <h4><i class="fa fa-info-circle" style="color:var(--primary);margin-right:6px;"></i>Deskripsi Produk</h4>
        <p><?= htmlspecialchars($produk['deskripsi']) ?></p>
      </div>

    </div>
  </div>

  <!-- Reviews -->
  <div class="reviews-section">
    <div class="reviews-header">
      <h3>Customer Reviews</h3>
      <a href="#">Lihat Semua</a>
    </div>
    <?php foreach ($reviews as $rev): ?>
    <div class="review-card">
      <div class="review-top">
        <div class="review-avatar"><?= strtoupper(substr($rev['nama'], 0, 1)) ?></div>
        <div class="review-meta">
          <div class="review-nama"><?= htmlspecialchars($rev['nama']) ?></div>
          <div class="review-stars">
            <?php for ($i = 1; $i <= 5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
          </div>
        </div>
        <div class="review-tanggal"><?= $rev['tanggal'] ?></div>
      </div>
      <div class="review-komentar"><?= htmlspecialchars($rev['komentar']) ?></div>
      <?php if (!empty($rev['foto'])): ?>
      <div style="margin-top:10px;padding-left:50px;">
        <img src="uploads/<?= htmlspecialchars($rev['foto']) ?>"
             alt="Foto ulasan"
             style="max-width:160px;max-height:160px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);cursor:pointer;"
             onclick="this.style.maxWidth=this.style.maxWidth==='100%'?'160px':'100%'">
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Produk Lain -->
  <div class="produk-lain">
    <div class="section-header">
      <h2 class="section-title">Produk <span>Lainnya</span></h2>
      <a href="kategori.php" class="section-link">Lihat Semua →</a>
    </div>
    <div class="produk-grid">
      <?php
      $lain = array_filter($produk_lain, fn($p) => $p['id'] != $produk['id']);
      $lain = array_slice($lain, 0, 4);
      foreach ($lain as $p):
      ?>
      <a href="produk.php?id=<?= $p['id'] ?>" class="produk-card">
        <div class="produk-card-img">
          <span class="img-placeholder"><i class="fa fa-image"></i></span>
        </div>
        <div class="produk-card-body">
          <div class="produk-card-kategori"><?= htmlspecialchars($p['kategori']) ?></div>
          <div class="produk-card-nama"><?= htmlspecialchars($p['nama']) ?></div>
          <div class="produk-card-harga">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
          <button class="btn-cart" onclick="addToCart(event, <?= $p['id'] ?>)">
            <i class="fa fa-cart-plus"></i> Add to Cart
          </button>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Footer -->
<footer class="footer">
  <div class="footer-content">
    <div class="footer-brand">
      <h3>Happy Snack</h3>
      <p>Snack homemade berkualitas, tanpa pengawet, freshly baked.</p>
    </div>
    <div class="footer-col">
      <h4>Menu</h4>
      <ul>
        <li><a href="index.php">Beranda</a></li>
        <li><a href="kategori.php">Semua Produk</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Kontak</h4>
      <p><i class="fa fa-phone"></i> 0812-3456-7890</p>
      <p><i class="fa fa-envelope"></i> happysnack@gmail.com</p>
    </div>
  </div>
  <div class="footer-bottom"><p>© 2026 Happy Snack. All rights reserved.</p></div>
</footer>

<script>
let varianDipilih = '<?= htmlspecialchars($produk['varian'][0] ?? '') ?>';

function pilihVarian(btn, nama) {
  document.querySelectorAll('.varian-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  varianDipilih = nama;
}

function ubahQty(delta) {
  const input = document.getElementById('qty');
  const max   = parseInt(input.max) || 99;
  let val = parseInt(input.value) + delta;
  if (val < 1) val = 1;
  if (val > max) val = max;
  input.value = val;
}

function addToCart(id) {
  const qty = parseInt(document.getElementById('qty').value);
  fetch('ajax/add-to-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id_produk: id, jumlah: qty, varian: varianDipilih })
  })
  .then(r => r.json())
  .then(data => { if (data.success) showToast('Produk ditambahkan ke keranjang!'); })
  .catch(() => { window.location.href = 'login.php'; });
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
