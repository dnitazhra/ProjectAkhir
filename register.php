<?php
session_start();
include 'koneksi.php';

// Kalau sudah login, redirect
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error   = '';
$success = '';

if (isset($_POST['daftar'])) {
    $nama      = trim($_POST['nama'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $konfirm   = $_POST['konfirm'] ?? '';
    $telepon   = trim($_POST['telepon'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');
    $kode_pos  = trim($_POST['kode_pos'] ?? '');
    $kecamatan = trim($_POST['kecamatan'] ?? '');
    $kabupaten = trim($_POST['kabupaten'] ?? '');
    $provinsi  = trim($_POST['provinsi'] ?? '');

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        // Cek email duplikat
        $stmt = mysqli_prepare($conn, "SELECT id_user FROM user WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = mysqli_prepare($conn,
                "INSERT INTO user (nama, email, password, telepon, alamat, kecamatan, kabupaten, provinsi, kode_pos, role)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pembeli')");
            mysqli_stmt_bind_param($stmt2, 'sssssssss',
                $nama, $email, $pass_hash, $telepon, $alamat, $kecamatan, $kabupaten, $provinsi, $kode_pos);
            if (mysqli_stmt_execute($stmt2)) {
                $success = 'Akun berhasil dibuat! Silakan masuk.';
            } else {
                $error = 'Terjadi kesalahan. Coba lagi.';
            }
            mysqli_stmt_close($stmt2);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun - lavo.id</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <h2>lavo.id</h2>
    </div>

    <!-- Header -->
    <div class="auth-header">
      <div class="label">Mulai Perjalananmu</div>
      <h1>Buat Akun<br>Baru</h1>
    </div>

    <!-- Alert -->
    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        <br><a href="login.php" style="font-weight:700;">Klik di sini untuk masuk →</a>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="">

      <div class="form-group">
        <label for="nama">Nama Lengkap</label>
        <input
          type="text"
          id="nama"
          name="nama"
          class="form-control"
          placeholder="Masukkan nama lengkap"
          value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
          required
        >
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control"
          placeholder="contoh@email.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required
        >
      </div>

      <div class="form-group">
        <label for="password">Kata Sandi</label>
        <div class="input-wrapper">
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Min. 8 karakter"
            required
          >
          <span class="input-icon" onclick="togglePassword('password', this)">
            <i class="fa fa-eye-slash"></i>
          </span>
        </div>
      </div>

      <div class="form-group">
        <label for="konfirm">Konfirmasi Kata Sandi</label>
        <div class="input-wrapper">
          <input
            type="password"
            id="konfirm"
            name="konfirm"
            class="form-control"
            placeholder="Ulangi kata sandi"
            required
          >
          <span class="input-icon" onclick="togglePassword('konfirm', this)">
            <i class="fa fa-eye-slash"></i>
          </span>
        </div>
      </div>

      <!-- Informasi Pengiriman -->
      <div style="margin:16px 0 8px;padding-top:16px;border-top:1px solid var(--border);">
        <div style="font-size:13px;font-weight:700;color:var(--text-dark);margin-bottom:4px;">
          <i class="fa fa-map-marker-alt" style="color:var(--primary);margin-right:6px;"></i>
          Informasi Pengiriman
        </div>
        <div style="font-size:12px;color:var(--text-muted);">
          Akan digunakan sebagai alamat default saat checkout
        </div>
      </div>

      <div class="form-group">
        <label for="telepon">Nomor Telepon</label>
        <input type="tel" id="telepon" name="telepon" class="form-control"
               placeholder="0812 XXXX XXXX"
               value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="alamat">Alamat Rumah Lengkap</label>
        <input type="text" id="alamat" name="alamat" class="form-control"
               placeholder="Nama jalan, nomor rumah, RT/RW..."
               value="<?= htmlspecialchars($_POST['alamat'] ?? '') ?>">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group">
          <label for="kecamatan">Kecamatan</label>
          <input type="text" id="kecamatan" name="kecamatan" class="form-control"
                 placeholder="Masukkan kecamatan"
                 value="<?= htmlspecialchars($_POST['kecamatan'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="kabupaten">Kabupaten / Kota</label>
          <input type="text" id="kabupaten" name="kabupaten" class="form-control"
                 placeholder="Masukkan kabupaten/kota"
                 value="<?= htmlspecialchars($_POST['kabupaten'] ?? '') ?>">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group">
          <label for="provinsi">Provinsi</label>
          <select id="provinsi" name="provinsi" class="form-control">
          <option value="">-- Pilih Provinsi --</option>
          <?php
          $provinsi_list = [
            'Aceh','Sumatera Utara','Sumatera Barat','Riau','Kepulauan Riau',
            'Jambi','Sumatera Selatan','Kepulauan Bangka Belitung','Bengkulu','Lampung',
            'DKI Jakarta','Jawa Barat','Banten','Jawa Tengah','DI Yogyakarta','Jawa Timur',
            'Bali','Nusa Tenggara Barat','Nusa Tenggara Timur',
            'Kalimantan Barat','Kalimantan Tengah','Kalimantan Selatan','Kalimantan Timur','Kalimantan Utara',
            'Sulawesi Utara','Gorontalo','Sulawesi Tengah','Sulawesi Barat','Sulawesi Selatan','Sulawesi Tenggara',
            'Maluku','Maluku Utara','Papua Barat','Papua','Papua Selatan','Papua Tengah','Papua Pegunungan'
          ];
          $selected_prov = $_POST['provinsi'] ?? '';
          foreach ($provinsi_list as $prov):
          ?>
          <option value="<?= $prov ?>" <?= $selected_prov === $prov ? 'selected' : '' ?>>
            <?= $prov ?>
          </option>
          <?php endforeach; ?>
        </select>
        </div>
        <div class="form-group">
          <label for="kode_pos">Kode Pos</label>
          <input type="text" id="kode_pos" name="kode_pos" class="form-control"
                 placeholder="12345" maxlength="5"
                 value="<?= htmlspecialchars($_POST['kode_pos'] ?? '') ?>">
        </div>
      </div>

      <button type="submit" name="daftar" class="btn btn-primary btn-full" style="margin-top:8px;">
        Daftar Sekarang
      </button>

    </form>

    <div class="auth-footer">
      Sudah punya akun? <a href="login.php">Masuk</a>
    </div>

  </div>
</div>

<script>
function togglePassword(fieldId, icon) {
  const input = document.getElementById(fieldId);
  const i = icon.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    i.className = 'fa fa-eye';
  } else {
    input.type = 'password';
    i.className = 'fa fa-eye-slash';
  }
}
</script>

</body>
</html>
