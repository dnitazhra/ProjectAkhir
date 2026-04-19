<?php
session_start();
include '../koneksi.php';
include '../includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') { header("Location: ../login.php"); exit; }

// Update status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode'], $_POST['status'])) {
    $kode_p  = trim($_POST['kode']);
    $status_p= trim($_POST['status']);
    $allowed = ['pending','diproses','dikemas','dikirim','selesai'];
    if (in_array($status_p, $allowed)) {
        $su = mysqli_prepare($conn, "UPDATE pesanan SET status = ? WHERE kode = ?");
        mysqli_stmt_bind_param($su, 'ss', $status_p, $kode_p);
        mysqli_stmt_execute($su);
        mysqli_stmt_close($su);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

$pesanan_list = getAllPesananAdmin($conn);

$status_cfg = [
    'pending'  => ['label'=>'Pending',  'bg'=>'#fef3c7','color'=>'#92400e'],
    'diproses' => ['label'=>'Diproses', 'bg'=>'#dbeafe','color'=>'#1d4ed8'],
    'dikemas'  => ['label'=>'Dikemas',  'bg'=>'#ede9fe','color'=>'#6d28d9'],
    'dikirim'  => ['label'=>'Dikirim',  'bg'=>'#cffafe','color'=>'#0e7490'],
    'selesai'  => ['label'=>'Selesai',  'bg'=>'#dcfce7','color'=>'#15803d'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pesanan - Happy Snack</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 240px; background: #1e1e2e; color: white; flex-shrink: 0; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 50; transition: left 0.3s; }
    .admin-sidebar-logo { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
    .admin-sidebar-logo span { font-size: 18px; font-weight: 700; color: var(--accent); font-family: 'Times New Roman', serif; }
    .admin-sidebar-logo small { display: block; font-size: 10px; color: rgba(255,255,255,0.5); }
    .admin-nav { padding: 12px 0; flex: 1; }
    .admin-nav-section { padding: 8px 16px 4px; font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1px; }
    .admin-nav a { display: flex; align-items: center; gap: 10px; padding: 11px 20px; font-size: 14px; color: rgba(255,255,255,0.7); transition: all 0.2s; border-left: 3px solid transparent; text-decoration: none; }
    .admin-nav a:hover { background: rgba(255,255,255,0.07); color: white; }
    .admin-nav a.active { background: rgba(171,53,0,0.3); color: white; border-left-color: var(--primary); }
    .admin-nav a i { width: 18px; text-align: center; }
    .admin-sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
    .admin-sidebar-footer a { display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.6); transition: color 0.2s; text-decoration: none; }
    .admin-sidebar-footer a:hover { color: white; }
    .admin-main { margin-left: 240px; flex: 1; background: #f4f6f9; min-height: 100vh; }
    .admin-topbar { background: white; border-bottom: 1px solid var(--border); padding: 0 24px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 40; }
    .admin-topbar h1 { font-size: 18px; font-weight: 700; }
    .admin-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }
    .admin-user { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; }
    .btn-menu-admin { display: none; background: none; border: none; font-size: 20px; cursor: pointer; }
    .admin-content { padding: 24px; }
    .table-card { background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden; }
    .table-card-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px; }
    .table-card-header h3 { font-size: 16px; font-weight: 700; }
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg-light); border-bottom: 1px solid var(--border); }
    .admin-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .admin-table tr:last-child td { border-bottom: none; }
    .admin-table tr:hover td { background: var(--bg-light); }
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
    .search-bar { display: flex; align-items: center; background: var(--bg-light); border: 1px solid var(--border); border-radius: 9999px; padding: 0 14px; gap: 8px; height: 38px; }
    .search-bar input { border: none; background: transparent; outline: none; font-size: 13px; width: 180px; }
    .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .filter-tab { padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1.5px solid var(--border); background: white; color: var(--text-muted); transition: all 0.2s; }
    .filter-tab.active, .filter-tab:hover { background: var(--primary); color: white; border-color: var(--primary); }
    .status-select { padding: 5px 10px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 12px; font-family: inherit; outline: none; cursor: pointer; }
    .status-select:focus { border-color: var(--primary); }
    @media (max-width: 900px) {
      .admin-sidebar { left: -240px; }
      .admin-sidebar.open { left: 0; }
      .admin-main { margin-left: 0; }
      .btn-menu-admin { display: block; }
    }
  </style>
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-logo">
      <div style="width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">H</div>
      <div><span>Happy Snack</span><small>Admin Panel</small></div>
    </div>
    <nav class="admin-nav">
      <div class="admin-nav-section">Main</div>
      <a href="dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <div class="admin-nav-section">Kelola</div>
      <a href="manajemen-pengguna.php"><i class="fa fa-users"></i> Manajemen Pengguna</a>
      <a href="riwayat-pesanan.php" class="active"><i class="fa fa-receipt"></i> Riwayat Pesanan</a>
      <div class="admin-nav-section">Lainnya</div>
      <a href="../index.php"><i class="fa fa-store"></i> Lihat Toko</a>
    </nav>
    <div class="admin-sidebar-footer">
      <a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
    </div>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button class="btn-menu-admin" onclick="document.getElementById('adminSidebar').classList.toggle('open')"><i class="fa fa-bars"></i></button>
        <a href="dashboard.php" class="btn-back" style="margin-bottom:0;">
          <i class="fa fa-arrow-left"></i> Dashboard
        </a>
        <h1>Riwayat Pesanan</h1>
      </div>
      <div class="admin-user">
        <div class="admin-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
        <span><?= htmlspecialchars($user['nama']) ?></span>
      </div>
    </div>

    <div class="admin-content">
      <div class="table-card">
        <div class="table-card-header">
          <h3><i class="fa fa-receipt" style="color:var(--primary);margin-right:8px;"></i>Semua Pesanan</h3>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <div class="filter-tabs">
              <button class="filter-tab active" onclick="filterStatus('semua', this)">Semua</button>
              <button class="filter-tab" onclick="filterStatus('pending', this)">Pending</button>
              <button class="filter-tab" onclick="filterStatus('diproses', this)">Diproses</button>
              <button class="filter-tab" onclick="filterStatus('dikirim', this)">Dikirim</button>
              <button class="filter-tab" onclick="filterStatus('selesai', this)">Selesai</button>
            </div>
            <div class="search-bar">
              <i class="fa fa-search" style="color:var(--text-muted);font-size:13px;"></i>
              <input type="text" placeholder="Cari pesanan..." oninput="cariPesanan(this.value)">
            </div>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table" id="pesananTable">
            <thead>
              <tr>
                <th>No</th>
                <th>Kode Pesanan</th>
                <th>Nama Pembeli</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Alamat</th>
                <th>Pembayaran</th>
                <th>Tanggal</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pesanan_list as $i => $p): $s = $status_cfg[$p['status']] ?? $status_cfg['pending']; ?>
              <tr data-status="<?= $p['status'] ?>">
                <td style="color:var(--text-muted);"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></td>
                <td style="font-family:monospace;font-weight:700;color:var(--primary);"><?= htmlspecialchars($p['kode']) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                      <?= strtoupper(substr($p['nama_pembeli'], 0, 1)) ?>
                    </div>
                    <?= htmlspecialchars($p['nama_pembeli']) ?>
                  </div>
                </td>
                <td style="font-weight:600;">—</td>
                <td style="text-align:center;">—</td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($p['kota'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['pembayaran']) ?></td>
                <td style="color:var(--text-muted);"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td>
                  <select class="status-select"
                          onchange="ubahStatus(this, '<?= $p['kode'] ?>')"
                          style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;border-color:<?= $s['bg'] ?>;">
                    <?php foreach ($status_cfg as $key => $cfg): ?>
                    <option value="<?= $key ?>" <?= $p['status'] === $key ? 'selected' : '' ?>>
                      <?= $cfg['label'] ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
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

<script>
function filterStatus(status, btn) {
  document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#pesananTable tbody tr').forEach(row => {
    row.style.display = (status === 'semua' || row.dataset.status === status) ? '' : 'none';
  });
}

function cariPesanan(q) {
  document.querySelectorAll('#pesananTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
}

function ubahStatus(select, kode) {
  const cfg = {
    pending:  { bg:'#fef3c7', color:'#92400e' },
    diproses: { bg:'#dbeafe', color:'#1d4ed8' },
    dikemas:  { bg:'#ede9fe', color:'#6d28d9' },
    dikirim:  { bg:'#cffafe', color:'#0e7490' },
    selesai:  { bg:'#dcfce7', color:'#15803d' },
  };
  const s = cfg[select.value];
  select.style.background    = s.bg;
  select.style.color         = s.color;
  select.style.borderColor   = s.bg;
  select.closest('tr').dataset.status = select.value;

  // Simpan ke DB
  fetch('riwayat-pesanan.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'kode=' + encodeURIComponent(kode) + '&status=' + encodeURIComponent(select.value)
  }).then(() => showToast('Status pesanan ' + kode + ' diperbarui!'));
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
