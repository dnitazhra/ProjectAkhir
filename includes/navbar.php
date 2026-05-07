<?php
// Pastikan functions.php sudah di-load
if (!function_exists('getCartCount')) {
    $func_path = str_replace('\\', '/', __DIR__) . '/functions.php';
    if (file_exists($func_path)) include_once $func_path;
}

// Tentukan halaman aktif
$current = basename($_SERVER['PHP_SELF'], '.php');
$cart_count_nav = 0;
if (isset($user) && $user) {
    $cart_count_nav = getCartCount($conn, $user['id_user']);
}
$profil_url = !isset($user) || !$user
    ? 'login.php'
    : ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'profil.php');
?>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar Mobile -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <span class="sidebar-logo">lavo.id</span>
    <button class="sidebar-close" onclick="closeSidebar()"><i class="fa fa-times"></i></button>
  </div>
  <div class="sidebar-nav">
    <a href="index.php" <?= $current==='index' ? 'class="active"' : '' ?>>
      <i class="fa fa-home"></i> Home
    </a>
    <a href="kategori.php" <?= in_array($current,['kategori','produk']) ? 'class="active"' : '' ?>>
      <i class="fa fa-store"></i> Kategori Produk
    </a>
    <a href="#kontak" onclick="closeSidebar(); scrollToKontak(event)">
      <i class="fa fa-envelope"></i> Kontak
    </a>
    <div style="height:1px;background:rgba(255,255,255,0.15);margin:8px 0;"></div>
    <?php if (isset($user) && $user): ?>
      <a href="lacak-pesanan.php"><i class="fa fa-box"></i> Pesanan Saya</a>
      <a href="<?= $profil_url ?>"><i class="fa fa-user"></i> Profil</a>
      <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
    <?php else: ?>
      <a href="login.php"><i class="fa fa-sign-in-alt"></i> Masuk</a>
      <a href="register.php"><i class="fa fa-user-plus"></i> Daftar</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Navbar -->
<header class="navbar">
  <!-- Logo -->
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="lavo.id" onerror="this.style.display='none'">
    <h2>lavo.id</h2>
  </div>

  <!-- Nav Links (tengah) -->
  <nav class="navbar-nav">
    <a href="index.php" <?= $current==='index' ? 'class="active"' : '' ?>>
      <i class="fa fa-home"></i> Home
    </a>
    <a href="kategori.php" <?= in_array($current,['kategori','produk']) ? 'class="active"' : '' ?>>
      <i class="fa fa-store"></i> Kategori Produk
    </a>
    <a href="#kontak" onclick="scrollToKontak(event)">
      <i class="fa fa-envelope"></i> Kontak
    </a>
  </nav>

  <!-- Actions (kanan) -->
  <div class="navbar-actions">
    <!-- Profil / Avatar -->
    <?php if (isset($user) && $user): ?>
      <a href="<?= $profil_url ?>" class="nav-btn" title="Profil: <?= htmlspecialchars($user['nama']) ?>"
         style="position:relative;padding:0;">
        <div style="
          width: 36px;
          height: 36px;
          border-radius: 50%;
          background: var(--primary);
          color: white;
          font-size: 14px;
          font-weight: 700;
          display: flex;
          align-items: center;
          justify-content: center;
          border: 2px solid rgba(171,53,0,0.3);
          text-transform: uppercase;
          font-family: var(--font-main);
        "><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
      </a>
    <?php else: ?>
      <a href="login.php" class="nav-btn" title="Masuk">
        <i class="fa fa-user"></i>
      </a>
    <?php endif; ?>

    <!-- Keranjang -->
    <a href="keranjang.php" class="nav-btn" title="Keranjang">
      <i class="fa fa-shopping-cart"></i>
      <?php if ($cart_count_nav > 0): ?>
        <span class="badge"><?= $cart_count_nav ?></span>
      <?php endif; ?>
    </a>

    <!-- Hamburger (mobile) -->
    <button class="nav-btn btn-menu" onclick="openSidebar()" title="Menu">
      <i class="fa fa-bars"></i>
    </button>
  </div>
</header>

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
function scrollToKontak(e) {
  e.preventDefault();
  const el = document.querySelector('.footer') || document.querySelector('#kontak');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  } else {
    window.location.href = 'index.php#kontak';
  }
}

// Global addToCart — cek login dulu
function addToCart(e, id) {
  if (e && e.preventDefault) { e.preventDefault(); e.stopPropagation(); }
  const isLoggedIn = <?= (isset($user) && $user) ? 'true' : 'false' ?>;
  if (!isLoggedIn) {
    // Redirect ke login dengan path relatif dari base URL
    const base = window.location.pathname.replace(/\/[^\/]*$/, '/');
    const isAdmin = base.includes('/admin/');
    window.location.href = isAdmin ? '../login.php' : 'login.php';
    return;
  }
  const base = window.location.pathname.replace(/\/[^\/]*$/, '/');
  const isAdmin = base.includes('/admin/');
  const ajaxUrl = isAdmin ? '../ajax/add-to-cart.php' : 'ajax/add-to-cart.php';
  fetch(ajaxUrl, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id_produk: id, jumlah: 1 })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('✅ Produk ditambahkan ke keranjang!');
      const badge = document.querySelector('.navbar-actions .badge');
      if (badge) badge.textContent = data.cart_count;
    } else if (data.redirect) {
      window.location.href = isAdmin ? '../login.php' : 'login.php';
    } else {
      showToast('⚠️ ' + (data.message || 'Gagal'), 'error');
    }
  })
  .catch(() => {
    window.location.href = isAdmin ? '../login.php' : 'login.php';
  });
}

function showLoginPrompt() {
  let overlay = document.getElementById('loginPromptOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'loginPromptOverlay';
    overlay.className = 'login-prompt-overlay';
    overlay.innerHTML = `
      <div class="login-prompt-card">
        <div class="login-prompt-icon">
          <i class="fa fa-shopping-cart"></i>
        </div>
        <h3>Masuk Dulu, Yuk!</h3>
        <p>Kamu perlu login untuk menambahkan produk ke keranjang belanja.</p>
        <div class="login-prompt-actions">
          <a href="login.php" class="btn-login-now">
            <i class="fa fa-sign-in-alt"></i> Masuk Sekarang
          </a>
          <button class="btn-cancel-prompt" onclick="closeLoginPrompt()">
            Nanti Saja
          </button>
        </div>
      </div>
    `;
    overlay.addEventListener('click', function(e) {
      if (e.target === this) closeLoginPrompt();
    });
    document.body.appendChild(overlay);
  }
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeLoginPrompt() {
  const overlay = document.getElementById('loginPromptOverlay');
  if (overlay) overlay.classList.remove('active');
  document.body.style.overflow = '';
}

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.textContent = msg;
  const bg = type === 'error' ? '#dc2626' : '#1a1a1a';
  t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
    background:${bg};color:white;padding:12px 24px;border-radius:9999px;
    font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.3)`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2500);
}
</script>
