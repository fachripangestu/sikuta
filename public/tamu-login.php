<?php
// Guest login + dashboard form (single page)
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tamu;
use Illuminate\Support\Str;

$login_code = config('auth.guest_login_code');
if (!is_string($login_code) || $login_code === '') {
    http_response_code(404);
    exit;
}
$errors = [];
$success = null;

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: tamu-login.php');
    exit;
}

// login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $code = trim($_POST['code'] ?? '');
    if ($code === '') {
        $errors[] = 'Kode kunjungan wajib diisi.';
    } elseif ($code !== $login_code) {
        $errors[] = 'Kode kunjungan tidak valid.';
    } else {
        $_SESSION['guest_code'] = $code;
        header('Location: tamu-login.php');
        exit;
    }
}

// dashboard submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_form') {
    if (!isset($_SESSION['guest_code'])) {
        header('Location: tamu-login.php');
        exit;
    }

    $nama      = trim($_POST['nama'] ?? '');
    $instansi  = trim($_POST['instansi'] ?? '');
    $jabatan   = trim($_POST['jabatan'] ?? '');
    $telepon   = trim($_POST['telepon'] ?? '');
    $tujuan    = trim($_POST['tujuan'] ?? '');
    $keperluan = trim($_POST['keperluan'] ?? '');

    if ($nama === '' || $telepon === '' || $tujuan === '') {
        $errors[] = 'Nama, telepon, dan tujuan wajib diisi.';
    }

    $upload_name = null;
    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['lampiran']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengunggah lampiran.';
        } else {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Lampiran harus PDF/JPG/PNG.';
            } else {
                $dir = __DIR__ . '/uploads_tamu';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $upload_name = Str::uuid() . '.' . $ext;
                if (!move_uploaded_file($_FILES['lampiran']['tmp_name'], $dir . '/' . $upload_name)) {
                    $errors[] = 'Gagal menyimpan lampiran.';
                }
            }
        }
    }

    if (!$errors) {
        Tamu::create([
            'nama'          => $nama,
            'instansi'      => $instansi,
            'jabatan'       => $jabatan,
            'telepon'       => $telepon,
            'tujuan'        => $tujuan,
            'keperluan'     => $keperluan,
            'lampiran_path' => $upload_name ? 'uploads_tamu/' . $upload_name : null,
        ]);
        $success = 'Data berhasil disimpan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Tamu - SIKUTA</title>
    <link rel="stylesheet" href="css/tamu-login.css">
</head>
<body>
    <div class="shell">
        <div class="header-brand">
            <div class="badge-logo">K</div>
            <div>
                <div class="brand-title">Kunjungan Tamu</div>
                <small class="brand-subtitle">Panel masuk</small>
            </div>
        </div>

        <div class="card">
            <div class="tabs">
                <a class="tab" href="{{ url('/login') }}">Pengelola</a>
                <div class="tab active">Tamu</div>
            </div>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['guest_code'])): ?>
                <h1>Masuk Tamu</h1>
                <p class="sub">Masuk cukup dengan kode kunjungan tanpa email / password.</p>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <label>Kode kunjungan</label>
                    <input type="text" name="code" placeholder="LTSR-2602-0001" required>
                    <div class="mt-14">
                        <button class="btn" type="submit">Lanjut</button>
                    </div>
                </form>
            <?php else: ?>
                <h1>Form Kunjungan</h1>
                <p class="sub">Lengkapi data kunjungan dan unggah surat/berkas jika ada.</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="submit_form">
                    <div class="grid-2">
                        <div>
                            <label>Nama</label>
                            <input type="text" name="nama" required>
                        </div>
                        <div>
                            <label>Instansi</label>
                            <input type="text" name="instansi">
                        </div>
                        <div>
                            <label>Jabatan</label>
                            <input type="text" name="jabatan">
                        </div>
                        <div>
                            <label>No. Telepon</label>
                            <input type="text" name="telepon" required>
                        </div>
                        <div>
                            <label>Tujuan</label>
                            <input type="text" name="tujuan" required>
                        </div>
                        <div>
                            <label>Lampiran (PDF/JPG/PNG)</label>
                            <input type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="mt-12">
                        <label>Keperluan</label>
                        <textarea name="keperluan" placeholder="Rincian keperluan"></textarea>
                    </div>
                    <div class="actions mt-14 justify-end">
                        <a class="btn-ghost" href="?logout=1">Keluar</a>
                        <button class="btn" type="submit">Kirim</button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="footer-link">
                Belum punya akun pengelola? <a href="{{ url('/register') }}">Hubungi admin</a>
            </div>
        </div>
    </div>
</body>
</html>
