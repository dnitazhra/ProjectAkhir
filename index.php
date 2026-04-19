<?php
session_start();
include 'koneksi.php';

$user = $_SESSION['user'] ?? null;

// Ambil produk dari DB
$produk_list = [];
$res = mysqli_query($conn, "
    SELECT p.id_produk AS id, p.nama, p.harga, p.satuan, p.gambar, k.nama AS kategori
    FROM produk p
    JOIN kategori k ON p.id_kategori = k.id_kategori
    ORDER BY k.id_kategori, p.nama
    LIMIT 8
");
while ($row = mysqli_fetch_assoc($res)) {
    $produk_list[] = $row;
}

// Hitung cart
$cart_count = 0;
if ($user) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(jumlah),0) FROM keranjang WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user['id_user']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $cart_count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $cart_count = (int)$cart_count;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Happy Snack - Toko Snack Online</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- ===== SIDEBAR OVERLAY ===== -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <span class="sidebar-logo">Happy Snack</span>
    <button class="sidebar-close" onclick="closeSidebar()">
      <i class="fa fa-times"></i>
    </button>
  </div>
  <div class="sidebar-nav">
    <a href="index.php" class="active"><i class="fa fa-home"></i> Beranda</a>
    <a href="kategori.php"><i class="fa fa-th-large"></i> Kategori</a>
    <a href="keranjang.php"><i class="fa fa-shopping-cart"></i> Keranjang</a>
    <?php if ($user): ?>
      <a href="profil.php"><i class="fa fa-user"></i> Profil</a>
      <a href="lacak-pesanan.php"><i class="fa fa-box"></i> Pesanan Saya</a>
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
    <img src="logo.png/logo.png" alt="Happy Snack Logo" onerror="this.style.display='none'">
    <h2>Happy Snack</h2>
  </div>

  <div class="navbar-search">
    <button type="button"><i class="fa fa-search"></i></button>
    <input type="text" placeholder="Cari snack favoritmu...">
  </div>

  <div class="navbar-actions">
    <?php if ($user): ?>
      <a href="profil.php" class="nav-btn" title="Profil">
        <i class="fa fa-user"></i>
      </a>
    <?php else: ?>
      <a href="login.php" class="nav-btn" title="Masuk">
        <i class="fa fa-user"></i>
      </a>
    <?php endif; ?>

    <a href="keranjang.php" class="nav-btn" title="Keranjang">
      <i class="fa fa-shopping-cart"></i>
      <?php if ($cart_count > 0): ?>
        <span class="badge"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>

    <button class="nav-btn btn-menu" onclick="openSidebar()" title="Menu">
      <i class="fa fa-bars"></i>
    </button>
  </div>
</header>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-content">
    <span class="hero-badge">🍪 Snack Homemade Terbaik</span>
    <h1>Toko Spesial<br>Snack Happy Snack</h1>
    <p>Temukan berbagai snack enak, kue kering, dan camilan berkualitas</p>
    <form class="hero-search" action="kategori.php" method="GET">
      <input type="text" name="q" placeholder="Cari snack favoritmu...">
      <button type="submit"><i class="fa fa-search"></i> Cari</button>
    </form>
  </div>
</section>

<!-- ===== KATEGORI ===== -->
<div style="max-width:1200px; margin:0 auto; padding:48px 24px 0;">
  <div class="section-header">
    <h2 class="section-title">Kategori <span>Produk</span></h2>
    <a href="kategori.php" class="section-link">Lihat Semua →</a>
  </div>
  <div class="kategori-grid">
    <a href="kategori.php?kategori=Snack+Kering" class="kategori-card">
      <img src="logo.png/snackkering.jpeg" alt="Snack Kering" onerror="this.style.display='none'">
      <div class="kategori-card-label">🥨 Snack Kering</div>
    </a>
    <a href="kategori.php?kategori=Kue+Kering" class="kategori-card">
      <img src="logo.png/kuekering.jpeg" alt="Kue Kering" onerror="this.style.display='none'">
      <div class="kategori-card-label">🍪 Kue Kering</div>
    </a>
  </div>
</div>

<!-- ===== SEMUA PRODUK ===== -->
<div class="section">
  <div class="section-header">
    <h2 class="section-title">Semua <span>Produk</span></h2>
    <a href="kategori.php" class="section-link">Lihat Semua →</a>
  </div>
  <div class="produk-grid">
    <?php foreach ($produk_list as $p): ?>
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
        <div class="produk-card-harga">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
        <button class="btn-cart" onclick="addToCart(event, <?= $p['id'] ?>)">
          <i class="fa fa-cart-plus"></i> Add to Cart
        </button>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ===== INFO STRIP ===== -->
<div class="info-strip">
  <div class="info-strip-inner">
    <div class="info-item">
      <i class="fa fa-truck"></i>
      <h4>Pengiriman Cepat</h4>
      <p>Dikirim ke seluruh Indonesia</p>
    </div>
    <div class="info-item">
      <i class="fa fa-star"></i>
      <h4>Rating 4.9 ⭐</h4>
      <p>Dipercaya 1200+ pelanggan</p>
    </div>
    <div class="info-item">
      <i class="fa fa-shield-alt"></i>
      <h4>Pembayaran Aman</h4>
      <p>GoPay, Transfer Bank, Kartu Kredit</p>
    </div>
  </div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="footer">
  <div class="footer-content">
    <div class="footer-brand">
      <h3>Happy Snack</h3>
      <p>Menyediakan berbagai snack enak dan kue kering berkualitas. Homemade, tanpa pengawet, freshly baked.</p>
    </div>
    <div class="footer-col">
      <h4>Menu</h4>
      <ul>
        <li><a href="index.php">Beranda</a></li>
        <li><a href="kategori.php">Produk</a></li>
        <li><a href="kategori.php?kategori=Snack+Kering">Snack Kering</a></li>
        <li><a href="kategori.php?kategori=Kue+Kering">Kue Kering</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Kontak</h4>
      <p><i class="fa fa-phone"></i> 0812-3456-7890</p>
      <p><i class="fa fa-envelope"></i> happysnack@gmail.com</p>
      <p><i class="fa fa-map-marker-alt"></i> Bandung, Jawa Barat</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2026 Happy Snack. All rights reserved.</p>
  </div>
</footer>

<!-- ===== SCRIPT ===== -->
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

function addToCart(e, id) {
  e.preventDefault();
  e.stopPropagation();
  // Nanti dihubungkan ke backend
  fetch('ajax/add-to-cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id_produk: id, jumlah: 1 })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('Produk ditambahkan ke keranjang!');
    }
  })
  .catch(() => {
    // Jika belum ada backend, redirect ke login
    window.location.href = 'login.php';
  });
}

function showToast(msg) {
  const toast = document.createElement('div');
  toast.textContent = msg;
  toast.style.cssText = `
    position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
    background:#1a1a1a; color:white; padding:12px 24px;
    border-radius:9999px; font-size:14px; font-weight:600;
    z-index:9999; box-shadow:0 4px 12px rgba(0,0,0,0.3);
    animation: fadeInUp 0.3s ease;
  `;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 2500);
}
</script>

</body>
</html>
