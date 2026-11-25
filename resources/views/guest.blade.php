<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halo Tamu</title>
    @php
        $faviconPath = public_path('images/logo-sikuta-full.png');
        $faviconVersion = file_exists($faviconPath) ? (string) filemtime($faviconPath) : (string) time();
    @endphp
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/guest.css') }}">
</head>
<body>
    <div class="card">
        <h1>Selamat datang, tamu!</h1>
        <p>Kode kunjungan Anda:</p>
        <div class="pill">{{ $guestCode }}</div>
        <p class="mt-18">Silakan tunjukkan kode ini saat tiba di lokasi untuk proses check-in.</p>
        <a class="btn" href="{{ route('dashboard') }}">Kembali ke halaman utama</a>
    </div>
</body>
</html>
