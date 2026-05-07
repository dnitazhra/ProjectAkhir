<?php
session_start();
include '../koneksi.php';

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') die("Login dulu sebagai admin");

$log = [];
$log[] = "__DIR__          = " . __DIR__;
$log[] = "dirname(__DIR__) = " . dirname(__DIR__);
$log[] = "uploads path     = " . dirname(__DIR__) . "/uploads/";
$log[] = "uploads writable = " . (is_writable(dirname(__DIR__) . '/uploads/') ? 'YA' : 'TIDAK');

// Ambil semua produk
$produk_list = [];
$res = mysqli_query($conn, "SELECT id_produk, nama, gambar FROM produk ORDER BY id_produk");
while ($row = mysqli_fetch_assoc($res)) $produk_list[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log[] = "\n--- POST DITERIMA ---";
    $log[] = "id_produk = " . ($_POST['id_produk'] ?? 'tidak ada');
    $log[] = "FILES keys = " . (empty($_FILES) ? 'KOSONG!' : implode(', ', array_keys($_FILES)));

    if (!empty($_FILES['gambar'])) {
        $f = $_FILES['gambar'];
        $log[] = "gambar.name     = " . $f['name'];
        $log[] = "gambar.size     = " . $f['size'];
        $log[] = "gambar.error    = " . $f['error'];
        $log[] = "gambar.tmp_name = " . $f['tmp_name'];
        $log[] = "tmp_name exists = " . (file_exists($f['tmp_name']) ? 'YA' : 'TIDAK');

        if ($f['error'] === 0 && !empty($f['tmp_name'])) {
            $ext     = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                $upload_dir = dirname(__DIR__) . '/uploads/';
                $nama_file  = uniqid('img_') . '.' . $ext;
                $target     = $upload_dir . $nama_file;
                $log[] = "target = " . $target;
                $ok = move_uploaded_file($f['tmp_name'], $target);
                $log[] = "move_uploaded_file = " . ($ok ? 'BERHASIL → ' . $nama_file : 'GAGAL');

                if ($ok) {
                    // Update DB
                    $id = (int)$_POST['id_produk'];
                    $stmt = mysqli_prepare($conn, "UPDATE produk SET gambar=? WHERE id_produk=?");
                    mysqli_stmt_bind_param($stmt, 'si', $nama_file, $id);
                    $db_ok = mysqli_stmt_execute($stmt);
                    $affected = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);
                    $log[] = "UPDATE DB = " . ($db_ok ? 'OK' : 'GAGAL');
                    $log[] = "Rows affected = " . $affected;
                }
            } else {
                $log[] = "EKSTENSI TIDAK DIIZINKAN: " . $ext;
            }
        }
    } else {
        $log[] = "FILES['gambar'] TIDAK ADA — enctype mungkin salah di form";
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Test Edit Gambar</title>
<style>
body{font-family:monospace;background:#1e1e2e;color:#cdd6f4;padding:20px}
pre{background:#181825;padding:16px;border-radius:8px;line-height:1.6}
.ok{color:#a6e3a1} .err{color:#f38ba8}
form{background:#313244;padding:20px;border-radius:8px;margin-bottom:20px}
select,input,button{margin:6px 0;display:block;padding:8px;border-radius:6px;border:none;width:300px}
button{background:#cba6f7;cursor:pointer;font-weight:bold;width:auto}
</style>
</head>
<body>
<h2>🔧 Test Edit Gambar Produk</h2>
<form method="POST" enctype="multipart/form-data">
  <label>Pilih Produk:</label>
  <select name="id_produk">
    <?php foreach ($produk_list as $p): ?>
    <option value="<?= $p['id_produk'] ?>"><?= htmlspecialchars($p['nama']) ?> [gambar: <?= $p['gambar'] ?: 'kosong' ?>]</option>
    <?php endforeach; ?>
  </select>
  <label>Pilih Gambar Baru:</label>
  <input type="file" name="gambar" accept="image/*" required>
  <button type="submit">Upload & Update DB</button>
</form>
<pre><?php
foreach ($log as $line) {
    if (strpos($line, 'BERHASIL') !== false || strpos($line, 'YA') !== false || strpos($line, 'OK') !== false) {
        echo '<span class="ok">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, 'GAGAL') !== false || strpos($line, 'TIDAK') !== false || strpos($line, 'KOSONG') !== false) {
        echo '<span class="err">' . htmlspecialchars($line) . '</span>' . "\n";
    } else {
        echo htmlspecialchars($line) . "\n";
    }
}
?></pre>
<a href="dashboard.php" style="color:#89b4fa">← Kembali ke Dashboard</a>
</body>
</html>
