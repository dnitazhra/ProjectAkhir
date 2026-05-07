<?php
session_start();
include 'koneksi.php';

// Kalau sudah login, redirect
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = 'Email atau kata sandi salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk - lavo.id</title>
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
      <div class="label">Selamat Datang Kembali</div>
      <h1>Masuk ke<br>Akun Anda</h1>
      <p>Masuk untuk melanjutkan pesanan Anda</p>
    </div>

    <!-- Alert Error -->
    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="">

      <div class="form-group">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-control"
          placeholder="nama@email.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required
        >
      </div>

      <div class="form-group">
        <div class="label-row">
          <label for="password">Kata Sandi</label>
          <a href="#">Lupa Kata Sandi?</a>
        </div>
        <div class="input-wrapper">
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="••••••••"
            required
          >
          <span class="input-icon" onclick="togglePassword('password', this)">
            <i class="fa fa-eye-slash"></i>
          </span>
        </div>
      </div>

      <button type="submit" name="login" class="btn btn-primary btn-full" style="margin-top:8px;">
        Masuk Sekarang
      </button>

    </form>

    <div class="auth-footer">
      Belum punya akun? <a href="register.php">Daftar Sekarang</a>
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
