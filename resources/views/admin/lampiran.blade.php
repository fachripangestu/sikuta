<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $filename }} - SIKUTA</title>
    @php
        $faviconPngPath = public_path('images/logo-sikuta-full.png');
        $faviconIcoPath = public_path('favicon.ico');
        $faviconVersion = file_exists($faviconIcoPath)
            ? (string) filemtime($faviconIcoPath)
            : (file_exists($faviconPngPath) ? (string) filemtime($faviconPngPath) : (string) time());
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <style>
        :root {
            color-scheme: dark;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #0b1b3c;
            color: #dce8ff;
            font-family: "Outfit", "Segoe UI", Arial, sans-serif;
        }

        .viewer-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .viewer-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(125, 172, 255, 0.24);
            background: rgba(5, 20, 48, 0.88);
            backdrop-filter: blur(4px);
        }

        .viewer-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .viewer-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid rgba(113, 175, 255, 0.35);
            padding: 8px 12px;
            color: #dce8ff;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            background: rgba(32, 78, 149, 0.24);
        }

        .viewer-frame {
            width: 100%;
            flex: 1;
            border: 0;
            background: #f4f7ff;
        }
    </style>
</head>
<body>
    @php
        $rawRoute = $rawRoute ?? route('admin.kunjungan.lampiran.raw', $tamu);
        $viewerTitle = $viewerTitle ?? ('Lampiran ' . $filename);
    @endphp
    <div class="viewer-shell">
        <header class="viewer-head">
            <h1 class="viewer-title">{{ $filename }}</h1>
            <a class="viewer-back" href="{{ route('admin.dashboard') }}">Kembali ke Dashboard</a>
        </header>
        <iframe
            class="viewer-frame"
            src="{{ $rawRoute }}"
            title="{{ $viewerTitle }}"
        ></iframe>
    </div>
</body>
</html>
