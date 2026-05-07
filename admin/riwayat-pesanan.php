<?php
session_start();
include '../koneksi.php';
include '../includes/functions.php';

$admin = $_SESSION['user'] ?? null;
if (!$admin || $admin['role'] !== 'admin') { header("Location: ../login.php"); exit; }

// Update status pesanan (dari tabel atau modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode'], $_POST['status'])) {
    $kode_p   = trim($_POST['kode']);
    $status_p = trim($_POST['status']);
    $allowed  = ['pending','diproses','dikemas','dikirim','selesai','ditolak'];
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

// Ambil detail item untuk setiap pesanan (untuk modal)
$detail_map = [];
foreach ($pesanan_list as $p) {
    $detail_map[$p['id_pesanan']] = getDetailPesanan($conn, $p['id_pesanan']);
}

$status_cfg = [
    'pending'  => ['label'=>'Pending',  'bg'=>'#fef3c7','color'=>'#92400e'],
    'diproses' => ['label'=>'Diproses', 'bg'=>'#dbeafe','color'=>'#1d4ed8'],
    'dikemas'  => ['label'=>'Dikemas',  'bg'=>'#ede9fe','color'=>'#6d28d9'],
    'dikirim'  => ['label'=>'Dikirim',  'bg'=>'#cffafe','color'=>'#0e7490'],
    'selesai'  => ['label'=>'Selesai',  'bg'=>'#dcfce7','color'=>'#15803d'],
    'ditolak'  => ['label'=>'Ditolak',  'bg'=>'#fee2e2','color'=>'#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pesanan - lavo.id</title>
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
    .btn-tolak { background: #fee2e2; color: #dc2626; border: none; padding: 5px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; cursor: pointer; transition: background 0.2s; white-space: nowrap; }
    .btn-tolak:hover { background: #fecaca; }
    .btn-detail-pesanan { background: #eff6ff; color: #2563eb; border: none; padding: 5px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; margin-right: 4px; }
    .btn-detail-pesanan:hover { background: #dbeafe; }

    /* ── Modal Detail Pesanan ── */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center; padding:16px; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:white; border-radius:16px; width:640px; max-width:100%; max-height:90vh; overflow-y:auto; box-shadow:0 24px 64px rgba(0,0,0,0.3); animation:modalIn 0.2s ease; }
    @keyframes modalIn { from{transform:scale(0.93);opacity:0} to{transform:scale(1);opacity:1} }
    .modal-header { background:linear-gradient(135deg,var(--primary),#c0392b); padding:20px 24px; border-radius:16px 16px 0 0; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .modal-header-left { display:flex; align-items:center; gap:14px; }
    .modal-kode { font-size:18px; font-weight:700; color:white; font-family:monospace; }
    .modal-tgl  { font-size:12px; color:rgba(255,255,255,0.8); margin-top:2px; }
    .modal-close { background:rgba(255,255,255,0.2); border:none; color:white; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .modal-close:hover { background:rgba(255,255,255,0.35); }
    .modal-body { padding:0; }
    .modal-section { padding:18px 24px; border-bottom:1px solid var(--border); }
    .modal-section:last-child { border-bottom:none; }
    .modal-section-title { font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:12px; display:flex; align-items:center; gap:6px; }
    .modal-section-title i { color:var(--primary); }
    .modal-row { display:flex; gap:12px; padding:6px 0; font-size:13px; }
    .modal-row .lbl { color:var(--text-muted); min-width:120px; flex-shrink:0; }
    .modal-row .val { font-weight:600; color:var(--text-dark); }
    .item-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f3f4f6; gap:12px; }
    .item-row:last-child { border-bottom:none; }
    .item-img { width:40px; height:40px; border-radius:8px; object-fit:cover; background:var(--bg-light); flex-shrink:0; }
    .item-img-placeholder { width:40px; height:40px; border-radius:8px; background:var(--bg-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .bayar-row { display:flex; justify-content:space-between; font-size:13px; padding:5px 0; color:var(--text-muted); }
    .bayar-row.total { border-top:1.5px solid var(--border); margin-top:6px; padding-top:10px; font-size:15px; font-weight:700; color:var(--text-dark); }
    .bayar-row.total .val { color:#dc2626; }
    .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .modal-status-select { padding:8px 14px; border:1.5px solid var(--border); border-radius:9999px; font-size:13px; font-family:inherit; outline:none; cursor:pointer; font-weight:600; }
    .btn-modal-tolak { background:#fee2e2; color:#dc2626; border:none; padding:8px 18px; border-radius:9999px; font-size:13px; font-weight:600; cursor:pointer; }
    .btn-modal-tolak:hover { background:#fecaca; }
    .btn-modal-close { margin-left:auto; padding:8px 20px; border-radius:9999px; border:1.5px solid var(--border); background:white; font-size:13px; font-weight:600; cursor:pointer; }

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
      <a href="manajemen-pengguna.php"><i class="fa fa-users"></i> Manajemen Pengguna</a>
      <a href="riwayat-pesanan.php" class="active"><i class="fa fa-receipt"></i> Riwayat Pesanan</a>
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
        <h1>Riwayat Pesanan</h1>
      </div>
      <div class="admin-user">
        <div class="admin-avatar"><?= strtoupper(substr($admin['nama'], 0, 1)) ?></div>
        <span><?= htmlspecialchars($admin['nama']) ?></span>
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
              <button class="filter-tab" onclick="filterStatus('ditolak', this)" style="color:#dc2626;border-color:#fecaca;">Ditolak</button>
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
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pesanan_list as $i => $p): $s = $status_cfg[$p['status']] ?? $status_cfg['pending']; ?>
              <tr data-status="<?= $p['status'] ?>" style="cursor:pointer;" onclick="lihatDetail(<?= $p['id_pesanan'] ?>)">
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
                <td style="font-size:12px;color:var(--text-muted);">
                  <?php
                  $items_p = $detail_map[$p['id_pesanan']] ?? [];
                  $nama_items = array_column($items_p, 'nama');
                  echo $nama_items ? htmlspecialchars(implode(', ', array_slice($nama_items, 0, 2))) . (count($nama_items) > 2 ? ' +' . (count($nama_items)-2) : '') : '—';
                  ?>
                </td>
                <td style="text-align:center;font-weight:700;">
                  <?= array_sum(array_column($detail_map[$p['id_pesanan']] ?? [], 'jumlah')) ?: '—' ?>
                </td>
                <td style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($p['kota'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['pembayaran']) ?></td>
                <td style="color:var(--text-muted);"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td onclick="event.stopPropagation()">
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
                <td onclick="event.stopPropagation()">
                  <button class="btn-detail-pesanan" onclick="lihatDetail(<?= $p['id_pesanan'] ?>)">
                    <i class="fa fa-eye"></i> Detail
                  </button>
                  <?php if ($p['status'] !== 'ditolak' && $p['status'] !== 'selesai'): ?>
                  <button class="btn-tolak"
                    onclick="tolakPesanan('<?= $p['kode'] ?>', '<?= htmlspecialchars($p['nama_pembeli']) ?>')">
                    <i class="fa fa-ban"></i> Tolak
                  </button>
                  <?php elseif ($p['status'] === 'ditolak'): ?>
                  <span style="font-size:12px;color:#dc2626;font-weight:600;">
                    <i class="fa fa-ban"></i> Ditolak
                  </span>
                  <?php else: ?>
                  <span style="font-size:12px;color:#16a34a;font-weight:600;">
                    <i class="fa fa-check"></i> Selesai
                  </span>
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

<!-- ── Modal Detail Pesanan ── -->
<div class="modal-overlay" id="modalPesanan" onclick="tutupModal(event)">
  <div class="modal-box">

    <!-- Header -->
    <div class="modal-header">
      <div class="modal-header-left">
        <div>
          <div class="modal-kode" id="mpKode">—</div>
          <div class="modal-tgl" id="mpTgl">—</div>
        </div>
        <span id="mpStatusBadge" style="padding:4px 14px;border-radius:9999px;font-size:12px;font-weight:700;"></span>
      </div>
      <button class="modal-close" onclick="document.getElementById('modalPesanan').classList.remove('open')">
        <i class="fa fa-times"></i>
      </button>
    </div>

    <div class="modal-body">

      <!-- Info Pembeli -->
      <div class="modal-section">
        <div class="modal-section-title"><i class="fa fa-user"></i> Informasi Pembeli</div>
        <div class="modal-row">
          <span class="lbl">Nama</span>
          <span class="val" id="mpNama">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Email</span>
          <span class="val" id="mpEmail">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">No. Telepon</span>
          <span class="val" id="mpTelepon">—</span>
        </div>
      </div>

      <!-- Alamat Pengiriman -->
      <div class="modal-section">
        <div class="modal-section-title"><i class="fa fa-map-marker-alt"></i> Alamat Pengiriman</div>
        <div class="modal-row">
          <span class="lbl">Alamat</span>
          <span class="val" id="mpAlamat">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Kota</span>
          <span class="val" id="mpKota">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Kode Pos</span>
          <span class="val" id="mpKodePos">—</span>
        </div>
        <div class="modal-row">
          <span class="lbl">Kurir</span>
          <span class="val" id="mpKurir">—</span>
        </div>
      </div>

      <!-- Item Pesanan -->
      <div class="modal-section">
        <div class="modal-section-title"><i class="fa fa-box"></i> Item Pesanan</div>
        <div id="mpItems">—</div>
      </div>

      <!-- Ringkasan Pembayaran -->
      <div class="modal-section">
        <div class="modal-section-title"><i class="fa fa-receipt"></i> Ringkasan Pembayaran</div>
        <div class="bayar-row">
          <span>Metode Pembayaran</span>
          <span id="mpMetode" style="font-weight:600;color:var(--text-dark);">—</span>
        </div>
        <div class="bayar-row">
          <span>Subtotal</span>
          <span id="mpSubtotal">—</span>
        </div>
        <div class="bayar-row">
          <span>Ongkos Kirim</span>
          <span id="mpOngkir">—</span>
        </div>
        <div class="bayar-row">
          <span>Diskon Voucher</span>
          <span id="mpDiskon" style="color:#16a34a;">—</span>
        </div>
        <div class="bayar-row total">
          <span>Total Pembayaran</span>
          <span class="val" id="mpTotal">—</span>
        </div>
      </div>

    </div>

    <!-- Footer Aksi Admin -->
    <div class="modal-footer">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1;">
        <label style="font-size:13px;font-weight:600;color:var(--text-muted);">Ubah Status:</label>
        <select class="modal-status-select" id="mpStatusSelect" onchange="ubahStatusModal()">
          <option value="pending">Pending</option>
          <option value="diproses">Diproses</option>
          <option value="dikemas">Dikemas</option>
          <option value="dikirim">Dikirim</option>
          <option value="selesai">Selesai</option>
          <option value="ditolak">Ditolak</option>
        </select>
        <button class="btn-modal-tolak" id="mpBtnTolak" onclick="tolakDariModal()">
          <i class="fa fa-ban"></i> Tolak Pesanan
        </button>
      </div>
      <button class="btn-modal-close" onclick="document.getElementById('modalPesanan').classList.remove('open')">
        Tutup
      </button>
    </div>

  </div>
</div>

<?php
// Siapkan data semua pesanan + detail sebagai JSON untuk JS
$pesanan_json = [];
foreach ($pesanan_list as $p) {
    $items = $detail_map[$p['id_pesanan']] ?? [];
    $pesanan_json[$p['id_pesanan']] = [
        'id'           => $p['id_pesanan'],
        'kode'         => $p['kode'],
        'status'       => $p['status'],
        'nama_pembeli' => $p['nama_pembeli'],
        'email'        => $p['email'],
        'telepon'      => $p['telepon'],
        'alamat'       => $p['alamat'],
        'kota'         => $p['kota'],
        'kode_pos'     => $p['kode_pos'],
        'kurir'        => $p['kurir'],
        'pembayaran'   => $p['pembayaran'],
        'subtotal'     => (float)$p['subtotal'],
        'ongkir'       => (float)$p['ongkir'],
        'diskon'       => (float)$p['diskon'],
        'total'        => (float)$p['total'],
        'created_at'   => $p['created_at'],
        'items'        => array_map(fn($it) => [
            'nama'    => $it['nama'],
            'varian'  => $it['varian'] ?? '',
            'jumlah'  => (int)$it['jumlah'],
            'harga'   => (float)$it['harga'],
            'gambar'  => $it['gambar'] ?? '',
        ], $items),
    ];
}
?>

<script>
const pesananData = <?= json_encode($pesanan_json, JSON_UNESCAPED_UNICODE) ?>;
let modalKodeAktif = '';

const statusCfg = {
  pending:  { label:'Pending',  bg:'#fef3c7', color:'#92400e' },
  diproses: { label:'Diproses', bg:'#dbeafe', color:'#1d4ed8' },
  dikemas:  { label:'Dikemas',  bg:'#ede9fe', color:'#6d28d9' },
  dikirim:  { label:'Dikirim',  bg:'#cffafe', color:'#0e7490' },
  selesai:  { label:'Selesai',  bg:'#dcfce7', color:'#15803d' },
  ditolak:  { label:'Ditolak',  bg:'#fee2e2', color:'#dc2626' },
};

function rupiah(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

function lihatDetail(id) {
  const p = pesananData[id];
  if (!p) return;
  modalKodeAktif = p.kode;

  const tgl = new Date(p.created_at);
  const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  const tglStr = tgl.getDate() + ' ' + bulan[tgl.getMonth()] + ' ' + tgl.getFullYear();

  // Header
  document.getElementById('mpKode').textContent = p.kode;
  document.getElementById('mpTgl').textContent  = tglStr;
  const sc = statusCfg[p.status] || statusCfg.pending;
  const badge = document.getElementById('mpStatusBadge');
  badge.textContent = sc.label;
  badge.style.background = sc.bg;
  badge.style.color      = sc.color;

  // Pembeli
  document.getElementById('mpNama').textContent    = p.nama_pembeli;
  document.getElementById('mpEmail').textContent   = p.email || '—';
  document.getElementById('mpTelepon').textContent = p.telepon || '—';

  // Alamat
  document.getElementById('mpAlamat').textContent  = p.alamat  || '—';
  document.getElementById('mpKota').textContent    = p.kota    || '—';
  document.getElementById('mpKodePos').textContent = p.kode_pos || '—';
  document.getElementById('mpKurir').textContent   = p.kurir   || '—';

  // Items
  const itemsEl = document.getElementById('mpItems');
  if (p.items.length === 0) {
    itemsEl.innerHTML = '<span style="color:var(--text-muted);font-size:13px;">Tidak ada item</span>';
  } else {
    itemsEl.innerHTML = p.items.map(it => `
      <div class="item-row">
        <div style="display:flex;align-items:center;gap:10px;flex:1;">
          ${it.gambar
            ? `<img src="../uploads/${it.gambar}" class="item-img" onerror="this.style.display='none'">`
            : `<div class="item-img-placeholder"><i class="fa fa-image" style="color:#d1d5db;font-size:16px;"></i></div>`
          }
          <div>
            <div style="font-weight:600;font-size:13px;">${it.nama}</div>
            ${it.varian ? `<div style="font-size:11px;color:var(--text-muted);">Varian: ${it.varian}</div>` : ''}
            <div style="font-size:11px;color:var(--text-muted);">${it.jumlah} x ${rupiah(it.harga)}</div>
          </div>
        </div>
        <div style="font-weight:700;font-size:13px;color:#dc2626;flex-shrink:0;">${rupiah(it.jumlah * it.harga)}</div>
      </div>
    `).join('');
  }

  // Pembayaran
  document.getElementById('mpMetode').textContent   = p.pembayaran;
  document.getElementById('mpSubtotal').textContent = rupiah(p.subtotal);
  document.getElementById('mpOngkir').textContent   = rupiah(p.ongkir);
  document.getElementById('mpDiskon').textContent   = '-' + rupiah(p.diskon);
  document.getElementById('mpTotal').textContent    = rupiah(p.total);

  // Footer aksi
  const sel = document.getElementById('mpStatusSelect');
  sel.value = p.status;
  applySelectStyle(sel, p.status);

  const btnTolak = document.getElementById('mpBtnTolak');
  btnTolak.style.display = (p.status === 'ditolak' || p.status === 'selesai') ? 'none' : 'inline-flex';

  document.getElementById('modalPesanan').classList.add('open');
}

function applySelectStyle(sel, status) {
  const sc = statusCfg[status] || statusCfg.pending;
  sel.style.background  = sc.bg;
  sel.style.color       = sc.color;
  sel.style.borderColor = sc.bg;
}

function ubahStatusModal() {
  const sel    = document.getElementById('mpStatusSelect');
  const status = sel.value;
  applySelectStyle(sel, status);

  // Update badge di modal
  const sc = statusCfg[status];
  const badge = document.getElementById('mpStatusBadge');
  badge.textContent      = sc.label;
  badge.style.background = sc.bg;
  badge.style.color      = sc.color;

  // Sembunyikan tombol tolak jika sudah selesai/ditolak
  document.getElementById('mpBtnTolak').style.display =
    (status === 'ditolak' || status === 'selesai') ? 'none' : 'inline-flex';

  // Simpan ke DB
  fetch('riwayat-pesanan.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'kode=' + encodeURIComponent(modalKodeAktif) + '&status=' + encodeURIComponent(status)
  })
  .then(r => r.json())
  .then(() => {
    // Sync dropdown di tabel
    syncTabelStatus(modalKodeAktif, status);
    showToast('✅ Status diperbarui: ' + sc.label);
  });
}

function tolakDariModal() {
  const alasan = prompt(
    `Tolak pesanan ${modalKodeAktif}?\n\nMasukkan alasan penolakan (opsional):`,
    'Bukti transfer tidak valid'
  );
  if (alasan === null) return;

  fetch('../ajax/tolak-pesanan.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ kode: modalKodeAktif, alasan: alasan })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Update modal
      const badge = document.getElementById('mpStatusBadge');
      badge.textContent = 'Ditolak'; badge.style.background = '#fee2e2'; badge.style.color = '#dc2626';
      const sel = document.getElementById('mpStatusSelect');
      sel.value = 'ditolak'; applySelectStyle(sel, 'ditolak');
      document.getElementById('mpBtnTolak').style.display = 'none';
      // Sync tabel
      syncTabelStatus(modalKodeAktif, 'ditolak');
      showToast('✅ Pesanan ' + modalKodeAktif + ' berhasil ditolak.');
    } else {
      showToast('❌ ' + (data.message || 'Gagal menolak pesanan'));
    }
  })
  .catch(() => showToast('❌ Terjadi kesalahan, coba lagi.'));
}

function syncTabelStatus(kode, status) {
  const sc = statusCfg[status] || statusCfg.pending;
  document.querySelectorAll('#pesananTable tbody tr').forEach(row => {
    const kodeCell = row.querySelector('td:nth-child(2)');
    if (kodeCell && kodeCell.textContent.trim() === kode) {
      row.dataset.status = status;
      const sel = row.querySelector('.status-select');
      if (sel) { sel.value = status; sel.style.background = sc.bg; sel.style.color = sc.color; sel.style.borderColor = sc.bg; }
      const btnTolak = row.querySelector('.btn-tolak');
      if (btnTolak && (status === 'ditolak' || status === 'selesai')) {
        btnTolak.outerHTML = status === 'ditolak'
          ? '<span style="font-size:12px;color:#dc2626;font-weight:600;"><i class="fa fa-ban"></i> Ditolak</span>'
          : '<span style="font-size:12px;color:#16a34a;font-weight:600;"><i class="fa fa-check"></i> Selesai</span>';
      }
    }
  });
}

function tutupModal(e) {
  if (e.target === document.getElementById('modalPesanan'))
    document.getElementById('modalPesanan').classList.remove('open');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.getElementById('modalPesanan').classList.remove('open');
});

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
  const s = statusCfg[select.value];
  select.style.background  = s.bg;
  select.style.color       = s.color;
  select.style.borderColor = s.bg;
  select.closest('tr').dataset.status = select.value;
  fetch('riwayat-pesanan.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'kode=' + encodeURIComponent(kode) + '&status=' + encodeURIComponent(select.value)
  }).then(() => showToast('✅ Status pesanan ' + kode + ' diperbarui!'));
}

function tolakPesanan(kode, nama) {
  const alasan = prompt(
    `Tolak pesanan ${kode} dari ${nama}?\n\nMasukkan alasan penolakan (opsional):`,
    'Bukti transfer tidak valid'
  );
  if (alasan === null) return;
  fetch('../ajax/tolak-pesanan.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ kode: kode, alasan: alasan })
  })
  .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
  .then(data => {
    if (data.success) {
      syncTabelStatus(kode, 'ditolak');
      showToast('✅ Pesanan ' + kode + ' berhasil ditolak.');
    } else {
      showToast('❌ Gagal: ' + (data.message || 'Terjadi kesalahan'));
    }
  })
  .catch(err => showToast('❌ Error: ' + err.message));
}

function showToast(msg) {
  const t = document.createElement('div');
  t.innerHTML = msg;
  t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
    background:#1a1a1a;color:white;padding:12px 24px;border-radius:9999px;
    font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.3)`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2500);
}
</script>
</body>
</html>
