<?php session_start(); $user = $_SESSION['user'] ?? null; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ulasan Berhasil - lavo.id</title>
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
      padding: 48px 36px;
      max-width: 440px;
      width: 100%;
      box-shadow: var(--shadow-lg);
      text-align: center;
    }
    .sukses-icon {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes popIn {
      0%   { transform: scale(0); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }
    .sukses-icon i { font-size: 40px; color: white; }
    .sukses-card h2 {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 10px;
      font-family: 'Times New Roman', serif;
      line-height: 1.3;
    }
    .sukses-card p {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.7;
      margin-bottom: 32px;
    }
    .sukses-actions { display: flex; flex-direction: column; gap: 10px; }
    .btn-lihat {
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
    .btn-lihat:hover { background: var(--primary-dark); }
    .btn-belanja {
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
    .btn-belanja:hover { background: var(--primary); color: white; }
    .btn-bagikan {
      background: transparent;
      color: var(--text-muted);
      border: 1.5px solid var(--border);
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
    .btn-bagikan:hover { border-color: var(--primary); color: var(--primary); }
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
    <div class="sukses-icon"><i class="fa fa-star"></i></div>
    <h2>Terima Kasih atas<br>Ulasan Anda!</h2>
    <p>Ulasan Anda sangat berarti bagi kami dan membantu pelanggan lain dalam memilih produk terbaik.</p>
    <div class="sukses-actions">
      <a href="ulasan-saya.php" class="btn-lihat">
        <i class="fa fa-list"></i> Lihat Ulasan Saya
      </a>
      <a href="kategori.php" class="btn-belanja">
        <i class="fa fa-shopping-bag"></i> Lanjut Belanja
      </a>
      <button class="btn-bagikan" onclick="bagikan()">
        <i class="fa fa-share-alt"></i> Bagikan ke Teman
      </button>
    </div>
  </div>
</div>

<script>
function bagikan() {
  if (navigator.share) {
    navigator.share({ title: 'lavo.id', text: 'Coba snack enak di lavo.id!', url: window.location.origin });
  } else {
    navigator.clipboard.writeText(window.location.origin);
    alert('Link disalin ke clipboard!');
  }
}
</script>
</body>
</html>
