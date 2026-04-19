<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header("Location: login.php"); exit; }

$cart_count   = getCartCount($conn, $user['id_user']);
$pesanan_list = getPesananUser($conn, $user['id_user']);

// Ambil pesanan selesai beserta detail produknya untuk tab ulasan
$pesanan_selesai = [];
foreach ($pesanan_list as $p) {
    if ($p['status'] === 'selesai') {
        $detail = getDetailPesanan($conn, $p['id_pesanan']);
        $pesanan_selesai[] = array_merge($p, ['detail' => $detail]);
    }
}

$status_label = [
    'pending'  => ['label'=>'Pending',   'color'=>'#f59e0b'],
    'diproses' => ['label'=>'Diproses',  'color'=>'#3b82f6'],
    'dikemas'  => ['label'=>'Dikemas',   'color'=>'#8b5cf6'],
    'dikirim'  => ['label'=>'Dikirim',   'color'=>'#06b6d4'],
    'selesai'  => ['label'=>'Selesai',   'color'=>'#22c55e'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil - Happy Snack</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .profil-page {
      max-width: 1100px;
      margin: 0 auto;
      padding: 32px 24px;
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 24px;
      align-items: flex-start;
    }

    /* ---- Sidebar Profil ---- */
    .profil-sidebar {
      position: sticky;
      top: 80px;
    }

    .profil-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 16px;
    }

    .profil-card-top {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      padding: 24px 20px;
      text-align: center;
    }

    .profil-avatar {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: white;
      color: var(--primary);
      font-size: 28px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
      border: 3px solid rgba(255,255,255,0.5);
    }

    .profil-nama {
      font-size: 16px;
      font-weight: 700;
      color: white;
      margin-bottom: 4px;
    }

    .profil-email {
      font-size: 12px;
      color: rgba(255,255,255,0.8);
    }

    .profil-edit-btn {
      display: inline-block;
      margin-top: 12px;
      background: rgba(255,255,255,0.2);
      color: white;
      padding: 6px 16px;
      border-radius: var(--radius-full);
      font-size: 12px;
      font-weight: 600;
      transition: background var(--transition);
    }

    .profil-edit-btn:hover { background: rgba(255,255,255,0.35); }

    .profil-menu { padding: 8px 0; }

    .profil-menu-section {
      padding: 8px 16px 4px;
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .profil-menu-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 11px 16px;
      font-size: 14px;
      font-weight: 500;
      color: var(--text-dark);
      cursor: pointer;
      transition: background var(--transition), color var(--transition);
      border-left: 3px solid transparent;
      text-decoration: none;
    }

    .profil-menu-item:hover { background: var(--bg-light); color: var(--primary); }

    .profil-menu-item.active {
      background: #fff5f0;
      color: var(--primary);
      border-left-color: var(--primary);
      font-weight: 700;
    }

    .profil-menu-item .left { display: flex; align-items: center; gap: 10px; }
    .profil-menu-item i { width: 18px; text-align: center; font-size: 14px; }

    .menu-badge {
      background: var(--primary);
      color: white;
      font-size: 11px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: var(--radius-full);
    }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      font-size: 14px;
      font-weight: 600;
      color: #ef4444;
      width: 100%;
      transition: background var(--transition);
      border-top: 1px solid var(--border);
    }

    .btn-logout:hover { background: #fef2f2; }

    /* ---- Main Content ---- */
    .profil-main { display: flex; flex-direction: column; gap: 20px; }

    .main-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
    }

    .main-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      background: var(--bg-light);
    }

    .main-card-header h3 {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-dark);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .main-card-header h3 i { color: var(--primary); }
    .main-card-header a { font-size: 13px; color: var(--primary); font-weight: 600; }
    .main-card-body { padding: 20px; }

    /* Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 20px;
    }

    .stat-box {
      background: var(--bg-light);
      border-radius: var(--radius-md);
      padding: 16px;
      text-align: center;
    }

    .stat-box i { font-size: 22px; color: var(--primary); margin-bottom: 8px; }
    .stat-box .num { font-size: 22px; font-weight: 700; color: var(--text-dark); }
    .stat-box .lbl { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    /* Pesanan table */
    .pesanan-table { width: 100%; border-collapse: collapse; }

    .pesanan-table th {
      text-align: left;
      padding: 10px 12px;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--border);
      background: var(--bg-light);
    }

    .pesanan-table td {
      padding: 12px;
      font-size: 14px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    .pesanan-table tr:last-child td { border-bottom: none; }
    .pesanan-table tr:hover td { background: var(--bg-light); }

    .status-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: var(--radius-full);
      font-size: 11px;
      font-weight: 700;
      color: white;
    }

    .btn-detail {
      font-size: 12px;
      color: var(--primary);
      font-weight: 600;
      padding: 4px 10px;
      border: 1px solid var(--primary);
      border-radius: var(--radius-full);
      transition: all var(--transition);
    }

    .btn-detail:hover { background: var(--primary); color: white; }

    /* Edit profil form */
    .edit-form .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    @media (max-width: 768px) {
      .profil-page { grid-template-columns: 1fr; }
      .profil-sidebar { position: static; }
      .stats-grid { grid-template-columns: repeat(3, 1fr); }
      .edit-form .form-grid-2 { grid-template-columns: 1fr; }
    }

    @media (max-width: 480px) {
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .pesanan-table { font-size: 12px; }
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
    <a href="profil.php" class="active"><i class="fa fa-user"></i> Profil</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
  </div>
</nav>

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
    <a href="profil.php" class="nav-btn"><i class="fa fa-user"></i></a>
    <a href="keranjang.php" class="nav-btn"><i class="fa fa-shopping-cart"></i></a>
    <button class="nav-btn btn-menu" onclick="openSidebar()"><i class="fa fa-bars"></i></button>
  </div>
</header>

<div style="max-width:1100px;margin:16px auto 0;padding:0 24px;">
  <a href="index.php" class="btn-back">
    <i class="fa fa-arrow-left"></i> Beranda
  </a>
</div>
<div class="profil-page">

  <!-- Sidebar -->
  <aside class="profil-sidebar">
    <div class="profil-card">
      <div class="profil-card-top">
        <div class="profil-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
        <div class="profil-nama"><?= htmlspecialchars($user['nama']) ?></div>
        <div class="profil-email"><?= htmlspecialchars($user['email']) ?></div>
        <a href="#editProfil" class="profil-edit-btn" onclick="showTab('edit')">
          <i class="fa fa-pen"></i> Edit Profil
        </a>
      </div>
      <div class="profil-menu">
        <div class="profil-menu-section">My Orders</div>
        <a href="#" class="profil-menu-item active" onclick="showTab('pesanan'); return false;">
          <span class="left"><i class="fa fa-box"></i> Daftar Pesanan</span>
          <span class="menu-badge"><?= count($pesanan_list) ?></span>
        </a>
        <a href="#" class="profil-menu-item" onclick="showTab('riwayat'); return false;">
          <span class="left"><i class="fa fa-history"></i> Riwayat Belanja</span>
          <i class="fa fa-chevron-right" style="font-size:11px;color:var(--text-muted)"></i>
        </a>

        <div class="profil-menu-section">Settings & Security</div>
        <a href="#" class="profil-menu-item" onclick="showTab('alamat'); return false;">
          <span class="left"><i class="fa fa-map-marker-alt"></i> Alamat Saya</span>
          <i class="fa fa-chevron-right" style="font-size:11px;color:var(--text-muted)"></i>
        </a>
        <a href="#" class="profil-menu-item" onclick="showTab('keamanan'); return false;">
          <span class="left"><i class="fa fa-shield-alt"></i> Keamanan Akun</span>
          <i class="fa fa-chevron-right" style="font-size:11px;color:var(--text-muted)"></i>
        </a>

        <div class="profil-menu-section">Support</div>
        <a href="#" class="profil-menu-item">
          <span class="left"><i class="fa fa-question-circle"></i> Pusat Bantuan</span>
          <i class="fa fa-chevron-right" style="font-size:11px;color:var(--text-muted)"></i>
        </a>
        <a href="#" class="profil-menu-item">
          <span class="left"><i class="fa fa-headset"></i> Hubungi Kami</span>
          <i class="fa fa-chevron-right" style="font-size:11px;color:var(--text-muted)"></i>
        </a>

        <a href="logout.php" class="btn-logout">
          <i class="fa fa-sign-out-alt"></i> Keluar
        </a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="profil-main">

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-box">
        <i class="fa fa-box"></i>
        <div class="num"><?= count($pesanan_list) ?></div>
        <div class="lbl">Total Pesanan</div>
      </div>
      <div class="stat-box">
        <i class="fa fa-check-circle"></i>
        <div class="num"><?= count(array_filter($pesanan_list, fn($p) => $p['status'] === 'selesai')) ?></div>
        <div class="lbl">Selesai</div>
      </div>
      <div class="stat-box">
        <i class="fa fa-star"></i>
        <div class="num">2</div>
        <div class="lbl">Ulasan</div>
      </div>
    </div>

    <!-- Tab: Daftar Pesanan -->
    <div class="main-card" id="tab-pesanan">
      <div class="main-card-header">
        <h3><i class="fa fa-box"></i> Daftar Pesanan</h3>
      </div>
      <div class="main-card-body" style="padding:0; overflow-x:auto;">
        <table class="pesanan-table">
          <thead>
            <tr>
              <th>No. Pesanan</th>
              <th>Produk</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pesanan_list as $p):
              $st = $status_label[$p['status']] ?? ['label'=>ucfirst($p['status']),'color'=>'#6b7280'];
            ?>
            <tr>
              <td style="font-family:monospace; font-weight:700; color:var(--primary);"><?= htmlspecialchars($p['kode']) ?></td>
              <td style="color:var(--text-muted);">—</td>
              <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
              <td style="font-weight:700; color:var(--text-red);">Rp <?= number_format($p['total'], 0, ',', '.') ?></td>
              <td>
                <span class="status-badge" style="background:<?= $st['color'] ?>;">
                  <?= $st['label'] ?>
                </span>
              </td>
              <td>
                <a href="lacak-pesanan.php?kode=<?= urlencode($p['kode']) ?>" class="btn-detail">Detail</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab: Edit Profil -->
    <div class="main-card" id="tab-edit" style="display:none;">
      <div class="main-card-header">
        <h3><i class="fa fa-user-edit"></i> Edit Profil</h3>
      </div>
      <div class="main-card-body edit-form">
        <form method="POST">
          <div class="form-grid-2">
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div class="form-group">
              <label>Nomor Telepon</label>
              <input type="tel" name="telepon" class="form-control" placeholder="0812 XXXX XXXX">
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:8px;">
            <i class="fa fa-save"></i> Simpan Perubahan
          </button>
        </form>
      </div>
    </div>

    <!-- Tab: Keamanan -->
    <div class="main-card" id="tab-keamanan" style="display:none;">
      <div class="main-card-header">
        <h3><i class="fa fa-shield-alt"></i> Keamanan Akun</h3>
      </div>
      <div class="main-card-body">
        <form method="POST">
          <div class="form-group">
            <label>Kata Sandi Lama</label>
            <div class="input-wrapper">
              <input type="password" name="pass_lama" class="form-control" placeholder="••••••••">
              <span class="input-icon" onclick="togglePassword('pass_lama', this)"><i class="fa fa-eye-slash"></i></span>
            </div>
          </div>
          <div class="form-group">
            <label>Kata Sandi Baru</label>
            <div class="input-wrapper">
              <input type="password" name="pass_baru" class="form-control" placeholder="Min. 8 karakter">
              <span class="input-icon" onclick="togglePassword('pass_baru', this)"><i class="fa fa-eye-slash"></i></span>
            </div>
          </div>
          <div class="form-group">
            <label>Konfirmasi Kata Sandi Baru</label>
            <div class="input-wrapper">
              <input type="password" name="pass_konfirm" class="form-control" placeholder="Ulangi kata sandi baru">
              <span class="input-icon" onclick="togglePassword('pass_konfirm', this)"><i class="fa fa-eye-slash"></i></span>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-lock"></i> Ubah Kata Sandi
          </button>
        </form>
      </div>
    </div>

    <!-- Tab: Alamat -->
    <div class="main-card" id="tab-alamat" style="display:none;">
      <div class="main-card-header">
        <h3><i class="fa fa-map-marker-alt"></i> Alamat Saya</h3>
      </div>
      <div class="main-card-body">
        <div style="background:var(--bg-light);border-radius:var(--radius-md);padding:16px 20px;border:1px solid var(--border);">
          <div style="font-weight:700;margin-bottom:4px;">Rumah Utama</div>
          <div style="font-size:13px;color:var(--text-muted);">Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan, 12110</div>
          <div style="margin-top:10px;display:flex;gap:8px;">
            <button class="btn btn-outline" style="padding:6px 14px;font-size:12px;">Edit</button>
            <button class="btn" style="padding:6px 14px;font-size:12px;color:#ef4444;border:1px solid #ef4444;border-radius:var(--radius-lg);">Hapus</button>
          </div>
        </div>
        <button class="btn btn-primary" style="margin-top:14px;font-size:13px;padding:10px 20px;">
          <i class="fa fa-plus"></i> Tambah Alamat Baru
        </button>
      </div>
    </div>

    <!-- Tab: Riwayat -->
    <div class="main-card" id="tab-riwayat" style="display:none;">
      <div class="main-card-header">
        <h3><i class="fa fa-history"></i> Riwayat Belanja</h3>
      </div>
      <div class="main-card-body">
        <?php if (empty($pesanan_selesai)): ?>
          <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <i class="fa fa-box-open" style="font-size:40px;opacity:0.2;margin-bottom:12px;display:block;"></i>
            <p>Belum ada pesanan selesai.</p>
          </div>
        <?php else: ?>
          <?php foreach ($pesanan_selesai as $p): ?>
          <div style="border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:16px;overflow:hidden;">
            <div style="background:var(--bg-light);padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);">
              <div>
                <span style="font-family:monospace;font-weight:700;color:var(--primary);"><?= htmlspecialchars($p['kode']) ?></span>
                <span style="font-size:12px;color:var(--text-muted);margin-left:10px;"><?= date('d M Y', strtotime($p['created_at'])) ?></span>
              </div>
              <span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:700;">Selesai</span>
            </div>
            <?php foreach ($p['detail'] as $item): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);gap:12px;flex-wrap:wrap;">
              <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:48px;height:48px;background:var(--bg-card);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <?php if (!empty($item['gambar'])): ?>
                    <img src="uploads/<?= htmlspecialchars($item['gambar']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-sm);">
                  <?php else: ?>
                    <i class="fa fa-image" style="color:var(--border);font-size:18px;"></i>
                  <?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($item['nama']) ?></div>
                  <?php if (!empty($item['varian'])): ?>
                    <div style="font-size:12px;color:var(--text-muted);">Varian: <?= htmlspecialchars($item['varian']) ?></div>
                  <?php endif; ?>
                  <div style="font-size:12px;color:var(--text-muted);"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                </div>
              </div>
              <a href="ulasan.php?id_produk=<?= $item['id_produk'] ?>&id_pesanan=<?= $p['id_pesanan'] ?>&varian=<?= urlencode($item['varian'] ?? '') ?>"
                 style="background:var(--primary);color:white;padding:7px 16px;border-radius:9999px;font-size:12px;font-weight:700;white-space:nowrap;display:flex;align-items:center;gap:6px;text-decoration:none;">
                <i class="fa fa-star"></i> Beri Ulasan
              </a>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function showTab(tab) {
  ['pesanan','edit','keamanan','alamat','riwayat'].forEach(t => {
    const el = document.getElementById('tab-' + t);
    if (el) el.style.display = 'none';
  });
  document.querySelectorAll('.profil-menu-item').forEach(el => el.classList.remove('active'));
  const target = document.getElementById('tab-' + tab);
  if (target) target.style.display = 'block';
}

function togglePassword(id, icon) {
  const input = document.getElementById(id);
  const i = icon.querySelector('i');
  if (input.type === 'password') { input.type = 'text'; i.className = 'fa fa-eye'; }
  else { input.type = 'password'; i.className = 'fa fa-eye-slash'; }
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
