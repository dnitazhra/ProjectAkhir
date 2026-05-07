<?php
session_start();
include '../koneksi.php';
include '../includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') { header("Location: ../login.php"); exit; }

$success = '';
$error   = '';

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');

    if ($nama && $email) {
        $stmt = mysqli_prepare($conn,
            "UPDATE user SET nama=?, email=?, telepon=? WHERE id_user=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $nama, $email, $telepon, $user['id_user']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Refresh session
        $stmt2 = mysqli_prepare($conn, "SELECT * FROM user WHERE id_user=?");
        mysqli_stmt_bind_param($stmt2, 'i', $user['id_user']);
        mysqli_stmt_execute($stmt2);
        $res = mysqli_stmt_get_result($stmt2);
        $_SESSION['user'] = mysqli_fetch_assoc($res);
        $user = $_SESSION['user'];
        mysqli_stmt_close($stmt2);
        $success = 'Profil berhasil diperbarui.';
    } else {
        $error = 'Nama dan email wajib diisi.';
    }
}

// Ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $lama   = $_POST['pass_lama'] ?? '';
    $baru   = $_POST['pass_baru'] ?? '';
    $konfirm= $_POST['pass_konfirm'] ?? '';

    if (!password_verify($lama, $user['password'])) {
        $error = 'Kata sandi lama tidak sesuai.';
    } elseif (strlen($baru) < 8) {
        $error = 'Kata sandi baru minimal 8 karakter.';
    } elseif ($baru !== $konfirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE user SET password=? WHERE id_user=?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $user['id_user']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = 'Kata sandi berhasil diubah.';
    }
}

// Stats admin
$stats = getStatsAdmin($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Admin - lavo.id</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width:240px; background:#1e1e2e; color:white; flex-shrink:0; display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:50; transition:left 0.3s; }
    .admin-sidebar-logo { padding:20px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; align-items:center; gap:10px; }
    .admin-sidebar-logo span { font-size:18px; font-weight:700; color:var(--accent); font-family:'Times New Roman',serif; }
    .admin-sidebar-logo small { display:block; font-size:10px; color:rgba(255,255,255,0.5); }
    .admin-nav { padding:12px 0; flex:1; }
    .admin-nav-section { padding:8px 16px 4px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1px; }
    .admin-nav a { display:flex; align-items:center; gap:10px; padding:11px 20px; font-size:14px; color:rgba(255,255,255,0.7); transition:all 0.2s; border-left:3px solid transparent; text-decoration:none; }
    .admin-nav a:hover { background:rgba(255,255,255,0.07); color:white; }
    .admin-nav a.active { background:rgba(171,53,0,0.3); color:white; border-left-color:var(--primary); }
    .admin-nav a i { width:18px; text-align:center; }
    .admin-sidebar-footer { padding:16px 20px; border-top:1px solid rgba(255,255,255,0.1); }
    .admin-sidebar-footer a { display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(255,255,255,0.6); transition:color 0.2s; text-decoration:none; }
    .admin-sidebar-footer a:hover { color:white; }
    .admin-main { margin-left:240px; flex:1; background:#f4f6f9; min-height:100vh; }
    .admin-topbar { background:white; border-bottom:1px solid var(--border); padding:0 24px; height:60px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:40; }
    .admin-topbar h1 { font-size:18px; font-weight:700; }
    .admin-avatar { width:34px; height:34px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; }
    .admin-user { display:flex; align-items:center; gap:8px; font-size:14px; font-weight:600; }
    .btn-menu-admin { display:none; background:none; border:none; font-size:20px; cursor:pointer; }
    .admin-content { padding:24px; }

    /* Profil Layout */
    .profil-admin-grid {
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 24px;
      align-items: flex-start;
    }

    /* Card kiri */
    .admin-profil-card {
      background: white;
      border-radius: var(--radius-md);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }

    .admin-profil-header {
      background: linear-gradient(135deg, #1e1e2e, #2d2d44);
      padding: 32px 20px;
      text-align: center;
    }

    .admin-profil-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      font-size: 32px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 14px;
      border: 3px solid rgba(255,255,255,0.2);
    }

    .admin-profil-nama {
      font-size: 18px;
      font-weight: 700;
      color: white;
      margin-bottom: 4px;
    }

    .admin-profil-role {
      display: inline-block;
      background: var(--primary);
      color: white;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 12px;
      border-radius: 9999px;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 6px;
    }

    .admin-profil-email {
      font-size: 12px;
      color: rgba(255,255,255,0.55);
    }

    /* Stats mini */
    .admin-stats-mini {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1px;
      background: var(--border);
    }

    .admin-stat-mini {
      background: white;
      padding: 16px;
      text-align: center;
    }

    .admin-stat-mini .num {
      font-size: 20px;
      font-weight: 700;
      color: var(--primary);
    }

    .admin-stat-mini .lbl {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    /* Info list */
    .admin-info-list { padding: 16px 20px; }

    .admin-info-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
    }

    .admin-info-item:last-child { border-bottom: none; }

    .admin-info-item i {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #fff5f0;
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      flex-shrink: 0;
    }

    .admin-info-item .lbl { color: var(--text-muted); font-size: 11px; }
    .admin-info-item .val { font-weight: 600; color: var(--text-dark); }

    /* Card kanan */
    .admin-form-cards { display: flex; flex-direction: column; gap: 20px; }

    .form-card {
      background: white;
      border-radius: var(--radius-md);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }

    .form-card-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      background: var(--bg-light);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-card-header h3 {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .form-card-header i { color: var(--primary); }
    .form-card-body { padding: 20px; }

    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    @media (max-width: 900px) {
      .admin-sidebar { left:-240px; }
      .admin-sidebar.open { left:0; }
      .admin-main { margin-left:0; }
      .btn-menu-admin { display:block; }
      .profil-admin-grid { grid-template-columns: 1fr; }
      .form-grid-2 { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <!-- Sidebar -->
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
      <a href="riwayat-pesanan.php"><i class="fa fa-receipt"></i> Riwayat Pesanan</a>
      <div class="admin-nav-section">Akun</div>
      <a href="profil.php" class="active"><i class="fa fa-user-shield"></i> Profil Admin</a>
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
        <button class="btn-menu-admin" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
          <i class="fa fa-bars"></i>
        </button>
        <a href="dashboard.php" class="btn-back" style="margin-bottom:0;">
          <i class="fa fa-arrow-left"></i> Dashboard
        </a>
        <h1>Profil Admin</h1>
      </div>
      <div class="admin-user">
        <div class="admin-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
        <span><?= htmlspecialchars($user['nama']) ?></span>
      </div>
    </div>

    <div class="admin-content">

      <!-- Alert -->
      <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:20px;">
        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:20px;">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <div class="profil-admin-grid">

        <!-- Kolom Kiri: Info -->
        <div>
          <div class="admin-profil-card">
            <!-- Header -->
            <div class="admin-profil-header">
              <div class="admin-profil-avatar">
                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
              </div>
              <div class="admin-profil-nama"><?= htmlspecialchars($user['nama']) ?></div>
              <div class="admin-profil-role">Administrator</div>
              <div class="admin-profil-email"><?= htmlspecialchars($user['email']) ?></div>
            </div>

            <!-- Stats Mini -->
            <div class="admin-stats-mini">
              <div class="admin-stat-mini">
                <div class="num"><?= $stats['pesanan_baru'] ?></div>
                <div class="lbl">Pesanan Baru</div>
              </div>
              <div class="admin-stat-mini">
                <div class="num"><?= $stats['total_user'] ?></div>
                <div class="lbl">Total User</div>
              </div>
              <div class="admin-stat-mini">
                <div class="num"><?= $stats['produk_aktif'] ?></div>
                <div class="lbl">Produk Aktif</div>
              </div>
              <div class="admin-stat-mini">
                <div class="num">Rp <?= number_format($stats['total_penjualan']/1000000, 1) ?>M</div>
                <div class="lbl">Total Penjualan</div>
              </div>
            </div>

            <!-- Info -->
            <div class="admin-info-list">
              <div class="admin-info-item">
                <i class="fa fa-user"></i>
                <div>
                  <div class="lbl">Nama</div>
                  <div class="val"><?= htmlspecialchars($user['nama']) ?></div>
                </div>
              </div>
              <div class="admin-info-item">
                <i class="fa fa-envelope"></i>
                <div>
                  <div class="lbl">Email</div>
                  <div class="val"><?= htmlspecialchars($user['email']) ?></div>
                </div>
              </div>
              <div class="admin-info-item">
                <i class="fa fa-phone"></i>
                <div>
                  <div class="lbl">Telepon</div>
                  <div class="val"><?= htmlspecialchars($user['telepon'] ?? '-') ?></div>
                </div>
              </div>
              <div class="admin-info-item">
                <i class="fa fa-shield-alt"></i>
                <div>
                  <div class="lbl">Role</div>
                  <div class="val" style="color:var(--primary);">Administrator</div>
                </div>
              </div>
              <div class="admin-info-item">
                <i class="fa fa-calendar"></i>
                <div>
                  <div class="lbl">Bergabung</div>
                  <div class="val"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Form -->
        <div class="admin-form-cards">

          <!-- Edit Profil -->
          <div class="form-card">
            <div class="form-card-header">
              <i class="fa fa-user-edit"></i>
              <h3>Edit Informasi Profil</h3>
            </div>
            <div class="form-card-body">
              <form method="POST">
                <div class="form-grid-2">
                  <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control"
                           value="<?= htmlspecialchars($user['nama']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="tel" name="telepon" class="form-control"
                           value="<?= htmlspecialchars($user['telepon'] ?? '') ?>"
                           placeholder="0812 XXXX XXXX">
                  </div>
                </div>
                <button type="submit" name="update_profil" class="btn btn-primary" style="margin-top:8px;">
                  <i class="fa fa-save"></i> Simpan Perubahan
                </button>
              </form>
            </div>
          </div>

          <!-- Ganti Password -->
          <div class="form-card">
            <div class="form-card-header">
              <i class="fa fa-lock"></i>
              <h3>Ganti Kata Sandi</h3>
            </div>
            <div class="form-card-body">
              <form method="POST">
                <div class="form-group">
                  <label>Kata Sandi Lama</label>
                  <div class="input-wrapper">
                    <input type="password" name="pass_lama" id="pass_lama" class="form-control"
                           placeholder="••••••••" required>
                    <span class="input-icon" onclick="togglePass('pass_lama', this)">
                      <i class="fa fa-eye-slash"></i>
                    </span>
                  </div>
                </div>
                <div class="form-grid-2">
                  <div class="form-group">
                    <label>Kata Sandi Baru</label>
                    <div class="input-wrapper">
                      <input type="password" name="pass_baru" id="pass_baru" class="form-control"
                             placeholder="Min. 8 karakter" required>
                      <span class="input-icon" onclick="togglePass('pass_baru', this)">
                        <i class="fa fa-eye-slash"></i>
                      </span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Konfirmasi Kata Sandi</label>
                    <div class="input-wrapper">
                      <input type="password" name="pass_konfirm" id="pass_konfirm" class="form-control"
                             placeholder="Ulangi kata sandi" required>
                      <span class="input-icon" onclick="togglePass('pass_konfirm', this)">
                        <i class="fa fa-eye-slash"></i>
                      </span>
                    </div>
                  </div>
                </div>
                <button type="submit" name="ganti_password" class="btn btn-primary" style="margin-top:8px;">
                  <i class="fa fa-key"></i> Ubah Kata Sandi
                </button>
              </form>
            </div>
          </div>

          <!-- Aksi Akun -->
          <div class="form-card">
            <div class="form-card-header">
              <i class="fa fa-cog"></i>
              <h3>Aksi Akun</h3>
            </div>
            <div class="form-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
              <a href="dashboard.php"
                 style="background:var(--bg-light);color:var(--text-dark);border:1px solid var(--border);padding:10px 20px;border-radius:var(--radius-lg);font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px;text-decoration:none;transition:all 0.2s;">
                <i class="fa fa-tachometer-alt"></i> Kembali ke Dashboard
              </a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePass(id, icon) {
  const input = document.getElementById(id);
  const i = icon.querySelector('i');
  if (input.type === 'password') { input.type = 'text'; i.className = 'fa fa-eye'; }
  else { input.type = 'password'; i.className = 'fa fa-eye-slash'; }
}
</script>
</body>
</html>
