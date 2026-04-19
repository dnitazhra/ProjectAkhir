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
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirm'] ?? '';

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
            $stmt2 = mysqli_prepare($conn, "INSERT INTO user (nama, email, password, role) VALUES (?, ?, ?, 'pembeli')");
            mysqli_stmt_bind_param($stmt2, 'sss', $nama, $email, $pass_hash);
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
  <title>Daftar Akun - Happy Snack</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <h2>Happy Snack</h2>
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
