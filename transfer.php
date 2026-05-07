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

$rekening_toko = [
    'BCA'     => ['no' => '1234 5678 90',   'nama' => 'lavo.id', 'warna' => '#003d82'],
    'Mandiri' => ['no' => '1100 0099 8877',  'nama' => 'lavo.id', 'warna' => '#003087'],
    'BRI'     => ['no' => '1122 3344 5566',  'nama' => 'lavo.id', 'warna' => '#00529b'],
];

// Proses setelah PIN — langsung redirect ke bukti transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_pin'])) {
    $bank          = trim($_POST['bank'] ?? '');
    $no_rek        = trim($_POST['no_rek_pengirim'] ?? '');
    $nama_pengirim = trim($_POST['nama_pengirim'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE pesanan SET status='pending' WHERE kode=?");
    mysqli_stmt_bind_param($stmt, 's', $kode);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['bukti_transfer'] = [
        'kode'          => $kode,
        'total'         => $total,
        'bank'          => $bank,
        'nama_pengirim' => $nama_pengirim,
        'no_rek'        => $no_rek,
        'bukti'         => null,
        'waktu'         => date('d-m-Y H:i:s'),
    ];
    unset($_SESSION['pesanan_kode'], $_SESSION['pesanan_total'], $_SESSION['pesanan_alamat']);
    header("Location: bukti-transfer.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transfer Bank - lavo.id</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background: #f0f2f5; }
    .transfer-page { max-width: 460px; margin: 0 auto; padding: 24px 16px 60px; }

    /* Step bar */
    .step-bar { display:flex; align-items:center; margin-bottom:8px; }
    .step-dot { width:28px;height:28px;border-radius:50%;background:var(--border);color:var(--text-muted);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .3s; }
    .step-dot.active { background:var(--primary);color:white; }
    .step-dot.done   { background:var(--success);color:white; }
    .step-line { flex:1;height:2px;background:var(--border);transition:background .3s; }
    .step-line.done { background:var(--success); }
    .step-labels { display:flex;justify-content:space-between;margin-bottom:24px; }
    .step-lbl { font-size:10px;color:var(--text-muted);text-align:center;width:28px; }
    .step-lbl.active { color:var(--primary);font-weight:700; }

    /* Panel */
    .step-panel { display:none; }
    .step-panel.active { display:block; }

    /* Bank card */
    .bank-item { display:flex;align-items:center;gap:14px;padding:14px 16px;border:2px solid var(--border);border-radius:var(--radius-md);cursor:pointer;transition:all .2s;margin-bottom:10px; }
    .bank-item:hover,.bank-item.selected { border-color:var(--primary);background:#fff5f0; }
    .bank-logo { width:52px;height:34px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:white;flex-shrink:0; }
    .bank-info { flex:1; }
    .bank-nama { font-size:14px;font-weight:700;color:var(--text-dark); }
    .bank-desc { font-size:12px;color:var(--text-muted); }
    .bank-radio { width:20px;height:20px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s; }
    .bank-item.selected .bank-radio { background:var(--primary);border-color:var(--primary);color:white; }

    /* Rekening toko */
    .rek-card { border-radius:var(--radius-md);overflow:hidden;margin-bottom:16px;box-shadow:var(--shadow-sm); }
    .rek-header { padding:14px 18px;color:white;font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px; }
    .rek-body { background:white;border:1px solid var(--border);border-top:none;padding:18px; }
    .rek-no { font-size:24px;font-weight:800;letter-spacing:2px;color:var(--text-dark);font-family:monospace;margin-bottom:4px; }
    .rek-nama { font-size:13px;color:var(--text-muted);margin-bottom:12px; }
    .btn-copy { background:#dbeafe;color:#1d4ed8;border:none;padding:7px 14px;border-radius:9999px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:background .2s; }
    .btn-copy:hover { background:#bfdbfe; }

    /* Total */
    .total-box { background:white;border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px; }
    .total-box .lbl { font-size:13px;color:var(--text-muted); }
    .total-box .val { font-size:20px;font-weight:800;color:var(--text-red); }

    /* Form */
    .form-card { background:white;border:1px solid var(--border);border-radius:var(--radius-md);padding:18px;margin-bottom:16px; }
    .form-card h4 { font-size:14px;font-weight:700;color:var(--text-dark);margin-bottom:14px;display:flex;align-items:center;gap:8px; }
    .form-card h4 i { color:var(--primary); }

    /* PIN */
    .pin-wrapper { display:flex;gap:8px;justify-content:center;margin:12px 0; }
    .pin-box { width:46px;height:54px;border:2px solid var(--border);border-radius:var(--radius-sm);text-align:center;font-size:20px;font-weight:700;outline:none;transition:border-color .2s; }
    .pin-box:focus { border-color:var(--primary); }

    /* Upload */
    .upload-area { border:2px dashed var(--border);border-radius:var(--radius-md);padding:28px;text-align:center;cursor:pointer;transition:all .2s;position:relative;margin-bottom:12px; }
    .upload-area:hover,.upload-area.dragover { border-color:var(--primary);background:#fff5f0; }
    .upload-area input[type=file] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%; }
    .upload-area i { font-size:36px;color:var(--border);margin-bottom:8px;display:block; }
    .upload-area p { font-size:13px;color:var(--text-muted);margin:0; }
    .upload-area small { font-size:11px;color:var(--text-muted); }
    .upload-preview { display:none;margin-bottom:12px;position:relative; }
    .upload-preview img { width:100%;border-radius:var(--radius-sm);border:1px solid var(--border);max-height:200px;object-fit:contain; }
    .upload-preview .btn-rm { position:absolute;top:6px;right:6px;background:#ef4444;color:white;border:none;border-radius:50%;width:26px;height:26px;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center; }

    /* Warning upload */
    .upload-warning { background:#fef3c7;border:1px solid #fcd34d;border-radius:var(--radius-sm);padding:12px 14px;font-size:13px;color:#92400e;margin-bottom:12px;display:flex;gap:8px;align-items:flex-start; }

    /* Buttons */
    .btn-next { width:100%;background:var(--primary);color:white;border-radius:9999px;padding:13px;font-size:15px;font-weight:700;border:none;cursor:pointer;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:6px; }
    .btn-next:hover { background:var(--primary-dark); }
    .btn-next:disabled { background:var(--border);cursor:not-allowed; }
    .btn-prev { width:100%;background:transparent;color:var(--text-muted);border:1.5px solid var(--border);border-radius:9999px;padding:11px;font-size:14px;font-weight:600;cursor:pointer;margin-top:8px;transition:all .2s; }
    .btn-prev:hover { border-color:var(--primary);color:var(--primary); }

    .info-note { background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius-sm);padding:11px 14px;font-size:12px;color:#1e40af;margin-bottom:14px;display:flex;gap:8px; }
  </style>
</head>
<body>

<header class="navbar">
  <div class="navbar-logo">
    <img src="logo.png/logo.png" alt="Logo" onerror="this.style.display='none'">
    <h2>lavo.id</h2>
  </div>
</header>

<div class="transfer-page">

  <h1 style="font-size:19px;font-weight:700;font-family:'Times New Roman',serif;margin-bottom:18px;">
    <i class="fa fa-university" style="color:var(--primary);margin-right:8px;"></i>Transfer Bank
  </h1>

  <!-- Step Bar -->
  <div class="step-bar">
    <div class="step-dot active" id="d1">1</div>
    <div class="step-line" id="l1"></div>
    <div class="step-dot" id="d2">2</div>
    <div class="step-line" id="l2"></div>
    <div class="step-dot" id="d3">3</div>
    <div class="step-line" id="l3"></div>
    <div class="step-dot" id="d4">4</div>
  </div>
  <div class="step-labels">
    <span class="step-lbl active" id="lb1">Bank</span>
    <span class="step-lbl" id="lb2">Rek</span>
    <span class="step-lbl" id="lb3">Data</span>
    <span class="step-lbl" id="lb4">PIN</span>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error" style="margin-bottom:14px;">
    <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- STEP 1: Pilih Bank -->
  <div class="step-panel active" id="s1">
    <div class="total-box">
      <span class="lbl">Total Transfer</span>
      <span class="val">Rp <?= number_format($total, 0, ',', '.') ?></span>
    </div>
    <p style="font-size:14px;font-weight:600;color:var(--text-dark);margin-bottom:12px;">Pilih Bank Tujuan</p>
    <?php foreach ($rekening_toko as $nb => $info): ?>
    <div class="bank-item" onclick="pilihBank('<?= $nb ?>','<?= $info['no'] ?>','<?= $info['nama'] ?>','<?= $info['warna'] ?>')" id="bi-<?= $nb ?>">
      <div class="bank-logo" style="background:<?= $info['warna'] ?>;"><?= $nb ?></div>
      <div class="bank-info">
        <div class="bank-nama">Bank <?= $nb ?></div>
        <div class="bank-desc">Rekening <?= $nb ?> lavo.id</div>
      </div>
      <div class="bank-radio" id="br-<?= $nb ?>">
        <i class="fa fa-check" style="font-size:10px;"></i>
      </div>
    </div>
    <?php endforeach; ?>
    <button class="btn-next" id="btnS1" onclick="goStep(2)" disabled>
      Lihat Rekening Tujuan <i class="fa fa-arrow-right"></i>
    </button>
  </div>

  <!-- STEP 2: Info Rekening Toko -->
  <div class="step-panel" id="s2">
    <div class="rek-card">
      <div class="rek-header" id="rekHeader">
        <i class="fa fa-university"></i> <span id="rekBankLabel">Bank</span>
      </div>
      <div class="rek-body">
        <div class="rek-no" id="rekNo">—</div>
        <div class="rek-nama" id="rekNama">—</div>
        <button class="btn-copy" onclick="copyRek()">
          <i class="fa fa-copy"></i> Salin Nomor
        </button>
      </div>
    </div>
    <div class="total-box">
      <span class="lbl">Nominal yang harus ditransfer</span>
      <span class="val">Rp <?= number_format($total, 0, ',', '.') ?></span>
    </div>
    <div class="info-note">
      <i class="fa fa-info-circle" style="flex-shrink:0;margin-top:1px;"></i>
      Transfer nominal <strong>tepat sama</strong> ke rekening di atas, lalu klik "Lanjut Transfer".
    </div>
    <button class="btn-next" onclick="goStep(3)">
      Lanjut Transfer <i class="fa fa-arrow-right"></i>
    </button>
    <button class="btn-prev" onclick="goStep(1)">
      <i class="fa fa-arrow-left"></i> Ganti Bank
    </button>
  </div>

  <!-- STEP 3: Data Penerima -->
  <div class="step-panel" id="s3">
    <div class="form-card">
      <h4><i class="fa fa-university"></i> Data Penerima</h4>

      <div class="form-group">
        <label>Nama Penerima</label>
        <input type="text" id="inp_nama" class="form-control"
               value="lavo.id" readonly
               style="background:var(--bg-light);font-weight:700;">
      </div>

      <div class="form-group" style="margin-top:12px;">
        <label>Nomor Rekening Penjual</label>
        <input type="text" id="inp_norek" class="form-control"
               readonly
               style="background:var(--bg-light);font-weight:700;font-family:monospace;letter-spacing:1px;">
        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block;">
          Nomor rekening toko sesuai bank yang dipilih
        </small>
      </div>
    </div>
    <button class="btn-next" onclick="goStep4()">
      Bayar <i class="fa fa-lock"></i>
    </button>
    <button class="btn-prev" onclick="goStep(2)">
      <i class="fa fa-arrow-left"></i> Kembali
    </button>
  </div>

  <!-- STEP 4: PIN -->
  <div class="step-panel" id="s4">
    <form method="POST">
      <input type="hidden" name="konfirmasi_pin" value="1">
      <input type="hidden" name="bank" id="hidBank">
      <input type="hidden" name="nama_pengirim" id="hidNama">
      <input type="hidden" name="no_rek_pengirim" id="hidNoRek">

      <div class="form-card" style="text-align:center;">
        <h4 style="justify-content:center;"><i class="fa fa-lock"></i> Masukkan PIN Bank</h4>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">
          Masukkan PIN 6 digit rekening <strong id="pinBankLabel">bank</strong> kamu
        </p>
        <div class="pin-wrapper">
          <input type="password" class="pin-box" maxlength="1" oninput="nextPin(this,0)" id="pin0">
          <input type="password" class="pin-box" maxlength="1" oninput="nextPin(this,1)" id="pin1">
          <input type="password" class="pin-box" maxlength="1" oninput="nextPin(this,2)" id="pin2">
          <input type="password" class="pin-box" maxlength="1" oninput="nextPin(this,3)" id="pin3">
          <input type="password" class="pin-box" maxlength="1" oninput="nextPin(this,4)" id="pin4">
          <input type="password" class="pin-box" maxlength="1" oninput="nextPin(this,5)" id="pin5">
        </div>
        <p style="font-size:11px;color:var(--text-muted);">
          <i class="fa fa-shield-alt"></i> PIN hanya untuk simulasi, tidak disimpan
        </p>
      </div>
      <button type="submit" class="btn-next" id="btnPin">
        <i class="fa fa-check"></i> OK, Konfirmasi
      </button>
    </form>
    <button class="btn-prev" onclick="goStep(3)">
      <i class="fa fa-arrow-left"></i> Kembali
    </button>
  </div>

</div>

<script>
let bankDipilih = '';
let rekNoRaw    = '';
const banks = <?= json_encode($rekening_toko) ?>;

function pilihBank(nama, no, namaToko, warna) {
  document.querySelectorAll('.bank-item').forEach(el => el.classList.remove('selected'));
  document.getElementById('bi-' + nama).classList.add('selected');
  bankDipilih = nama;
  rekNoRaw    = no.replace(/\s/g, '');
  document.getElementById('btnS1').disabled = false;

  // Set rekening info step 2
  document.getElementById('rekHeader').style.background = warna;
  document.getElementById('rekBankLabel').textContent   = 'Bank ' + nama;
  document.getElementById('rekNo').textContent          = no;
  document.getElementById('rekNama').textContent        = 'a.n. ' + namaToko;
  document.getElementById('pinBankLabel').textContent   = nama;

  // Auto-fill step 3 — data penerima (rekening toko)
  document.getElementById('inp_nama').value  = namaToko;
  document.getElementById('inp_norek').value = no;
}

function goStep4() {
  const nama  = document.getElementById('inp_nama').value.trim();
  const norek = document.getElementById('inp_norek').value.trim();
  if (!nama || !norek) {
    alert('Lengkapi nama dan nomor rekening pengirim.');
    return;
  }
  document.getElementById('hidNama').value  = nama;
  document.getElementById('hidNoRek').value = norek;
  document.getElementById('hidBank').value  = bankDipilih;
  goStep(4);
  // Focus pin pertama
  setTimeout(() => document.getElementById('pin0').focus(), 300);
}

function goStep5() {
  // Cek PIN terisi semua
  const pins = document.querySelectorAll('.pin-box');
  let filled = true;
  pins.forEach(p => { if (!p.value) filled = false; });
  if (!filled) { alert('Masukkan PIN 6 digit terlebih dahulu.'); return; }
  // Submit form PIN
  document.querySelector('#s4 form').submit();
}

function goStep(n) {
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('s' + n).classList.add('active');

  for (let i = 1; i <= 4; i++) {
    const dot = document.getElementById('d' + i);
    const lbl = document.getElementById('lb' + i);
    if (i < n) {
      dot.className = 'step-dot done';
      dot.innerHTML = '<i class="fa fa-check" style="font-size:10px;"></i>';
    } else if (i === n) {
      dot.className = 'step-dot active';
      dot.textContent = i;
    } else {
      dot.className = 'step-dot';
      dot.textContent = i;
    }
    lbl.className = 'step-lbl' + (i === n ? ' active' : '');
    if (i < 4) {
      document.getElementById('l' + i).className = 'step-line' + (i < n ? ' done' : '');
    }
  }
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function copyRek() {
  navigator.clipboard.writeText(rekNoRaw).then(() => showToast('Nomor rekening disalin!'));
}

function nextPin(input, idx) {
  if (input.value && idx < 5) document.getElementById('pin' + (idx+1)).focus();
}

function previewBukti(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('previewImg').src = e.target.result;
    document.getElementById('uploadPreview').style.display = 'block';
    document.getElementById('uploadArea').style.display    = 'none';
  };
  reader.readAsDataURL(input.files[0]);
}

function hapusPreview() {
  document.getElementById('previewImg').src = '';
  document.getElementById('uploadPreview').style.display = 'none';
  document.getElementById('uploadArea').style.display    = 'block';
  document.querySelector('input[name="bukti_transfer"]').value = '';
}

function showToast(msg) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
    background:#1a1a1a;color:white;padding:12px 24px;border-radius:9999px;
    font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.3)`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2500);
}

// Drag & drop
const area = document.getElementById('uploadArea');
area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
area.addEventListener('dragleave', () => area.classList.remove('dragover'));
area.addEventListener('drop', e => {
  e.preventDefault(); area.classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (file) {
    const input = area.querySelector('input[type=file]');
    const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
    previewBukti(input);
  }
});

// Cegah back browser di step 5
window.addEventListener('popstate', function() {
  const s5 = document.getElementById('s5');
  if (s5 && s5.classList.contains('active')) {
    history.pushState(null, '', window.location.href);
    alert('Harap upload bukti transfer terlebih dahulu sebelum meninggalkan halaman ini.');
  }
});
</script>

</body>
</html>
