<?php
session_start();
include 'koneksi.php';
include 'includes/functions.php';

$user = $_SESSION['user'] ?? null;
if (!$user || empty($_SESSION['pesanan_kode'])) {
    header("Location: index.php"); exit;
}

$kode  = $_SESSION['pesanan_kode'];
$total = $_SESSION['pesanan_total'] ?? 0;
$error = '';

// Proses upload bukti transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi'])) {
    $no_rek_pengirim = trim($_POST['no_rek'] ?? '');
    $nama_pengirim   = trim($_POST['nama_pengirim'] ?? '');
    $bank_pengirim   = trim($_POST['bank'] ?? '');

    // Upload SS bukti transfer
    $bukti = null;
    if (!empty($_FILES['bukti_transfer']['tmp_name'])) {
        $ext     = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && $_FILES['bukti_transfer']['size'] <= 5 * 1024 * 1024) {
            $nama_file = 'transfer_' . $kode . '_' . time() . '.' . $ext;
            $target    = 'uploads/' . $nama_file;
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $target)) {
                $bukti = $nama_file;
            }
        } else {
            $error = 'File harus berupa gambar (JPG/PNG) maksimal 5MB.';
        }
    }

    if (!$error) {
        if (empty($no_rek_pengirim) || empty($nama_pengirim) || empty($bank_pengirim)) {
            $error = 'Lengkapi semua informasi transfer.';
        } elseif (!$bukti) {
            $error = 'Upload bukti transfer terlebih dahulu.';
        } else {
            // Simpan info konfirmasi ke DB
            $catatan = "Transfer dari: $nama_pengirim | Bank: $bank_pengirim | No.Rek: $no_rek_pengirim | Bukti: $bukti";
            $stmt = mysqli_prepare($conn, "UPDATE pesanan SET status='pending' WHERE kode=?");
            mysqli_stmt_bind_param($stmt, 's', $kode);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Simpan data untuk halaman bukti
            $_SESSION['bukti_transfer'] = [
                'kode'          => $kode,
                'total'         => $total,
                'nama_pengirim' => $nama_pengirim,
                'bank_pengirim' => $bank_pengirim,
                'no_rek'        => $no_rek_pengirim,
                'bukti'         => $bukti,
                'waktu'         => date('d-m-Y H:i:s'),
            ];

            // Bersihkan session pesanan
            unset($_SESSION['pesanan_kode'], $_SESSION['pesanan_total'], $_SESSION['pesanan_alamat']);
            header("Location: bukti-transfer.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Transfer - lavo.id</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .konfirmasi-page {
      max-width: 560px;
      margin: 0 auto;
      padding: 32px 24px;
    }

    .konfirmasi-title {
      font-size: 22px;
      font-weight: 700;
      font-family: 'Times New Roman', serif;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .konfirmasi-title i { color: #1a56db; }

    /* Tagihan box */
    .tagihan-box {
      background: linear-gradient(135deg, #1e40af, #1d4ed8);
      border-radius: var(--radius-md);
      padding: 20px 24px;
      color: white;
      margin-bottom: 20px;
      text-align: center;
    }

    .tagihan-box .label {
      font-size: 13px;
      opacity: 0.8;
      margin-bottom: 6px;
    }

    .tagihan-box .nominal {
      font-size: 32px;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .tagihan-box .kode {
      font-size: 13px;
      opacity: 0.75;
      margin-top: 6px;
      font-family: monospace;
    }

    /* Rekening tujuan */
    .rek-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .rek-card-header {
      background: var(--bg-light);
      padding: 12px 16px;
      font-size: 13px;
      font-weight: 700;
      color: var(--text-dark);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .rek-card-header i { color: #1a56db; }

    .rek-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      gap: 12px;
    }

    .rek-item:last-child { border-bottom: none; }

    .rek-bank {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 3px;
    }

    .rek-nomor {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-dark);
      letter-spacing: 1px;
      font-family: monospace;
    }

    .rek-atas-nama {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .btn-copy-rek {
      background: #dbeafe;
      color: #1d4ed8;
      border: none;
      padding: 8px 14px;
      border-radius: var(--radius-sm);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      flex-shrink: 0;
      transition: background 0.2s;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .btn-copy-rek:hover { background: #bfdbfe; }

    /* Form section */
    .form-section {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      margin-bottom: 20px;
    }

    .form-section-header {
      background: var(--bg-light);
      padding: 12px 16px;
      font-size: 13px;
      font-weight: 700;
      color: var(--text-dark);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-section-header i { color: var(--primary); }
    .form-section-body { padding: 16px; }

    /* Upload area */
    .upload-area {
      border: 2px dashed var(--border);
      border-radius: var(--radius-md);
      padding: 28px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      position: relative;
    }

    .upload-area:hover, .upload-area.dragover {
      border-color: var(--primary);
      background: #fff5f0;
    }

    .upload-area input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }

    .upload-area i { font-size: 36px; color: var(--border); margin-bottom: 10px; }
    .upload-area p { font-size: 14px; color: var(--text-muted); margin: 0; }
    .upload-area small { font-size: 12px; color: var(--text-muted); }

    .upload-preview {
      display: none;
      margin-top: 12px;
      position: relative;
    }

    .upload-preview img {
      max-width: 100%;
      max-height: 200px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      object-fit: contain;
    }

    .upload-preview .btn-remove {
      position: absolute;
      top: 6px;
      right: 6px;
      background: #ef4444;
      color: white;
      border: none;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      font-size: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-konfirmasi {
      width: 100%;
      background: #1d4ed8;
      color: white;
      border-radius: var(--radius-lg);
      padding: 14px;
      font-size: 15px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border: none;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-konfirmasi:hover { background: #1e40af; }

    @media (max-width: 480px) {
      .konfirmasi-page { padding: 16px; }
      .tagihan-box .nominal { font-size: 24px; }
    }
  </style>
</head>
<body>

<header class="navbar">
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="Logo" onerror="this.style.display='none'">
    <h2>lavo.id</h2>
  </div>
  <div class="navbar-actions">
    <a href="profil.php" class="nav-btn"><i class="fa fa-user"></i></a>
    <a href="keranjang.php" class="nav-btn"><i class="fa fa-shopping-cart"></i></a>
  </div>
</header>

<div class="konfirmasi-page">

  <button class="btn-back" onclick="history.back()">
    <i class="fa fa-arrow-left"></i> Kembali
  </button>

  <h1 class="konfirmasi-title">
    <i class="fa fa-university"></i> Konfirmasi Transfer
  </h1>

  <!-- Alert error -->
  <?php if ($error): ?>
  <div class="alert alert-error" style="margin-bottom:16px;">
    <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- Total Tagihan -->
  <div class="tagihan-box">
    <div class="label">Total yang harus ditransfer</div>
    <div class="nominal">Rp <?= number_format($total, 0, ',', '.') ?></div>
    <div class="kode">No. Pesanan: <?= htmlspecialchars($kode) ?></div>
  </div>

  <!-- Rekening Tujuan -->
  <div class="rek-card">
    <div class="rek-card-header">
      <i class="fa fa-university"></i> Transfer ke Rekening Berikut
    </div>
    <div class="rek-item">
      <div>
        <div class="rek-bank">BCA</div>
        <div class="rek-nomor">1234 5678 90</div>
        <div class="rek-atas-nama">a.n. lavo.id</div>
      </div>
      <button class="btn-copy-rek" onclick="copyRek('1234567890', this)">
        <i class="fa fa-copy"></i> Salin
      </button>
    </div>
    <div class="rek-item">
      <div>
        <div class="rek-bank">Mandiri</div>
        <div class="rek-nomor">1100 0099 8877</div>
        <div class="rek-atas-nama">a.n. lavo.id</div>
      </div>
      <button class="btn-copy-rek" onclick="copyRek('110000998877', this)">
        <i class="fa fa-copy"></i> Salin
      </button>
    </div>
  </div>

  <form method="POST" enctype="multipart/form-data">

    <!-- Info Pengirim -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="fa fa-user"></i> Informasi Pengirim
      </div>
      <div class="form-section-body">
        <div class="form-group">
          <label>Nama Pemilik Rekening</label>
          <input type="text" name="nama_pengirim" class="form-control"
                 placeholder="Sesuai nama di rekening bank" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="form-group">
            <label>Bank Pengirim</label>
            <select name="bank" class="form-control" required>
              <option value="">Pilih Bank</option>
              <option>BCA</option>
              <option>Mandiri</option>
              <option>BNI</option>
              <option>BRI</option>
              <option>BSI</option>
              <option>CIMB Niaga</option>
              <option>Lainnya</option>
            </select>
          </div>
          <div class="form-group">
            <label>Nomor Rekening</label>
            <input type="text" name="no_rek" class="form-control"
                   placeholder="Nomor rekening pengirim" required>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Bukti -->
    <div class="form-section">
      <div class="form-section-header">
        <i class="fa fa-image"></i> Upload Bukti Transfer
      </div>
      <div class="form-section-body">
        <div class="upload-area" id="uploadArea">
          <input type="file" name="bukti_transfer" accept="image/*"
                 onchange="previewBukti(this)" required>
          <i class="fa fa-cloud-upload-alt"></i>
          <p>Klik atau drag foto bukti transfer di sini</p>
          <small>Format: JPG, PNG, WEBP • Maks. 5MB</small>
        </div>
        <div class="upload-preview" id="uploadPreview">
          <img id="previewImg" src="" alt="Preview">
          <button type="button" class="btn-remove" onclick="hapusPreview()">
            <i class="fa fa-times"></i>
          </button>
        </div>
      </div>
    </div>

    <button type="submit" name="konfirmasi" class="btn-konfirmasi">
      <i class="fa fa-check-circle"></i> Konfirmasi Pembayaran
    </button>

  </form>

</div>

<?php include 'includes/footer.php'; ?>

<script>
function copyRek(nomor, btn) {
  navigator.clipboard.writeText(nomor).then(() => {
    btn.innerHTML = '<i class="fa fa-check"></i> Disalin!';
    btn.style.background = '#dcfce7';
    btn.style.color = '#16a34a';
    setTimeout(() => {
      btn.innerHTML = '<i class="fa fa-copy"></i> Salin';
      btn.style.background = '';
      btn.style.color = '';
    }, 2000);
  });
}

function previewBukti(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('previewImg').src = e.target.result;
    document.getElementById('uploadPreview').style.display = 'block';
    document.getElementById('uploadArea').style.display = 'none';
  };
  reader.readAsDataURL(input.files[0]);
}

function hapusPreview() {
  document.getElementById('previewImg').src = '';
  document.getElementById('uploadPreview').style.display = 'none';
  document.getElementById('uploadArea').style.display = 'block';
  document.querySelector('input[name="bukti_transfer"]').value = '';
}

// Drag & drop
const area = document.getElementById('uploadArea');
area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
area.addEventListener('dragleave', () => area.classList.remove('dragover'));
area.addEventListener('drop', e => {
  e.preventDefault();
  area.classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (file) {
    const input = area.querySelector('input[type="file"]');
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewBukti(input);
  }
});
</script>
</body>
</html>
