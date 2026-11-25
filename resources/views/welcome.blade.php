<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alur Penggunaan Kunjungan</title>
    @php
        $faviconPath = public_path('images/logo-sikuta-full.png');
        $faviconVersion = file_exists($faviconPath) ? (string) filemtime($faviconPath) : (string) time();
    @endphp
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --teal-700: #0f7f98;
            --teal-600: #138da5;
            --teal-500: #1f9fb6;
            --line: #22a5bb;
            --paper: #f4f7f8;
            --ink: #0d1b22;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Nunito', system-ui, -apple-system, sans-serif;
            background: #e9eff2;
            color: var(--ink);
            padding: 18px;
        }

        .notif-wrap {
            max-width: 880px;
            margin: 0 auto 18px;
        }

        .notif-panel {
            width: min(480px, 100%);
            margin-left: auto;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(20, 31, 53, 0.18);
            overflow: hidden;
            border: 1px solid #e6e9ef;
        }

        .notif-head {
            padding: 14px 16px 10px;
            font-size: 36px;
            font-weight: 800;
            color: #1f2430;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notif-head .bell {
            color: #ff4c98;
            font-size: 20px;
        }

        .notif-tabs {
            display: flex;
            gap: 20px;
            border-bottom: 1px solid #d8dde7;
            padding: 0 16px;
        }

        .notif-tab {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 2px 12px;
            font-size: 28px;
            font-weight: 700;
            color: #222;
        }

        .notif-tab .count {
            border-radius: 7px;
            background: #2c8de8;
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            min-width: 26px;
            text-align: center;
            line-height: 1;
            padding: 3px 6px;
        }

        .notif-tab.muted .count {
            background: #e7ecf4;
            color: #6f7f94;
        }

        .notif-tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 3px;
            background: #2c8de8;
        }

        .notif-item {
            display: grid;
            grid-template-columns: 52px 1fr;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e9edf3;
        }

        .notif-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #8893a2;
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 20px;
            font-weight: 800;
            position: relative;
        }

        .notif-avatar::after {
            content: "";
            position: absolute;
            right: -2px;
            bottom: 1px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #3d7ed7;
            border: 2px solid #fff;
        }

        .notif-title {
            font-size: 25px;
            font-weight: 800;
            color: #363c47;
            line-height: 1.1;
        }

        .notif-badge {
            margin-left: 6px;
            border-radius: 10px;
            background: #eaf2ff;
            color: #3071cc;
            font-size: 18px;
            font-weight: 800;
            padding: 2px 7px;
            vertical-align: middle;
            white-space: nowrap;
        }

        .notif-text {
            margin-top: 3px;
            font-size: 22px;
            color: #676f7c;
            line-height: 1.35;
        }

        .notif-time {
            margin-top: 4px;
            font-size: 19px;
            color: #8a96a6;
        }

        .notif-foot {
            padding: 10px 16px;
            text-align: right;
            color: #2c8de8;
            font-size: 28px;
            font-weight: 700;
            background: #f7f9fc;
        }

        .flow-wrap {
            max-width: 880px;
            margin: 0 auto;
            background: var(--paper);
            border: 3px solid var(--teal-600);
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(16, 53, 66, 0.14);
            overflow: hidden;
        }

        .flow-head {
            background: linear-gradient(180deg, var(--teal-700), #0c7188);
            color: #fff;
            text-align: center;
            font-size: clamp(26px, 3.6vw, 46px);
            font-weight: 800;
            line-height: 1.1;
            padding: 14px 16px;
        }

        .flow-body {
            padding: 20px 22px 24px;
        }

        .step {
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 14px;
            align-items: center;
            position: relative;
            margin: 10px 0;
        }

        .step-number {
            width: 66px;
            height: 66px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            display: grid;
            place-items: center;
        }

        .step-number span {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(180deg, var(--teal-700), #0d88a1);
            color: #fff;
            font-weight: 800;
            font-size: 27px;
            display: grid;
            place-items: center;
        }

        .step-card {
            border: 2px solid var(--line);
            border-radius: 14px;
            background: #fff;
            min-height: 84px;
            display: grid;
            grid-template-columns: 86px 1fr;
            align-items: center;
            padding: 10px 16px;
            box-shadow: 0 5px 10px rgba(13, 36, 45, 0.08);
        }

        .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 36px;
            color: #0a6f86;
        }

        .step-title {
            font-size: clamp(22px, 2.8vw, 38px);
            font-weight: 800;
            line-height: 1.1;
            color: #111;
        }

        .step-sub {
            font-size: clamp(16px, 2vw, 28px);
            font-weight: 700;
            line-height: 1.2;
            margin-top: 4px;
            color: #111;
        }

        .arrow {
            width: 0;
            height: 0;
            margin: 6px auto;
            border-left: 13px solid transparent;
            border-right: 13px solid transparent;
            border-top: 18px solid #1692aa;
        }

        @media (max-width: 700px) {
            body { padding: 8px; }
            .notif-panel { width: 100%; }
            .notif-head { font-size: 28px; }
            .notif-tab { font-size: 22px; }
            .notif-tab .count { font-size: 16px; }
            .notif-title { font-size: 20px; }
            .notif-text { font-size: 16px; }
            .notif-time { font-size: 14px; }
            .notif-foot { font-size: 20px; }
            .flow-body { padding: 12px; }
            .step { grid-template-columns: 58px 1fr; gap: 10px; }
            .step-number { width: 54px; height: 54px; border-radius: 12px; }
            .step-number span { width: 36px; height: 36px; font-size: 22px; }
            .step-card { grid-template-columns: 56px 1fr; min-height: 66px; padding: 8px 10px; }
            .icon { width: 46px; height: 46px; font-size: 27px; }
            .arrow { border-left-width: 9px; border-right-width: 9px; border-top-width: 14px; }
        }
    </style>
</head>
<body>
    <section class="notif-wrap" aria-label="Notifikasi">
        <div class="notif-panel">
            <div class="notif-head"><span class="bell">&#128276;</span>Notifikasi</div>
            <div class="notif-tabs">
                <div class="notif-tab active">All <span class="count">0</span></div>
                <div class="notif-tab muted">Kotak Masuk <span class="count">0</span></div>
            </div>

            <article class="notif-item">
                <div class="notif-avatar">DB</div>
                <div>
                    <div class="notif-title">DEDEN BAHTIAR NUGRAHA <span class="notif-badge">KOTAK MASUK</span></div>
                    <div class="notif-text">Aktivitas anda telah di setujui oleh atasan (DEDEN BAHTIAR NUGRAHA). Total aktivitas 12 dengan waktu efektif 2415 menit.</div>
                    <div class="notif-time">21 jam yang lalu</div>
                </div>
            </article>

            <article class="notif-item">
                <div class="notif-avatar">DB</div>
                <div>
                    <div class="notif-title">DEDEN BAHTIAR NUGRAHA <span class="notif-badge">KOTAK MASUK</span></div>
                    <div class="notif-text">Indikator Persentase Implementasi Manajemen Risiko disetujui oleh DEDEN BAHTIAR NUGRAHA.</div>
                    <div class="notif-time">03 Mei 2026 11:23</div>
                </div>
            </article>

            <article class="notif-item">
                <div class="notif-avatar">M</div>
                <div>
                    <div class="notif-title">MURTADI <span class="notif-badge">KOTAK MASUK</span></div>
                    <div class="notif-text">Aktivitas anda telah di setujui oleh atasan (MURTADI) total aktivitas 33 dengan waktu efektif 7080 menit.</div>
                    <div class="notif-time">02 Apr 2026 18:59</div>
                </div>
            </article>

            <div class="notif-foot">Lihat semua notifikasi</div>
        </div>
    </section>

    <section class="flow-wrap" aria-label="Alur Tanpa Login">
        <div class="flow-head">1. Alur Tanpa Login (Tamu)</div>
        <div class="flow-body">
            <div class="step">
                <div class="step-number"><span>1</span></div>
                <div class="step-card"><div class="icon">??</div><div class="step-title">Mulai</div></div>
            </div>
            <div class="arrow"></div>

            <div class="step">
                <div class="step-number"><span>2</span></div>
                <div class="step-card"><div class="icon">??</div><div class="step-title">Tamu membuka website kunjungan tamu</div></div>
            </div>
            <div class="arrow"></div>

            <div class="step">
                <div class="step-number"><span>3</span></div>
                <div class="step-card"><div class="icon">???</div><div class="step-title">Tamu menekan tombol "Isi Permohonan"</div></div>
            </div>
            <div class="arrow"></div>

            <div class="step">
                <div class="step-number"><span>4</span></div>
                <div class="step-card">
                    <div class="icon">??</div>
                    <div>
                        <div class="step-title">Tamu mengisi data diri dan data kunjungan</div>
                        <div class="step-sub">(asal instansi, PIC, kontak, tujuan, tanggal usulan, jumlah peserta, lampiran)</div>
                    </div>
                </div>
            </div>
            <div class="arrow"></div>

            <div class="step">
                <div class="step-number"><span>5</span></div>
                <div class="step-card"><div class="icon">??</div><div class="step-title">Tamu mengirim permohonan</div></div>
            </div>
            <div class="arrow"></div>

            <div class="step">
                <div class="step-number"><span>6</span></div>
                <div class="step-card"><div class="icon">???</div><div class="step-title">Sistem menyimpan data permohonan</div></div>
            </div>
            <div class="arrow"></div>

            <div class="step">
                <div class="step-number"><span>7</span></div>
                <div class="step-card"><div class="icon">?</div><div class="step-title">Menunggu verifikasi internal</div></div>
            </div>
            <div class="arrow"></div>

            <div class="step">
                <div class="step-number"><span>8</span></div>
                <div class="step-card"><div class="icon">??</div><div class="step-title">Selesai</div></div>
            </div>
        </div>
    </section>
</body>
</html>

