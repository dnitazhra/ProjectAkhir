<?php
session_start();
include '../koneksi.php';
include '../includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') { header("Location: ../login.php"); exit; }

// Proses tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_produk'])) {
    $nama_p   = trim($_POST['nama_produk']);
    $kat_nama = trim($_POST['kategori']);
    $harga    = (float)$_POST['harga'];
    $satuan   = trim($_POST['satuan']);
    $stok     = (int)$_POST['stok'];
    $deskripsi= trim($_POST['deskripsi'] ?? '');
    $gambar   = uploadGambar($_FILES['gambar'] ?? [], '../uploads/');

    // Cari id_kategori
    $sk = mysqli_prepare($conn, "SELECT id_kategori FROM kategori WHERE nama = ?");
    mysqli_stmt_bind_param($sk, 's', $kat_nama);
    mysqli_stmt_execute($sk);
    mysqli_stmt_bind_result($sk, $id_kat);
    mysqli_stmt_fetch($sk);
    mysqli_stmt_close($sk);

    if ($id_kat) {
        $sp = mysqli_prepare($conn,
            "INSERT INTO produk (id_kategori, nama, harga, satuan, stok, deskripsi, gambar)
             VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($sp, 'isdsiss', $id_kat, $nama_p, $harga, $satuan, $stok, $deskripsi, $gambar);
        mysqli_stmt_execute($sp);
        mysqli_stmt_close($sp);
    }
    header("Location: dashboard.php"); exit;
}

// Proses hapus produk
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    $sh  = mysqli_prepare($conn, "DELETE FROM produk WHERE id_produk = ?");
    mysqli_stmt_bind_param($sh, 'i', $hid);
    mysqli_stmt_execute($sh);
    mysqli_stmt_close($sh);
    header("Location: dashboard.php"); exit;
}

$stats       = getStatsAdmin($conn);
$produk_list = getAllProdukAdmin($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Happy Snack</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }

    /* Sidebar Admin */
    .admin-sidebar {
      width: 240px;
      background: #1e1e2e;
      color: white;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0;
      height: 100vh;
      z-index: 50;
      transition: left 0.3s;
    }

    .admin-sidebar-logo {
      padding: 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .admin-sidebar-logo span {
      font-size: 18px;
      font-weight: 700;
      color: var(--accent);
      font-family: 'Times New Roman', serif;
    }

    .admin-sidebar-logo small {
      display: block;
      font-size: 10px;
      color: rgba(255,255,255,0.5);
      font-family: sans-serif;
    }

    .admin-nav { padding: 12px 0; flex: 1; }

    .admin-nav-section {
      padding: 8px 16px 4px;
      font-size: 10px;
      font-weight: 700;
      color: rgba(255,255,255,0.4);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .admin-nav a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 20px;
      font-size: 14px;
      color: rgba(255,255,255,0.7);
      transition: all 0.2s;
      border-left: 3px solid transparent;
      text-decoration: none;
    }

    .admin-nav a:hover { background: rgba(255,255,255,0.07); color: white; }
    .admin-nav a.active { background: rgba(171,53,0,0.3); color: white; border-left-color: var(--primary); }
    .admin-nav a i { width: 18px; text-align: center; }

    .admin-sidebar-footer {
      padding: 16px 20px;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    .admin-sidebar-footer a {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: rgba(255,255,255,0.6);
      transition: color 0.2s;
      text-decoration: none;
    }

    .admin-sidebar-footer a:hover { color: white; }

    /* Main */
    .admin-main {
      margin-left: 240px;
      flex: 1;
      background: #f4f6f9;
      min-height: 100vh;
    }

    /* Topbar */
    .admin-topbar {
      background: white;
      border-bottom: 1px solid var(--border);
      padding: 0 24px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 40;
    }

    .admin-topbar h1 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .admin-topbar-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .admin-user {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-dark);
    }

    .admin-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 700;
    }

    .btn-menu-admin {
      display: none;
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: var(--text-dark);
    }

    /* Content */
    .admin-content { padding: 24px; }

    /* Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: white;
      border-radius: var(--radius-md);
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: var(--shadow-sm);
    }

    .stat-icon {
      width: 52px;
      height: 52px;
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      color: white;
      flex-shrink: 0;
    }

    .stat-info .val {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-dark);
      line-height: 1;
    }

    .stat-info .lbl {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* Table card */
    .table-card {
      background: white;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }

    .table-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    .table-card-header h3 {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .admin-table { width: 100%; border-collapse: collapse; }

    .admin-table th {
      text-align: left;
      padding: 11px 16px;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: var(--bg-light);
      border-bottom: 1px solid var(--border);
    }

    .admin-table td {
      padding: 12px 16px;
      font-size: 14px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    .admin-table tr:last-child td { border-bottom: none; }
    .admin-table tr:hover td { background: var(--bg-light); }

    .btn-edit {
      background: #dbeafe;
      color: #1d4ed8;
      border: none;
      padding: 5px 12px;
      border-radius: var(--radius-full);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      margin-right: 4px;
      transition: background 0.2s;
    }

    .btn-edit:hover { background: #bfdbfe; }

    .btn-hapus {
      background: #fee2e2;
      color: #dc2626;
      border: none;
      padding: 5px 12px;
      border-radius: var(--radius-full);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-hapus:hover { background: #fecaca; }

    /* Modal Tambah Produk */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 200;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.active { display: flex; }

    .modal {
      background: white;
      border-radius: var(--radius-lg);
      padding: 28px;
      width: 100%;
      max-width: 480px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-header h3 { font-size: 18px; font-weight: 700; }

    .modal-close {
      font-size: 20px;
      color: var(--text-muted);
      cursor: pointer;
      padding: 4px;
    }

    @media (max-width: 900px) {
      .admin-sidebar { left: -240px; }
      .admin-sidebar.open { left: 0; }
      .admin-main { margin-left: 0; }
      .btn-menu-admin { display: block; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 480px) {
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .admin-content { padding: 16px; }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-logo">
      <div style="width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">H</div>
      <div>
        <span>Happy Snack</span>
        <small>Admin Panel</small>
      </div>
    </div>
    <nav class="admin-nav">
      <div class="admin-nav-section">Main</div>
      <a href="dashboard.php" class="active"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <div class="admin-nav-section">Kelola</div>
      <a href="manajemen-pengguna.php"><i class="fa fa-users"></i> Manajemen Pengguna</a>
      <a href="riwayat-pesanan.php"><i class="fa fa-receipt"></i> Riwayat Pesanan</a>
      <div class="admin-nav-section">Lainnya</div>
      <a href="../index.php"><i class="fa fa-store"></i> Lihat Toko</a>
    </nav>
    <div class="admin-sidebar-footer">
      <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
    </div>
  </aside>

  <!-- Main -->
  <div class="admin-main">

    <!-- Topbar -->
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button class="btn-menu-admin" onclick="toggleAdminSidebar()"><i class="fa fa-bars"></i></button>
        <h1>Dashboard Admin</h1>
      </div>
      <div class="admin-topbar-right">
        <div class="admin-user">
          <div class="admin-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
          <span><?= htmlspecialchars($user['nama']) ?></span>
        </div>
      </div>
    </div>

    <div class="admin-content">

      <!-- Stats -->
      <div class="stats-grid">
        <?php
        $stats_display = [
            ['label'=>'Total Penjualan', 'value'=> 'Rp '.number_format($stats['total_penjualan'],0,',','.'), 'icon'=>'fa-money-bill-wave','color'=>'#22c55e'],
            ['label'=>'Pesanan Baru',    'value'=> $stats['pesanan_baru'],    'icon'=>'fa-box',            'color'=>'#3b82f6'],
            ['label'=>'Produk Aktif',    'value'=> $stats['produk_aktif'],    'icon'=>'fa-store',          'color'=>'#f59e0b'],
            ['label'=>'Pelanggan',       'value'=> $stats['total_user'],      'icon'=>'fa-users',          'color'=>'#8b5cf6'],
        ];
        foreach ($stats_display as $s): ?>
        <div class="stat-card">
          <div class="stat-icon" style="background:<?= $s['color'] ?>;">
            <i class="fa <?= $s['icon'] ?>"></i>
          </div>
          <div class="stat-info">
            <div class="val"><?= $s['value'] ?></div>
            <div class="lbl"><?= $s['label'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Tabel Produk -->
      <div class="table-card">
        <div class="table-card-header">
          <h3><i class="fa fa-box" style="color:var(--primary);margin-right:8px;"></i>Daftar Produk Happy Snack</h3>
          <button class="btn btn-primary" style="padding:8px 16px;font-size:13px;" onclick="bukaModal()">
            <i class="fa fa-plus"></i> Tambah Produk
          </button>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Satuan</th>
                <th>Stok</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($produk_list as $i => $p): ?>
              <tr>
                <td style="color:var(--text-muted);"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($p['nama']) ?></td>
                <td style="color:var(--text-red);font-weight:700;">Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                <td><?= $p['satuan'] ?></td>
                <td>
                  <span style="background:<?= $p['stok'] > 0 ? '#dcfce7' : '#fee2e2' ?>;color:<?= $p['stok'] > 0 ? '#16a34a' : '#dc2626' ?>;padding:3px 10px;border-radius:9999px;font-size:12px;font-weight:700;">
                    <?= $p['stok'] ?>
                  </span>
                </td>
                <td>
                  <button class="btn-edit"><i class="fa fa-pen"></i> Edit</button>
                  <a href="dashboard.php?hapus=<?= $p['id_produk'] ?>"
                     class="btn-hapus"
                     onclick="return confirm('Hapus produk ini?')">
                    <i class="fa fa-trash"></i> Hapus
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Tambah Produk -->
<div class="modal-overlay" id="modalTambah">
  <div class="modal">
    <div class="modal-header">
      <h3>Tambah Produk Baru</h3>
      <span class="modal-close" onclick="tutupModal()"><i class="fa fa-times"></i></span>
    </div>
    <form method="POST">
      <div class="form-group">
        <label>Nama Produk</label>
        <input type="text" name="nama_produk" class="form-control" placeholder="Nama produk" required>
      </div>
      <div class="form-group">
        <label>Kategori</label>
        <select name="kategori" class="form-control">
          <option>Snack Kering</option>
          <option>Kue Kering</option>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label>Harga (Rp)</label>
          <input type="number" name="harga" class="form-control" placeholder="15000" required>
        </div>
        <div class="form-group">
          <label>Satuan</label>
          <input type="text" name="satuan" class="form-control" placeholder="250g / pcs">
        </div>
      </div>
      <div class="form-group">
        <label>Stok</label>
        <input type="number" name="stok" class="form-control" placeholder="50" required>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi produk..."></textarea>
      </div>
      <div class="form-group">
        <label>Gambar Produk</label>
        <input type="file" name="gambar" class="form-control" accept="image/*">
      </div>
      <div style="display:flex;gap:10px;margin-top:8px;">
        <button type="submit" class="btn btn-primary btn-full">
          <i class="fa fa-save"></i> Simpan Produk
        </button>
        <button type="button" class="btn btn-outline" onclick="tutupModal()" style="flex:1;">
          Batal
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
}
function bukaModal() {
  document.getElementById('modalTambah').classList.add('active');
}
function tutupModal() {
  document.getElementById('modalTambah').classList.remove('active');
}
function hapusProduk(btn) {
  if (confirm('Hapus produk ini?')) {
    btn.closest('tr').style.opacity = '0';
    btn.closest('tr').style.transition = 'opacity 0.3s';
    setTimeout(() => btn.closest('tr').remove(), 300);
  }
}
document.getElementById('modalTambah').addEventListener('click', function(e) {
  if (e.target === this) tutupModal();
});
</script>
</body>
</html>
