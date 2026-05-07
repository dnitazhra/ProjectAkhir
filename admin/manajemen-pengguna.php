<?php
session_start();
include '../koneksi.php';
include '../includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') { header("Location: ../login.php"); exit; }

// Hapus user
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    $sh  = mysqli_prepare($conn, "DELETE FROM user WHERE id_user = ? AND role != 'admin'");
    mysqli_stmt_bind_param($sh, 'i', $hid);
    mysqli_stmt_execute($sh);
    mysqli_stmt_close($sh);
    header("Location: manajemen-pengguna.php"); exit;
}

$users = getAllUserAdmin($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Pengguna - lavo.id</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Reuse admin layout styles */
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar {
      width: 240px; background: #1e1e2e; color: white; flex-shrink: 0;
      display: flex; flex-direction: column; position: fixed;
      top: 0; left: 0; height: 100vh; z-index: 50; transition: left 0.3s;
    }
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
    .admin-topbar h1 { font-size: 18px; font-weight: 700; color: var(--text-dark); }
    .admin-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }
    .admin-user { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; }
    .btn-menu-admin { display: none; background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-dark); }
    .admin-content { padding: 24px; }
    .table-card { background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden; }
    .table-card-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); }
    .table-card-header h3 { font-size: 16px; font-weight: 700; }
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { text-align: left; padding: 11px 16px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg-light); border-bottom: 1px solid var(--border); }
    .admin-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .admin-table tr:last-child td { border-bottom: none; }
    .admin-table tr:hover td { background: var(--bg-light); }
    .role-badge { display: inline-block; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
    .btn-hapus-user { background: #fee2e2; color: #dc2626; border: none; padding: 5px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .btn-hapus-user:hover { background: #fecaca; }
    .btn-detail-user { background: #eff6ff; color: #2563eb; border: none; padding: 5px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; cursor: pointer; margin-right: 4px; }
    .btn-detail-user:hover { background: #dbeafe; }
    .search-bar { display: flex; align-items: center; background: var(--bg-light); border: 1px solid var(--border); border-radius: 9999px; padding: 0 14px; gap: 8px; height: 38px; }
    .search-bar input { border: none; background: transparent; outline: none; font-size: 13px; width: 200px; }

    /* ── Modal Detail User ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.5); z-index: 1000;
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: white; border-radius: 16px; width: 520px; max-width: 95vw;
      max-height: 90vh; overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      animation: modalIn 0.2s ease;
    }
    @keyframes modalIn {
      from { transform: scale(0.92); opacity: 0; }
      to   { transform: scale(1);    opacity: 1; }
    }
    .modal-header {
      background: linear-gradient(135deg, var(--primary), #c0392b);
      padding: 24px; border-radius: 16px 16px 0 0;
      display: flex; align-items: center; gap: 16px;
    }
    .modal-avatar {
      width: 60px; height: 60px; border-radius: 50%;
      background: rgba(255,255,255,0.25); color: white;
      font-size: 24px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      border: 3px solid rgba(255,255,255,0.4); flex-shrink: 0;
    }
    .modal-header-info { flex: 1; }
    .modal-header-info h2 { font-size: 18px; font-weight: 700; color: white; margin-bottom: 4px; }
    .modal-header-info p  { font-size: 13px; color: rgba(255,255,255,0.8); }
    .modal-close {
      background: rgba(255,255,255,0.2); border: none; color: white;
      width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
      font-size: 16px; display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .modal-close:hover { background: rgba(255,255,255,0.35); }
    .modal-body { padding: 24px; }
    .modal-section { margin-bottom: 20px; }
    .modal-section-title {
      font-size: 11px; font-weight: 700; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 1px;
      margin-bottom: 12px; padding-bottom: 6px;
      border-bottom: 1px solid var(--border);
    }
    .modal-row {
      display: flex; gap: 12px; padding: 8px 0;
      border-bottom: 1px solid #f3f4f6; font-size: 14px;
    }
    .modal-row:last-child { border-bottom: none; }
    .modal-row .lbl { color: var(--text-muted); min-width: 130px; flex-shrink: 0; }
    .modal-row .val { font-weight: 600; color: var(--text-dark); word-break: break-word; }
    .modal-stats {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
      margin-bottom: 20px;
    }
    .modal-stat {
      background: var(--bg-light); border-radius: 10px;
      padding: 14px; text-align: center;
    }
    .modal-stat i { font-size: 18px; color: var(--primary); margin-bottom: 6px; display: block; }
    .modal-stat .num { font-size: 20px; font-weight: 700; color: var(--text-dark); }
    .modal-stat .lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .modal-footer {
      padding: 16px 24px; border-top: 1px solid var(--border);
      display: flex; gap: 10px; justify-content: flex-end;
    }

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
      <div style="width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">L</div>
      <div><span>lavo.id</span><small>Admin Panel</small></div>
    </div>
    <nav class="admin-nav">
      <div class="admin-nav-section">Main</div>
      <a href="dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <div class="admin-nav-section">Kelola</div>
      <a href="manajemen-pengguna.php" class="active"><i class="fa fa-users"></i> Manajemen Pengguna</a>
      <a href="riwayat-pesanan.php"><i class="fa fa-receipt"></i> Riwayat Pesanan</a>
      <div class="admin-nav-section">Lainnya</div>
      <a href="../index.php"><i class="fa fa-store"></i> Lihat Toko</a>
      <a href="profil.php"><i class="fa fa-user-shield"></i> Profil Admin</a>
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
        <h1>Manajemen Pengguna</h1>
      </div>
      <div class="admin-user">
        <div class="admin-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
        <span><?= htmlspecialchars($user['nama']) ?></span>
      </div>
    </div>

    <div class="admin-content">
      <div class="table-card">
        <div class="table-card-header">
          <h3><i class="fa fa-users" style="color:var(--primary);margin-right:8px;"></i>Data Pengguna</h3>
          <div class="search-bar">
            <i class="fa fa-search" style="color:var(--text-muted);font-size:13px;"></i>
            <input type="text" id="searchUser" placeholder="Cari pengguna..." oninput="filterUser(this.value)">
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="admin-table" id="userTable">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Telepon</th>
                <th>Alamat</th>
                <th>Role</th>
                <th>Pesanan</th>
                <th>Bergabung</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $i => $u): ?>
              <tr style="cursor:pointer;" onclick="lihatDetail(<?= $u['id_user'] ?>)">
                <td style="color:var(--text-muted);"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">
                      <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600;"><?= htmlspecialchars($u['nama']) ?></span>
                  </div>
                </td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['telepon'] ?? '-') ?></td>
                <td style="font-size:12px;color:var(--text-muted);max-width:200px;">
                  <?php
                  $alamat_parts = array_filter([
                    $u['alamat']    ?? '',
                    $u['kecamatan'] ?? '',
                    $u['kabupaten'] ?? '',
                    $u['provinsi']  ?? '',
                    $u['kode_pos']  ?? '',
                  ]);
                  echo $alamat_parts ? htmlspecialchars(implode(', ', $alamat_parts)) : '<span style="color:#d1d5db;">—</span>';
                  ?>
                </td>
                <td>
                  <span class="role-badge" style="background:<?= $u['role']==='admin' ? '#fef3c7' : '#dbeafe' ?>;color:<?= $u['role']==='admin' ? '#92400e' : '#1d4ed8' ?>;">
                    <?= ucfirst($u['role']) ?>
                  </span>
                </td>
                <td style="text-align:center;font-weight:700;"><?= $u['total_pesanan'] ?></td>
                <td style="color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td onclick="event.stopPropagation()">
                  <button class="btn-detail-user" onclick="lihatDetail(<?= $u['id_user'] ?>)">
                    <i class="fa fa-eye"></i> Detail
                  </button>
                  <?php if ($u['role'] !== 'admin'): ?>
                  <a href="manajemen-pengguna.php?hapus=<?= $u['id_user'] ?>"
                     class="btn-hapus-user"
                     onclick="return confirm('Hapus pengguna ini?')">
                    <i class="fa fa-trash"></i> Hapus
                  </a>
                  <?php else: ?>
                  <span style="font-size:12px;color:var(--text-muted);">—</span>
                  <?php endif; ?>
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

<!-- ── Modal Detail Pengguna ── -->
<div class="modal-overlay" id="modalDetail" onclick="tutupModal(event)">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-avatar" id="mdAvatar"></div>
      <div class="modal-header-info">
        <h2 id="mdNama"></h2>
        <p id="mdEmail"></p>
      </div>
      <button class="modal-close" onclick="document.getElementById('modalDetail').classList.remove('open')">
        <i class="fa fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <!-- Stats -->
      <div class="modal-stats">
        <div class="modal-stat">
          <i class="fa fa-box"></i>
          <div class="num" id="mdPesanan">0</div>
          <div class="lbl">Total Pesanan</div>
        </div>
        <div class="modal-stat">
          <i class="fa fa-user-tag"></i>
          <div class="num" id="mdRole">—</div>
          <div class="lbl">Role</div>
        </div>
        <div class="modal-stat">
          <i class="fa fa-calendar-alt"></i>
          <div class="num" id="mdTahun">—</div>
          <div class="lbl">Bergabung</div>
        </div>
      </div>

      <!-- Info Akun -->
      <div class="modal-section">
        <div class="modal-section-title"><i class="fa fa-user" style="margin-right:6px;"></i>Informasi Akun</div>
        <div class="modal-row">
          <span class="lbl">Nama Lengkap</span>
          <span class="val" id="mdNamaFull">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Email</span>
          <span class="val" id="mdEmailFull">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">No. Telepon</span>
          <span class="val" id="mdTelepon">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Role</span>
          <span class="val" id="mdRoleFull">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Bergabung Sejak</span>
          <span class="val" id="mdBergabung">—</span>
        </div>
      </div>

      <!-- Alamat -->
      <div class="modal-section">
        <div class="modal-section-title"><i class="fa fa-map-marker-alt" style="margin-right:6px;"></i>Alamat</div>
        <div class="modal-row">
          <span class="lbl">Alamat</span>
          <span class="val" id="mdAlamat">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Kecamatan</span>
          <span class="val" id="mdKecamatan">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Kabupaten/Kota</span>
          <span class="val" id="mdKabupaten">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Provinsi</span>
          <span class="val" id="mdProvinsi">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Kode Pos</span>
          <span class="val" id="mdKodePos">—</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button onclick="document.getElementById('modalDetail').classList.remove('open')"
        style="padding:8px 20px;border-radius:9999px;border:1.5px solid var(--border);background:white;font-size:13px;font-weight:600;cursor:pointer;">
        Tutup
      </button>
      <a id="mdLihatPesanan" href="#"
        style="padding:8px 20px;border-radius:9999px;background:var(--primary);color:white;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <i class="fa fa-receipt"></i> Lihat Pesanan
      </a>
    </div>
  </div>
</div>

<?php
// Siapkan data semua user sebagai JSON untuk JS
$users_json = [];
foreach ($users as $u) {
    $users_json[$u['id_user']] = [
        'id'           => $u['id_user'],
        'nama'         => $u['nama'],
        'email'        => $u['email'],
        'telepon'      => $u['telepon']      ?? '',
        'role'         => $u['role'],
        'alamat'       => $u['alamat']       ?? '',
        'kecamatan'    => $u['kecamatan']    ?? '',
        'kabupaten'    => $u['kabupaten']    ?? '',
        'provinsi'     => $u['provinsi']     ?? '',
        'kode_pos'     => $u['kode_pos']     ?? '',
        'total_pesanan'=> $u['total_pesanan'],
        'created_at'   => $u['created_at'],
    ];
}
?>

<script>
const usersData = <?= json_encode($users_json, JSON_UNESCAPED_UNICODE) ?>;

function lihatDetail(id) {
  const u = usersData[id];
  if (!u) return;

  const tgl = new Date(u.created_at);
  const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  const tglStr = tgl.getDate() + ' ' + bulan[tgl.getMonth()] + ' ' + tgl.getFullYear();

  document.getElementById('mdAvatar').textContent    = u.nama.charAt(0).toUpperCase();
  document.getElementById('mdNama').textContent      = u.nama;
  document.getElementById('mdEmail').textContent     = u.email;
  document.getElementById('mdPesanan').textContent   = u.total_pesanan;
  document.getElementById('mdRole').textContent      = u.role.charAt(0).toUpperCase() + u.role.slice(1);
  document.getElementById('mdTahun').textContent     = tgl.getFullYear();
  document.getElementById('mdNamaFull').textContent  = u.nama;
  document.getElementById('mdEmailFull').textContent = u.email;
  document.getElementById('mdTelepon').textContent   = u.telepon  || '—';
  document.getElementById('mdRoleFull').innerHTML    = u.role === 'admin'
    ? '<span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:9999px;font-size:12px;">Admin</span>'
    : '<span style="background:#dbeafe;color:#1d4ed8;padding:2px 10px;border-radius:9999px;font-size:12px;">Pembeli</span>';
  document.getElementById('mdBergabung').textContent = tglStr;
  document.getElementById('mdAlamat').textContent    = u.alamat    || '—';
  document.getElementById('mdKecamatan').textContent = u.kecamatan || '—';
  document.getElementById('mdKabupaten').textContent = u.kabupaten || '—';
  document.getElementById('mdProvinsi').textContent  = u.provinsi  || '—';
  document.getElementById('mdKodePos').textContent   = u.kode_pos  || '—';
  document.getElementById('mdLihatPesanan').href     = 'riwayat-pesanan.php';

  document.getElementById('modalDetail').classList.add('open');
}

function tutupModal(e) {
  if (e.target === document.getElementById('modalDetail')) {
    document.getElementById('modalDetail').classList.remove('open');
  }
}

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.getElementById('modalDetail').classList.remove('open');
});

function filterUser(q) {
  const rows = document.querySelectorAll('#userTable tbody tr');
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
}
</script>
</body>
</html>
