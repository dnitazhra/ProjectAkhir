<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header("Location: login.php"); exit; }

$id_produk     = (int)($_GET['id_produk'] ?? 0);
$id_pesanan    = (int)($_GET['id_pesanan'] ?? 0);
$produk        = $id_produk ? getProdukById($conn, $id_produk) : null;
$produk_nama   = $produk['nama'] ?? ($_GET['produk'] ?? 'Produk');
$produk_varian = $_GET['varian'] ?? '';
$tgl_pesan     = date('d M Y');

// Proses submit ulasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim'])) {
    $rating   = (int)($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');
    $foto     = uploadGambar($_FILES['foto'] ?? []);

    if ($rating >= 1 && $rating <= 5 && strlen($komentar) >= 5) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO ulasan (id_user, id_produk, id_pesanan, rating, komentar, foto)
             VALUES (?, ?, ?, ?, ?, ?)");
        $id_pes = $id_pesanan ?: null;
        mysqli_stmt_bind_param($stmt, 'iiiiss',
            $user['id_user'], $id_produk, $id_pes, $rating, $komentar, $foto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: ulasan-berhasil.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tulis Ulasan - Happy Snack</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .ulasan-page {
      max-width: 560px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    .ulasan-page-title {
      font-size: 22px;
      font-weight: 700;
      font-family: 'Times New Roman', serif;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .ulasan-page-title i { color: var(--primary); }

    /* Produk info */
    .produk-info-box {
      display: flex;
      gap: 14px;
      align-items: center;
      background: var(--bg-light);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 14px 16px;
      margin-bottom: 24px;
    }

    .produk-info-img {
      width: 60px;
      height: 60px;
      border-radius: var(--radius-sm);
      background: var(--bg-card);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 24px;
      color: var(--border);
    }

    .produk-info-detail .tgl { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
    .produk-info-detail .nama { font-size: 15px; font-weight: 700; color: var(--text-dark); }
    .produk-info-detail .varian { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    /* Rating bintang */
    .rating-section {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 20px;
      margin-bottom: 16px;
      text-align: center;
    }

    .rating-section h4 {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 4px;
    }

    .rating-section p {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 16px;
    }

    .star-input {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 12px;
      flex-direction: row-reverse;
    }

    .star-input input { display: none; }

    .star-input label {
      font-size: 36px;
      color: var(--border);
      cursor: pointer;
      transition: color 0.15s, transform 0.15s;
    }

    .star-input label:hover,
    .star-input label:hover ~ label,
    .star-input input:checked ~ label {
      color: #f59e0b;
    }

    .star-input label:hover { transform: scale(1.15); }

    .rating-text {
      font-size: 14px;
      font-weight: 700;
      color: var(--primary);
      min-height: 20px;
    }

    /* Foto */
    .foto-section {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 20px;
      margin-bottom: 16px;
    }

    .foto-section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .foto-section-header h4 { font-size: 14px; font-weight: 700; color: var(--text-dark); }
    .foto-section-header span { font-size: 12px; color: var(--text-muted); }

    .foto-list {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .foto-add-btn {
      width: 72px;
      height: 72px;
      border: 2px dashed var(--border);
      border-radius: var(--radius-sm);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      cursor: pointer;
      transition: border-color var(--transition), background var(--transition);
      font-size: 11px;
      color: var(--text-muted);
    }

    .foto-add-btn:hover { border-color: var(--primary); background: #fff5f0; color: var(--primary); }
    .foto-add-btn i { font-size: 20px; }
    .foto-add-btn input { display: none; }

    .foto-preview {
      width: 72px;
      height: 72px;
      border-radius: var(--radius-sm);
      object-fit: cover;
      border: 1px solid var(--border);
    }

    /* Komentar */
    .komentar-section {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 20px;
      margin-bottom: 24px;
    }

    .komentar-section h4 {
      font-size: 14px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 12px;
    }

    .komentar-section textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 14px;
      font-family: inherit;
      resize: vertical;
      min-height: 100px;
      outline: none;
      transition: border-color var(--transition);
      color: var(--text-dark);
    }

    .komentar-section textarea:focus { border-color: var(--primary); }

    .char-count {
      text-align: right;
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 6px;
    }

    .btn-kirim {
      width: 100%;
      background: var(--primary);
      color: white;
      border-radius: var(--radius-lg);
      padding: 14px;
      font-size: 15px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background var(--transition);
      border: none;
      cursor: pointer;
    }

    .btn-kirim:hover { background: var(--primary-dark); }

    @media (max-width: 480px) {
      .ulasan-page { padding: 16px; }
      .star-input label { font-size: 30px; }
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
    <a href="profil.php"><i class="fa fa-user"></i> Profil</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Keluar</a>
  </div>
</nav>

<header class="navbar">
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="Logo" onerror="this.style.display='none'">
    <h2>Happy Snack</h2>
  </div>
  <div class="navbar-search" style="pointer-events:none;opacity:0.5;">
    <i class="fa fa-search" style="color:var(--text-muted)"></i>
    <input type="text" placeholder="Cari produk..." disabled>
  </div>
  <div class="navbar-actions">
    <a href="profil.php" class="nav-btn"><i class="fa fa-user"></i></a>
    <a href="keranjang.php" class="nav-btn"><i class="fa fa-shopping-cart"></i></a>
    <button class="nav-btn btn-menu" onclick="openSidebar()"><i class="fa fa-bars"></i></button>
  </div>
</header>

<div class="ulasan-page">

  <button class="btn-back" onclick="history.back()">
    <i class="fa fa-arrow-left"></i> Kembali
  </button>
  <h1 class="ulasan-page-title"><i class="fa fa-star"></i> Tulis Ulasan</h1>

  <!-- Info Produk -->
  <div class="produk-info-box">
    <div class="produk-info-img"><i class="fa fa-image"></i></div>
    <div class="produk-info-detail">
      <div class="tgl">Dipesan pada <?= $tgl_pesan ?></div>
      <div class="nama"><?= htmlspecialchars($produk_nama) ?></div>
      <div class="varian">Varian: <?= htmlspecialchars($produk_varian) ?></div>
    </div>
  </div>

  <form action="ulasan.php?id_produk=<?= $id_produk ?>&id_pesanan=<?= $id_pesanan ?>" method="POST" enctype="multipart/form-data" onsubmit="return validasiUlasan()">

    <!-- Rating -->
    <div class="rating-section">
      <h4>Bagaimana kualitas produk?</h4>
      <p>Berikan rating untuk membantu pembeli lain</p>
      <div class="star-input">
        <input type="radio" name="rating" id="s5" value="5">
        <label for="s5" title="Sangat Baik">★</label>
        <input type="radio" name="rating" id="s4" value="4">
        <label for="s4" title="Baik">★</label>
        <input type="radio" name="rating" id="s3" value="3">
        <label for="s3" title="Cukup">★</label>
        <input type="radio" name="rating" id="s2" value="2">
        <label for="s2" title="Kurang">★</label>
        <input type="radio" name="rating" id="s1" value="1">
        <label for="s1" title="Sangat Kurang">★</label>
      </div>
      <div class="rating-text" id="ratingText">Pilih rating</div>
    </div>

    <!-- Foto -->
    <div class="foto-section">
      <div class="foto-section-header">
        <h4><i class="fa fa-camera" style="color:var(--primary);margin-right:6px;"></i>Tambahkan Foto</h4>
        <span>Maks. 3 Foto</span>
      </div>
      <div class="foto-list" id="fotoList">
        <label class="foto-add-btn" id="fotoAddBtn">
          <i class="fa fa-plus"></i>
          <span>Tambah</span>
          <input type="file" accept="image/*" multiple onchange="previewFoto(this)">
        </label>
      </div>
    </div>

    <!-- Komentar -->
    <div class="komentar-section">
      <h4><i class="fa fa-comment" style="color:var(--primary);margin-right:6px;"></i>Ulasan Anda</h4>
      <textarea name="komentar" id="komentar" maxlength="500"
                placeholder="Ceritakan pengalaman Anda menggunakan produk ini..."
                oninput="hitungChar(this)"></textarea>
      <div class="char-count"><span id="charCount">0</span> / 500</div>
    </div>

    <button type="submit" name="kirim" class="btn-kirim">
      <i class="fa fa-paper-plane"></i> Kirim Ulasan
    </button>

  </form>
</div>

<?php include 'includes/footer.php'; ?>

<script>
const ratingLabels = ['','Sangat Kurang','Kurang','Cukup','Baik','Sangat Baik'];

document.querySelectorAll('.star-input input').forEach(input => {
  input.addEventListener('change', () => {
    document.getElementById('ratingText').textContent = ratingLabels[input.value];
  });
});

function hitungChar(el) {
  document.getElementById('charCount').textContent = el.value.length;
}

let fotoCount = 0;
function previewFoto(input) {
  const files = Array.from(input.files).slice(0, 3 - fotoCount);
  files.forEach(file => {
    if (fotoCount >= 3) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'foto-preview';
      document.getElementById('fotoList').insertBefore(img, document.getElementById('fotoAddBtn'));
      fotoCount++;
      if (fotoCount >= 3) document.getElementById('fotoAddBtn').style.display = 'none';
    };
    reader.readAsDataURL(file);
  });
}

function validasiUlasan() {
  const rating = document.querySelector('input[name="rating"]:checked');
  if (!rating) { alert('Pilih rating terlebih dahulu.'); return false; }
  const komentar = document.getElementById('komentar').value.trim();
  if (komentar.length < 5) { alert('Tulis ulasan minimal 5 karakter.'); return false; }
  return true;
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
