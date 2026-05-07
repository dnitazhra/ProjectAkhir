<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header("Location: login.php"); exit; }

$cart_count = getCartCount($conn, $user['id_user']);

// Ambil semua ulasan milik user beserta info produk dan pesanan
$stmt = mysqli_prepare($conn,
    "SELECT u.id_ulasan, u.rating, u.komentar, u.foto, u.created_at,
            p.id_produk, p.nama AS nama_produk, p.gambar AS gambar_produk,
            k.nama AS kategori,
            ps.kode AS kode_pesanan, u.id_pesanan
     FROM ulasan u
     JOIN produk p  ON u.id_produk  = p.id_produk
     JOIN kategori k ON p.id_kategori = k.id_kategori
     LEFT JOIN pesanan ps ON u.id_pesanan = ps.id_pesanan
     WHERE u.id_user = ?
     ORDER BY u.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $user['id_user']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$ulasan_list = [];
while ($row = mysqli_fetch_assoc($res)) $ulasan_list[] = $row;
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ulasan Saya - lavo.id</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .ulasan-saya-page {
      max-width: 760px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    .page-title {
      font-size: 22px;
      font-weight: 700;
      font-family: 'Times New Roman', serif;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .page-title i { color: var(--primary); }

    /* Stats bar */
    .ulasan-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-bottom: 24px;
    }

    .ulasan-stat-box {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 16px;
      text-align: center;
    }

    .ulasan-stat-box i { font-size: 20px; color: var(--primary); margin-bottom: 6px; display: block; }
    .ulasan-stat-box .num { font-size: 22px; font-weight: 700; color: var(--text-dark); }
    .ulasan-stat-box .lbl { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    /* Ulasan card */
    .ulasan-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 16px;
      transition: box-shadow 0.2s;
    }

    .ulasan-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }

    .ulasan-card-header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      background: var(--bg-light);
    }

    .ulasan-produk-img {
      width: 52px;
      height: 52px;
      border-radius: 10px;
      object-fit: cover;
      border: 1px solid var(--border);
      flex-shrink: 0;
    }

    .ulasan-produk-img-placeholder {
      width: 52px;
      height: 52px;
      border-radius: 10px;
      background: var(--bg-card);
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--border);
      flex-shrink: 0;
    }

    .ulasan-produk-info { flex: 1; min-width: 0; }

    .ulasan-produk-nama {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .ulasan-produk-meta {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 3px;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .ulasan-produk-meta .kode {
      font-family: monospace;
      font-weight: 600;
      color: var(--primary);
    }

    .ulasan-tgl {
      font-size: 12px;
      color: var(--text-muted);
      flex-shrink: 0;
    }

    .ulasan-card-body { padding: 16px 20px; }

    .ulasan-stars {
      color: #f59e0b;
      font-size: 18px;
      letter-spacing: 2px;
      margin-bottom: 10px;
    }

    .ulasan-stars .empty { color: #e5e7eb; }

    .ulasan-komentar {
      font-size: 14px;
      color: var(--text-dark);
      line-height: 1.7;
      margin-bottom: 12px;
    }

    .ulasan-foto-list {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .ulasan-foto-item {
      width: 80px;
      height: 80px;
      border-radius: 8px;
      object-fit: cover;
      border: 1px solid var(--border);
      cursor: pointer;
      transition: transform 0.2s;
    }

    .ulasan-foto-item:hover { transform: scale(1.05); }

    .ulasan-card-footer {
      padding: 12px 20px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .rating-label {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #fef3c7;
      color: #92400e;
      padding: 4px 12px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 700;
    }

    .btn-lihat-produk {
      font-size: 12px;
      color: var(--primary);
      font-weight: 600;
      padding: 5px 14px;
      border: 1px solid var(--primary);
      border-radius: 9999px;
      transition: all 0.2s;
      text-decoration: none;
    }

    .btn-lihat-produk:hover { background: var(--primary); color: white; }

    /* Kosong */
    .empty-state {
      text-align: center;
      padding: 60px 24px;
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
    }

    .empty-state i { font-size: 48px; color: #e5e7eb; margin-bottom: 16px; display: block; }
    .empty-state h3 { font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
    .empty-state p { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; }

    /* Lightbox foto */
    .lightbox {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.85); z-index: 9999;
      align-items: center; justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox img { max-width: 90vw; max-height: 85vh; border-radius: 8px; }
    .lightbox-close {
      position: absolute; top: 20px; right: 24px;
      color: white; font-size: 28px; cursor: pointer;
      background: rgba(0,0,0,0.4); width: 40px; height: 40px;
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
    }

    @media (max-width: 600px) {
      .ulasan-saya-page { padding: 16px; }
      .ulasan-stats { grid-template-columns: repeat(3, 1fr); }
      .ulasan-card-header { flex-wrap: wrap; }
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="ulasan-saya-page">

  <button class="btn-back" onclick="history.back()">
    <i class="fa fa-arrow-left"></i> Kembali
  </button>

  <h1 class="page-title">
    <i class="fa fa-star"></i> Ulasan Saya
  </h1>

  <?php
  // Hitung statistik
  $total_ulasan = count($ulasan_list);
  $total_rating = $total_ulasan > 0 ? array_sum(array_column($ulasan_list, 'rating')) : 0;
  $rata_rating  = $total_ulasan > 0 ? round($total_rating / $total_ulasan, 1) : 0;
  $bintang5     = count(array_filter($ulasan_list, fn($u) => $u['rating'] == 5));
  ?>

  <!-- Stats -->
  <div class="ulasan-stats">
    <div class="ulasan-stat-box">
      <i class="fa fa-comment-alt"></i>
      <div class="num"><?= $total_ulasan ?></div>
      <div class="lbl">Total Ulasan</div>
    </div>
    <div class="ulasan-stat-box">
      <i class="fa fa-star"></i>
      <div class="num"><?= $rata_rating ?></div>
      <div class="lbl">Rata-rata Rating</div>
    </div>
    <div class="ulasan-stat-box">
      <i class="fa fa-award"></i>
      <div class="num"><?= $bintang5 ?></div>
      <div class="lbl">Bintang 5</div>
    </div>
  </div>

  <?php if (empty($ulasan_list)): ?>
  <!-- Kosong -->
  <div class="empty-state">
    <i class="fa fa-star"></i>
    <h3>Belum Ada Ulasan</h3>
    <p>Kamu belum pernah menulis ulasan. Beli produk dan bagikan pengalamanmu!</p>
    <a href="kategori.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;">
      <i class="fa fa-shopping-bag"></i> Mulai Belanja
    </a>
  </div>

  <?php else: ?>

  <!-- Daftar Ulasan -->
  <?php foreach ($ulasan_list as $u):
    $bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $tgl   = new DateTime($u['created_at']);
    $tgl_str = $tgl->format('d') . ' ' . $bulan[(int)$tgl->format('m')-1] . ' ' . $tgl->format('Y');
    $rating_labels = ['','Sangat Kurang','Kurang','Cukup','Baik','Sangat Baik'];
  ?>
  <div class="ulasan-card">
    <!-- Header: info produk -->
    <div class="ulasan-card-header">
      <?php if (!empty($u['gambar_produk'])): ?>
        <img src="uploads/<?= htmlspecialchars($u['gambar_produk']) ?>"
             class="ulasan-produk-img"
             onerror="this.style.display='none'">
      <?php else: ?>
        <div class="ulasan-produk-img-placeholder">
          <i class="fa fa-image" style="color:#d1d5db;font-size:18px;"></i>
        </div>
      <?php endif; ?>

      <div class="ulasan-produk-info">
        <div class="ulasan-produk-nama"><?= htmlspecialchars($u['nama_produk']) ?></div>
        <div class="ulasan-produk-meta">
          <span style="background:#fff5f0;color:var(--primary);padding:1px 8px;border-radius:9999px;font-size:11px;font-weight:700;">
            <?= htmlspecialchars($u['kategori']) ?>
          </span>
          <?php if ($u['kode_pesanan']): ?>
          <span class="kode"><?= htmlspecialchars($u['kode_pesanan']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="ulasan-tgl"><?= $tgl_str ?></div>
    </div>

    <!-- Body: rating + komentar + foto -->
    <div class="ulasan-card-body">
      <!-- Bintang -->
      <div class="ulasan-stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <span class="<?= $i <= $u['rating'] ? '' : 'empty' ?>">★</span>
        <?php endfor; ?>
      </div>

      <!-- Komentar -->
      <div class="ulasan-komentar">
        <?= nl2br(htmlspecialchars($u['komentar'])) ?>
      </div>

      <!-- Foto -->
      <?php if (!empty($u['foto'])): ?>
      <div class="ulasan-foto-list">
        <img src="uploads/<?= htmlspecialchars($u['foto']) ?>"
             class="ulasan-foto-item"
             onclick="bukaLightbox(this.src)"
             alt="Foto ulasan">
      </div>
      <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="ulasan-card-footer">
      <span class="rating-label">
        <i class="fa fa-star"></i>
        <?= $u['rating'] ?>/5 — <?= $rating_labels[$u['rating']] ?>
      </span>
      <a href="produk.php?id=<?= $u['id_produk'] ?>" class="btn-lihat-produk">
        <i class="fa fa-eye"></i> Lihat Produk
      </a>
    </div>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>

</div>

<!-- Lightbox foto -->
<div class="lightbox" id="lightbox" onclick="tutupLightbox()">
  <div class="lightbox-close" onclick="tutupLightbox()"><i class="fa fa-times"></i></div>
  <img id="lightboxImg" src="" alt="Foto ulasan">
</div>

<?php include 'includes/footer.php'; ?>

<script>
function bukaLightbox(src) {
  document.getElementById('lightboxImg').src = src;
  document.getElementById('lightbox').classList.add('open');
}
function tutupLightbox() {
  document.getElementById('lightbox').classList.remove('open');
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') tutupLightbox();
});
</script>
</body>
</html>
