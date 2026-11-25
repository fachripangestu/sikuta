<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kehadiran Tamu - SIKUTA</title>
    @php
        $faviconPath = public_path('images/logo-sikuta-full.png');
        $faviconVersion = file_exists($faviconPath) ? (string) filemtime($faviconPath) : (string) time();
    @endphp
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (() => {
            try {
                const savedTheme = localStorage.getItem('sikuta_theme');
                const systemPrefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
                const initialTheme = savedTheme === 'light' || savedTheme === 'dark'
                    ? savedTheme
                    : (systemPrefersLight ? 'light' : 'dark');
                document.documentElement.setAttribute('data-theme', initialTheme);
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/guest-form.css') }}?v={{ file_exists(public_path('css/guest-form.css')) ? filemtime(public_path('css/guest-form.css')) : time() }}">
</head>
<body>
    @php
        $showFormOnLoad = $errors->any() || session('guest_success');
        $showHistoryOnLoad = false;
        $showChartOnLoad = false;
        $dbDown = $dbDown ?? false;
    @endphp

    <button
        class="theme-toggle"
        id="themeToggleBtn"
        type="button"
        aria-pressed="false"
        aria-label="Aktifkan mode terang"
    >
        <span class="theme-toggle-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M12 4.5a.9.9 0 0 1 .9.9V7a.9.9 0 0 1-1.8 0V5.4a.9.9 0 0 1 .9-.9Zm0 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.5-3.5a.9.9 0 0 1-.9.9H17a.9.9 0 0 1 0-1.8h1.6a.9.9 0 0 1 .9.9Zm-13.6 0a.9.9 0 0 1-.9.9H3.4a.9.9 0 1 1 0-1.8H5a.9.9 0 0 1 .9.9Zm9.16-5.56a.9.9 0 0 1 1.27 0l1.13 1.13a.9.9 0 1 1-1.28 1.28L15.06 7.7a.9.9 0 0 1 0-1.27Zm-7.12 7.12a.9.9 0 0 1 1.27 0 .9.9 0 0 1 0 1.27l-1.13 1.13a.9.9 0 0 1-1.27-1.28l1.13-1.12Zm8.4 2.22a.9.9 0 0 1 1.27 0l1.13 1.13a.9.9 0 1 1-1.28 1.27l-1.12-1.13a.9.9 0 0 1 0-1.27Zm-8.4-8.4a.9.9 0 0 1 0 1.28L6.8 9.8a.9.9 0 1 1-1.27-1.27l1.12-1.13a.9.9 0 0 1 1.28 0ZM12 17a.9.9 0 0 1 .9.9v1.6a.9.9 0 0 1-1.8 0v-1.6a.9.9 0 0 1 .9-.9Z" fill="currentColor"/>
            </svg>
        </span>
        <span class="theme-toggle-label">Mode</span>
    </button>

    <div class="shell">
        @if ($dbDown)
            <section class="card">
                <div class="alert alert-error">
                    Koneksi database belum aktif. Jalankan MySQL di XAMPP agar data riwayat dan proses verifikasi dapat berjalan normal.
                </div>
            </section>
        @endif

        <section class="hero">
            <div class="hero-main">
                <div class="badge-row">
                    <img class="logo-badge" src="{{ asset('images/logo-dki-jaya-raya.png') }}" alt="Logo DKI Jakarta">
                    <span class="eyebrow">Sistem Kunjungan Tamu Studi Tiru Inspektorat DKI Jakarta</span>
                </div>
                <div class="title-wrap">
                    <h1>SIKUTA mempermudah kunjungan Anda</h1>
                    <p>Kunjungan studi tiru meningkatkan pelayanan mutu tamu satu pintu</p>
                </div>
                <div class="hero-cta-wrap">
                    <button class="hero-cta-btn cta-isi" id="btnDaftarHadir" type="button">Permohonan Kunjungan Tamu</button>
                    <button class="hero-cta-btn hero-cta-btn-secondary cta-riwayat" id="btnRiwayatProgress" type="button">Progress dan Daftar Riwayat Kunjungan Tamu</button>
                    <button class="hero-cta-btn hero-cta-btn-admin cta-chart" id="btnStatistikKunjungan" type="button">Grafik Kunjungan</button>
                    <button class="hero-cta-btn hero-cta-btn-flow cta-alur" id="btnAlurPenggunaan" type="button">Alur Penggunaan</button>
                </div>
            </div>
            <aside class="hero-side">
                <div class="hero-side-top">
                    <div class="hero-side-logo-frame">
                        <img class="hero-side-logo" src="{{ asset('images/logo-sikuta-full.png') }}?v={{ file_exists(public_path('images/logo-sikuta-full.png')) ? filemtime(public_path('images/logo-sikuta-full.png')) : time() }}" alt="Logo SIKUTA">
                    </div>
                </div>
                <div class="hero-side-cta">
                    <a class="admin-link" href="{{ route('admin.login') }}">Login</a>
                </div>
                <p class="admin-note">Login khusus pengelola Kunjungan Tamu Inspektorat DKI Jakarta</p>
            </aside>
        </section>
        <div class="login-entry-modal" id="loginEntryModal" hidden aria-hidden="true">
            <div class="login-entry-backdrop" data-close-login-entry="1"></div>
            <section class="card login-entry-dialog" role="dialog" aria-modal="true" aria-labelledby="loginEntryTitle">
                <h2 id="loginEntryTitle">Login Hanya Khusus Admin Pengelola</h2>
                <p>Tamu tanpa login hanya isi permohonan kunjungan.</p>
                <div class="login-entry-actions">
                    <button class="login-entry-btn login-entry-btn-cancel" id="btnCancelLoginEntry" type="button">Batal</button>
                    <button class="login-entry-btn login-entry-btn-confirm" id="btnConfirmLoginEntry" type="button">Login</button>
                </div>
            </section>
        </div>
        <div class="alur-modal" id="alurPenggunaanModal" hidden aria-hidden="true">
            <div class="alur-backdrop" data-close-alur="1"></div>
            <section class="card process-card alur-dialog" role="dialog" aria-modal="true" aria-labelledby="alurTitle">
                <header class="alur-header">
                    <div class="alur-header-text">
                        <h2 id="alurTitle">Alur Penggunaan dan Proses Verifikasi Permohonan Kunjungan Tamu</h2>
                    </div>
                    <button class="alur-close" id="btnCloseAlur" type="button" aria-label="Tutup popup alur penggunaan">&times;</button>
                </header>

                <div class="process-dashboard">
                    <article class="process-flow-card">
                        <h3>Diagram 1: Alur Tanpa Login (Tamu)</h3>
                        <svg class="process-svg process-svg-large" viewBox="0 0 520 230" role="img" aria-label="Diagram alur tanpa login tamu">
                            <defs>
                                <linearGradient id="guestStepA" x1="0" x2="1" y1="0" y2="0">
                                    <stop offset="0%" stop-color="#14b8a6"></stop>
                                    <stop offset="100%" stop-color="#0284c7"></stop>
                                </linearGradient>
                                <marker id="arrowGuest" markerWidth="9" markerHeight="9" refX="8.2" refY="4.5" orient="auto">
                                    <path d="M0,0 L9,4.5 L0,9 z" fill="#96ddff"></path>
                                </marker>
                            </defs>

                            <rect x="34" y="74" rx="16" ry="16" width="140" height="68" fill="url(#guestStepA)" opacity="0.95"></rect>
                            <text x="104" y="101" text-anchor="middle" fill="#06263c" font-size="13" font-weight="700">Klik Tombol</text>
                            <text x="104" y="121" text-anchor="middle" fill="#06263c" font-size="12">Permohonan tamu</text>

                            <rect x="190" y="74" rx="16" ry="16" width="140" height="68" fill="url(#guestStepA)" opacity="0.95"></rect>
                            <text x="260" y="101" text-anchor="middle" fill="#06263c" font-size="13" font-weight="700">Isi Permohonan</text>
                            <text x="260" y="121" text-anchor="middle" fill="#06263c" font-size="12">Data lengkap</text>

                            <rect x="346" y="74" rx="16" ry="16" width="140" height="68" fill="url(#guestStepA)" opacity="0.95"></rect>
                            <text x="416" y="99" text-anchor="middle" fill="#06263c" font-size="13" font-weight="700">Pantau Progress</text>
                            <text x="416" y="119" text-anchor="middle" fill="#06263c" font-size="12">Hingga surat balasan</text>

                            <line x1="174" y1="108" x2="190" y2="108" stroke="#96ddff" stroke-width="3.5" stroke-linecap="round" marker-end="url(#arrowGuest)"></line>
                            <line x1="330" y1="108" x2="346" y2="108" stroke="#96ddff" stroke-width="3.5" stroke-linecap="round" marker-end="url(#arrowGuest)"></line>
                        </svg>
                    </article>

                    <article class="process-flow-card">
                        <h3>Diagram 2: Proses Verifikasi Kunjungan Tamu</h3>
                        <svg class="process-svg process-svg-large" viewBox="0 0 520 280" role="img" aria-label="Diagram alur internal dengan login">
                            <defs>
                                <linearGradient id="internalStepA" x1="0" x2="1" y1="0" y2="0">
                                    <stop offset="0%" stop-color="#60a5fa"></stop>
                                    <stop offset="100%" stop-color="#2563eb"></stop>
                                </linearGradient>
                                <linearGradient id="internalStepB" x1="0" x2="1" y1="0" y2="0">
                                    <stop offset="0%" stop-color="#fbbf24"></stop>
                                    <stop offset="100%" stop-color="#f97316"></stop>
                                </linearGradient>
                                <linearGradient id="internalStepC" x1="0" x2="1" y1="0" y2="0">
                                    <stop offset="0%" stop-color="#4ade80"></stop>
                                    <stop offset="100%" stop-color="#16a34a"></stop>
                                </linearGradient>
                                <marker id="arrowInternalBlue" markerWidth="9" markerHeight="9" refX="8.2" refY="4.5" orient="auto">
                                    <path d="M0,0 L9,4.5 L0,9 z" fill="#96ddff"></path>
                                </marker>
                                <marker id="arrowInternalGreen" markerWidth="9" markerHeight="9" refX="8.2" refY="4.5" orient="auto">
                                    <path d="M0,0 L9,4.5 L0,9 z" fill="#9ce6b0"></path>
                                </marker>
                                <marker id="arrowInternalAmber" markerWidth="9" markerHeight="9" refX="8.2" refY="4.5" orient="auto">
                                    <path d="M0,0 L9,4.5 L0,9 z" fill="#ffd5a3"></path>
                                </marker>
                            </defs>

                            <rect x="20" y="24" rx="14" ry="14" width="148" height="62" fill="url(#internalStepA)" opacity="0.95"></rect>
                            <text x="94" y="50" text-anchor="middle" fill="#f3f8ff" font-size="13" font-weight="700">Data Entry</text>
                            <text x="94" y="68" text-anchor="middle" fill="#f3f8ff" font-size="12">Antrian &amp; verifikasi</text>

                            <rect x="188" y="24" rx="14" ry="14" width="176" height="62" fill="url(#internalStepA)" opacity="0.95"></rect>
                            <text x="276" y="50" text-anchor="middle" fill="#f3f8ff" font-size="13" font-weight="700">Sekretaris Pimpinan</text>
                            <text x="276" y="68" text-anchor="middle" fill="#f3f8ff" font-size="12">Persetujuan / tunda</text>

                            <polygon points="392,106 460,106 494,138 460,170 392,170 358,138" fill="url(#internalStepB)" opacity="0.95"></polygon>
                            <text x="426" y="136" text-anchor="middle" fill="#3b2507" font-size="12" font-weight="700">Keputusan</text>
                            <text x="426" y="154" text-anchor="middle" fill="#3b2507" font-size="12">Sekretaris</text>

                            <rect x="94" y="198" rx="14" ry="14" width="208" height="64" fill="url(#internalStepC)" opacity="0.95"></rect>
                            <text x="198" y="226" text-anchor="middle" fill="#042714" font-size="13" font-weight="700">Operator Konfirmasi</text>
                            <text x="198" y="244" text-anchor="middle" fill="#042714" font-size="12">Kehadiran ke Subbag Umum</text>

                            <rect x="332" y="198" rx="14" ry="14" width="172" height="64" fill="url(#internalStepB)" opacity="0.95"></rect>
                            <text x="418" y="226" text-anchor="middle" fill="#3b2507" font-size="13" font-weight="700">Reschedule</text>
                            <text x="418" y="244" text-anchor="middle" fill="#3b2507" font-size="12">Tanggal penundaan</text>

                            <line x1="168" y1="55" x2="188" y2="55" stroke="#96ddff" stroke-width="4" stroke-linecap="round" marker-end="url(#arrowInternalBlue)"></line>
                            <line x1="364" y1="55" x2="400" y2="100" stroke="#96ddff" stroke-width="4" stroke-linecap="round" marker-end="url(#arrowInternalBlue)"></line>

                            <path d="M358 138 H272 L240 198" fill="none" stroke="#9ce6b0" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" marker-end="url(#arrowInternalGreen)"></path>
                            <text x="300" y="128" text-anchor="middle" fill="#9ce6b0" font-size="11" font-weight="700">DITERIMA</text>

                            <path d="M494 138 V186 H418" fill="none" stroke="#ffd5a3" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" marker-end="url(#arrowInternalAmber)"></path>
                            <text x="496" y="128" text-anchor="end" fill="#ffd5a3" font-size="11" font-weight="700">DITUNDA</text>
                        </svg>
                    </article>
                </div>

                <div class="process-grid process-grid-steps">
                    <article class="process-column">
                        <h3>1. Alur Tanpa Login (Tamu)</h3>
                        <ul class="process-steps">
                            <li><span class="step-num">1</span><span>Tamu klik tombol Permohonan Kunjungan Tamu.</span></li>
                            <li><span class="step-num">2</span><span>Tamu mengisi data permohonan kunjungan tamu secara lengkap.</span></li>
                            <li><span class="step-num">3</span><span>Tamu menunggu proses verifikasi dan dapat melihat progress permohonannya di Progress Kunjungan Tamu hingga surat balasan terunggah.</span></li>
                        </ul>
                    </article>
                    <article class="process-column">
                        <h3>2. Proses Verifikasi Kunjungan Tamu</h3>
                        <ul class="process-steps">
                            <li><span class="step-num">1</span><span>Data Permohonan kunjungan tamu telah terinput dan masuk ke antrian data entry.</span></li>
                            <li><span class="step-num">2</span><span>Data Entry verifikasi data permohonan kunjungan tamu.</span></li>
                            <li><span class="step-num">3</span><span>Sekretaris Pimpinan menerima data permohonan kunjungan tamu dan memberikan persetujuan untuk diteruskan ke Operator Bidang/Wilayah atau ditunda dengan memberikan tanggal penundaan.</span></li>
                            <li><span class="step-num">4</span><span>Operator Bidang/Wilayah mengonfirmasi kedatangan kunjungan tamu dan diteruskan ke tim Subbagian Umum.</span></li>
                            <li><span class="step-num">5</span><span>Tim Subbagian Umum mengirimkan surat balasan untuk tamu dan terupdate di progress riwayat tamu.</span></li>
                        </ul>
                    </article>
                </div>
            </section>
        </div>
        <section
            class="card form-card toggle-card {{ $showFormOnLoad ? 'is-visible' : '' }}"
            id="form-daftar-hadir"
            @if (!$showFormOnLoad) hidden aria-hidden="true" @endif
        >
            <div class="card-head">
                <div>
                    <h2>Form Permohonan Kunjungan Tamu Studi Tiru</h2>
                    <p>Lengkapi semua data permohonan kunjungan tamu</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            @if (session('guest_success'))
                <div class="alert alert-success">{{ session('guest_success') }}</div>
            @endif
            <div class="form-warning-popup" id="formWarningAlert" hidden aria-live="assertive" aria-atomic="true"></div>
            <div class="alert alert-success alert-loading" id="formLoadingAlert" hidden aria-live="polite" aria-busy="true">
                <span class="alert-loading-spinner" aria-hidden="true"></span>
                <span class="alert-loading-text">Permohonan kunjungan sedang dikirim, mohon tunggu...</span>
            </div>

            <form id="guestRequestForm" method="post" action="{{ route('guest.form.submit') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="grid-2">
                    <div class="field">
                        <label for="nama">Nama Pemohon</label>
                        <input id="nama" type="text" name="nama" value="{{ old('nama') }}" placeholder="Masukkan nama pemohon" required data-required-label="Nama Pemohon">
                    </div>
                    <div class="field">
                        <label for="instansi">Instansi</label>
                        <input id="instansi" type="text" name="instansi" value="{{ old('instansi') }}" placeholder="Nama instansi" required data-required-label="Instansi">
                    </div>
                    <div class="field">
                        <label for="instansi_provinsi">Asal Provinsi Instansi</label>
                        <select
                            id="instansi_provinsi"
                            name="instansi_provinsi"
                            required
                            data-required-label="Asal Provinsi Instansi"
                            data-old-value="{{ old('instansi_provinsi') }}"
                        >
                            <option value="">Pilih provinsi instansi</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="instansi_kabupaten_kota">Asal Kabupaten/Kota Instansi</label>
                        <select
                            id="instansi_kabupaten_kota"
                            name="instansi_kabupaten_kota"
                            required
                            data-required-label="Asal Kabupaten/Kota Instansi"
                            data-old-value="{{ old('instansi_kabupaten_kota') }}"
                            disabled
                        >
                            <option value="">Pilih kabupaten/kota instansi</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="nomor_surat">Nomor Surat Pemohon</label>
                        <input id="nomor_surat" type="text" name="nomor_surat" value="{{ old('nomor_surat') }}" placeholder="Contoh: 123/ABC/ST/2026" required data-required-label="Nomor Surat Pemohon">
                    </div>
                    <div class="field">
                        <label for="jabatan">Jabatan Pemohon</label>
                        <input id="jabatan" type="text" name="jabatan" value="{{ old('jabatan') }}" placeholder="Masukkan jabatan pemohon" required data-required-label="Jabatan Pemohon">
                    </div>
                    <div class="field">
                        <label for="telepon">Nomor WA Pemohon</label>
                        <input id="telepon" type="text" name="telepon" value="{{ old('telepon') }}" placeholder="08xxxxxxxxxx" required data-required-label="Nomor WA Pemohon">
                    </div>
                    <input type="hidden" name="tujuan" value="{{ old('tujuan', 'Kunjungan Studi Tiru') }}">
                    <div class="field">
                        <label for="tanggal_usulan">Tanggal Usulan</label>
                        <input id="tanggal_usulan" type="date" name="tanggal_usulan" value="{{ old('tanggal_usulan') }}" required data-required-label="Tanggal Usulan">
                    </div>
                    <div class="field">
                        <label for="jumlah_peserta">Jumlah Pemohon Kunjungan</label>
                        <input id="jumlah_peserta" type="number" name="jumlah_peserta" min="1" max="5000" value="{{ old('jumlah_peserta') }}" placeholder="Contoh: 8" required data-required-label="Jumlah Pemohon Kunjungan">
                    </div>
                    <div class="field">
                        <label for="lampiran">Lampiran (PDF/JPG/PNG)</label>
                        <input
                            id="lampiran"
                            type="file"
                            name="lampiran"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                            data-required-label="Lampiran"
                        >
                    </div>
                    <div class="field full">
                        <label for="keperluan">Perihal Kunjungan</label>
                        <textarea id="keperluan" name="keperluan" placeholder="Tulis perihal kunjungan" required data-required-label="Perihal Kunjungan">{{ old('keperluan') }}</textarea>
                    </div>
                </div>

                <div class="submit-row">
                    <button class="btn" type="submit">Kirim Permohonan</button>
                </div>
            </form>
        </section>

        <section
            class="card history-card toggle-card {{ $showHistoryOnLoad ? 'is-visible' : '' }}"
            id="riwayat-progress-tamu"
            @if (!$showHistoryOnLoad) hidden aria-hidden="true" @endif
        >
            <div class="card-head">
                <div>
                    <h2>Progress dan Daftar Riwayat Kunjungan Tamu</h2>
                </div>
                <span class="chip">{{ $recentKunjungan->count() }} data</span>
            </div>

            @if ($recentKunjungan->isEmpty())
                <div class="history-empty">
                    Belum ada riwayat kunjungan pada database.
                </div>
            @else
                <div class="history-list">
                    @foreach ($recentKunjungan as $row)
                        @php
                            $statusKey = strtolower(trim($row->status ?? 'pending'));
                            $workflowStepKey = strtolower(trim((string) ($row->workflow_step ?? '')));
                            $asalProvinsi = trim((string) ($row->instansi_provinsi ?? ''));
                            $asalKabupatenKota = trim((string) ($row->instansi_kabupaten_kota ?? ''));
                            $asalDaerah = '-';
                            if ($asalProvinsi !== '' || $asalKabupatenKota !== '') {
                                $asalDaerah = trim(
                                    ($asalKabupatenKota !== '' ? $asalKabupatenKota : '-') .
                                    ', ' .
                                    ($asalProvinsi !== '' ? $asalProvinsi : '-')
                                );
                            }
                            $perihalKunjungan = trim((string) ($row->keperluan ?? ''));
                            $tanggalPermohonan = $row->tanggal_usulan?->timezone('Asia/Jakarta')?->locale('id')->translatedFormat('d M Y')
                                ?? $row->created_at?->timezone('Asia/Jakarta')?->locale('id')->translatedFormat('d M Y')
                                ?? '-';
                            $hasBalasan = trim((string) ($row->balasan_data ?? '')) !== '' || trim((string) ($row->balasan_path ?? '')) !== '';
                            $isDitunda = $statusKey === 'ditunda' || (
                                $statusKey === 'selesai'
                                && $hasBalasan
                                && is_null($row->bidang_verified_at)
                                && trim((string) ($row->verification_note ?? '')) !== ''
                            );
                            $isSelesai = !$isDitunda && (
                                $hasBalasan
                                || in_array($statusKey, ['selesai', 'approved'], true)
                                || $workflowStepKey === 'selesai'
                            );
                            $isDisetujui = !$isSelesai && !$isDitunda && (
                                !is_null($row->bidang_verified_at)
                                || $statusKey === 'terkonfirmasi'
                            );
                            $currentStatus = $isSelesai
                                ? ['label' => 'SELESAI', 'class' => 'status-accepted']
                                : ($isDitunda
                                    ? ['label' => 'DITUNDA', 'class' => 'status-rescheduled']
                                    : ($isDisetujui
                                        ? ['label' => 'DISETUJUI', 'class' => 'status-accepted']
                                        : ['label' => 'DIPROSES', 'class' => 'status-progress']));
                            $balasanDisplayName = trim((string) ($row->balasan_nama ?? ''));
                            if ($balasanDisplayName === '') {
                                $balasanDisplayName = 'Surat Balasan #' . (int) $row->id;
                            }
                        @endphp
                        <article class="history-row history-row-simple">
                            <div class="history-field">
                                <p class="history-field-label">Instansi Pemohon</p>
                                <p class="history-field-value">{{ $row->instansi ?: '-' }}</p>
                            </div>

                            <div class="history-field">
                                <p class="history-field-label">Daerah Asal Pemohon</p>
                                <p class="history-field-value">{{ $asalDaerah }}</p>
                            </div>

                            <div class="history-field">
                                <p class="history-field-label">Tanggal Permohonan</p>
                                <p class="history-field-value">{{ $tanggalPermohonan }}</p>
                            </div>

                            <div class="history-field">
                                <p class="history-field-label">Perihal Kunjungan</p>
                                <p class="history-field-value">{{ $perihalKunjungan !== '' ? $perihalKunjungan : '-' }}</p>
                            </div>

                            <div class="history-field history-field-status">
                                <p class="history-field-label">Status Permohonan</p>
                                <span class="status-pill {{ $currentStatus['class'] }}">{{ $currentStatus['label'] }}</span>
                            </div>

                            <div class="history-field history-field-balasan">
                                <p class="history-field-label">Surat Balasan</p>
                                @if ($hasBalasan)
                                    <a
                                        class="history-reply-link"
                                        href="{{ route('guest.kunjungan.balasan.raw', ['tamu' => $row, 'v' => $faviconVersion]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Lihat surat balasan {{ $balasanDisplayName }}"
                                        title="{{ $balasanDisplayName }}"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-6Z" fill="currentColor" opacity=".35"/>
                                            <path d="M14 2v6h5" fill="currentColor"/>
                                            <path d="M9 13h6M9 16h6M9 10h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        </svg>
                                    </a>
                                @else
                                    <p class="history-field-value history-field-value-muted">-</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section
            class="card chart-guest-card toggle-card {{ $showChartOnLoad ? 'is-visible' : '' }}"
            id="statistik-kunjungan-tamu"
            @if (!$showChartOnLoad) hidden aria-hidden="true" @endif
        >
            <div class="card-head">
                <div>
                    <h2>Grafik Kunjungan Tamu</h2>
                </div>
            </div>
            <div class="chart-guest-grid">
                <article class="chart-guest-panel">
                    <h3>Kunjungan Tamu Studi Tiru Pertahun</h3>
                    <div id="chartKunjunganTahunanGuest" class="chart-guest-box"></div>
                </article>
            </div>
        </section>
    </div>

    <footer class="landing-footer" aria-labelledby="footerContactTitle">
        <div class="landing-footer-inner">
            <section class="landing-footer-about" aria-label="Tentang Inspektorat DKI Jakarta">
                <div class="landing-footer-brand">
                    <img class="landing-footer-brand-logo" src="{{ asset('images/logo-dki.png') }}" alt="Logo Inspektorat Provinsi DKI Jakarta">
                    <div class="landing-footer-brand-copy">
                        <strong>INSPEKTORAT</strong>
                        <span>PROVINSI DKI JAKARTA</span>
                    </div>
                </div>
                <p>
                    Inspektorat merupakan unsur pengawasan internal penyelenggaraan Pemerintahan Daerah
                    yang mempunyai tugas membantu Gubernur dalam membina dan mengawasi penyelenggaraan urusan pemerintahan
                    yang menjadi kewenangan Daerah dan tugas pembantuan oleh Perangkat Daerah, pengelolaan sumber daya daerah,
                    pengelolaan BUMD, pencegahan dan investigasi.
                </p>
            </section>

            <section class="landing-footer-contact" aria-label="Kontak Inspektorat">
                <h2 id="footerContactTitle">Kontak Kami</h2>
                <ul class="landing-footer-contact-list">
                    <li>
                        <span class="landing-footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M12 2.5c-3.6 0-6.5 2.9-6.5 6.5 0 4.8 6.5 12.4 6.5 12.4s6.5-7.6 6.5-12.4c0-3.6-2.9-6.5-6.5-6.5Zm0 8.9a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <span>Grha Ali Sadikin Blok G Lt. 17-18, Jl. Medan Merdeka Selatan No. 8-9, Jakarta Pusat 10110</span>
                    </li>
                    <li>
                        <span class="landing-footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M3.8 5.5A1.8 1.8 0 0 1 5.6 3.7h12.8a1.8 1.8 0 0 1 1.8 1.8v13a1.8 1.8 0 0 1-1.8 1.8H5.6a1.8 1.8 0 0 1-1.8-1.8v-13Zm1.8.3 6.3 4.7a1.4 1.4 0 0 0 1.7 0l6.3-4.7H5.6Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <a href="mailto:inspektorat@jakarta.go.id">inspektorat@jakarta.go.id</a>
                    </li>
                </ul>
            </section>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        (function setupThemeToggle() {
            const root = document.documentElement;
            const toggleBtn = document.getElementById('themeToggleBtn');
            if (!toggleBtn) return;

            const labelEl = toggleBtn.querySelector('.theme-toggle-label');

            const getCurrentTheme = () => (root.getAttribute('data-theme') === 'light' ? 'light' : 'dark');

            const animateThemeTransition = () => {
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                root.classList.remove('theme-transitioning');
                void root.offsetWidth;
                root.classList.add('theme-transitioning');
                window.setTimeout(() => root.classList.remove('theme-transitioning'), 360);
            };

            const applyTheme = (theme) => {
                const normalizedTheme = theme === 'light' ? 'light' : 'dark';
                const isLight = normalizedTheme === 'light';

                root.setAttribute('data-theme', normalizedTheme);
                toggleBtn.setAttribute('aria-pressed', isLight ? 'true' : 'false');
                toggleBtn.setAttribute('aria-label', isLight ? 'Aktifkan mode gelap' : 'Aktifkan mode terang');

                if (labelEl) {
                    labelEl.textContent = 'Mode';
                }
            };

            applyTheme(getCurrentTheme());

            toggleBtn.addEventListener('click', () => {
                const nextTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
                animateThemeTransition();
                applyTheme(nextTheme);

                try {
                    localStorage.setItem('sikuta_theme', nextTheme);
                } catch (error) {
                    // Ignore storage failures and keep theme for current page view.
                }
            });
        })();

        (function slideToSections() {
            const closeTimers = new WeakMap();
            const toggleSections = [
                document.getElementById('form-daftar-hadir'),
                document.getElementById('riwayat-progress-tamu'),
                document.getElementById('statistik-kunjungan-tamu'),
            ].filter(Boolean);

            const updateLandingScrollLock = () => {
                const hasVisibleSection = toggleSections.some((section) => {
                    return section.classList.contains('is-visible') && !section.hasAttribute('hidden');
                });

                document.body.classList.toggle('landing-no-scroll', !hasVisibleSection);
            };

            const clearCloseTimer = (target) => {
                const timerId = closeTimers.get(target);
                if (timerId) {
                    clearTimeout(timerId);
                    closeTimers.delete(target);
                }
            };

            const closeWithTransition = (target) => {
                clearCloseTimer(target);
                target.classList.remove('is-visible');
                target.classList.add('is-closing');
                target.setAttribute('aria-hidden', 'true');

                const finalizeClose = () => {
                    clearCloseTimer(target);
                    target.classList.remove('is-closing');
                    target.setAttribute('hidden', 'hidden');
                    updateLandingScrollLock();
                };

                const onTransitionEnd = (event) => {
                    if (event.target !== target || event.propertyName !== 'opacity') return;
                    target.removeEventListener('transitionend', onTransitionEnd);
                    finalizeClose();
                };

                target.addEventListener('transitionend', onTransitionEnd);

                const fallbackTimer = setTimeout(() => {
                    target.removeEventListener('transitionend', onTransitionEnd);
                    finalizeClose();
                }, 360);
                closeTimers.set(target, fallbackTimer);
            };

            const openWithTransition = (target) => {
                clearCloseTimer(target);
                target.classList.remove('is-closing');
                if (target.hasAttribute('hidden')) {
                    target.removeAttribute('hidden');
                }

                requestAnimationFrame(() => {
                    target.classList.add('is-visible');
                    target.removeAttribute('aria-hidden');
                    updateLandingScrollLock();
                });
            };

            const bindToggleButton = (buttonId, targetId) => {
                const btn = document.getElementById(buttonId);
                const target = document.getElementById(targetId);
                if (!btn || !target) return;

                btn.addEventListener('click', () => {
                    const isVisible = target.classList.contains('is-visible') && !target.hasAttribute('hidden');

                    if (isVisible) {
                        closeWithTransition(target);
                        return;
                    }

                    openWithTransition(target);
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    target.classList.add('slide-focus');
                    setTimeout(() => target.classList.remove('slide-focus'), 900);
                });
            };

            bindToggleButton('btnDaftarHadir', 'form-daftar-hadir');
            bindToggleButton('btnRiwayatProgress', 'riwayat-progress-tamu');
            bindToggleButton('btnStatistikKunjungan', 'statistik-kunjungan-tamu');
            updateLandingScrollLock();
        })();

        (function setupButtonClickTransitions() {
            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const interactiveButtons = Array.from(document.querySelectorAll('.hero-cta-btn, .admin-link'));

            const playClickFeedback = (element) => {
                if (prefersReducedMotion) return;
                element.classList.remove('is-clicked');
                void element.offsetWidth;
                element.classList.add('is-clicked');
                window.setTimeout(() => element.classList.remove('is-clicked'), 360);
            };

            interactiveButtons.forEach((element) => {
                element.addEventListener('click', () => {
                    playClickFeedback(element);
                });
            });
        })();

        (function resetLandingVisualState() {
            const clearLeavingState = () => {
                document.body.classList.remove('page-leaving');
            };

            clearLeavingState();
            window.addEventListener('pageshow', clearLeavingState);
        })();

        (function popupLoginEntry() {
            const loginLink = document.querySelector('.admin-link');
            const modal = document.getElementById('loginEntryModal');
            const btnCancel = document.getElementById('btnCancelLoginEntry');
            const btnConfirm = document.getElementById('btnConfirmLoginEntry');
            if (!(loginLink instanceof HTMLAnchorElement) || !modal || !btnCancel || !btnConfirm) return;
            let closeTimer = null;
            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const syncModalScrollLock = () => {
                const hasOpenModal = !!document.querySelector('.alur-modal.is-open, .login-entry-modal.is-open');
                document.body.classList.toggle('has-modal-open', hasOpenModal);
            };

            const openModal = () => {
                if (closeTimer) {
                    window.clearTimeout(closeTimer);
                    closeTimer = null;
                }
                modal.classList.remove('is-closing');
                modal.removeAttribute('hidden');
                modal.classList.add('is-open');
                modal.removeAttribute('aria-hidden');
                syncModalScrollLock();
            };

            const closeModal = (immediate = false) => {
                if (closeTimer) {
                    window.clearTimeout(closeTimer);
                    closeTimer = null;
                }

                if (immediate) {
                    modal.classList.remove('is-open', 'is-closing');
                    modal.setAttribute('hidden', 'hidden');
                    modal.setAttribute('aria-hidden', 'true');
                    syncModalScrollLock();
                    return;
                }

                modal.classList.remove('is-open');
                modal.classList.add('is-closing');
                const closeDuration = prefersReducedMotion ? 0 : 240;
                closeTimer = window.setTimeout(() => {
                    modal.classList.remove('is-closing');
                    modal.setAttribute('hidden', 'hidden');
                    modal.setAttribute('aria-hidden', 'true');
                    syncModalScrollLock();
                    closeTimer = null;
                }, closeDuration);
            };

            loginLink.addEventListener('click', (event) => {
                if (
                    event.defaultPrevented ||
                    event.button !== 0 ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey
                ) {
                    return;
                }

                event.preventDefault();
                openModal();
            });

            btnCancel.addEventListener('click', closeModal);

            btnConfirm.addEventListener('click', () => {
                closeModal(true);
                document.body.classList.add('page-leaving');
                window.setTimeout(() => {
                    window.location.assign(loginLink.href);
                }, 220);
            });

            modal.addEventListener('click', (event) => {
                const target = event.target;
                if (target instanceof HTMLElement && target.dataset.closeLoginEntry === '1') {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();

        (function renderGuestCharts() {
            const yearlyChartContainer = document.getElementById('chartKunjunganTahunanGuest');
            const chartSection = document.getElementById('statistik-kunjungan-tamu');
            const chartButton = document.getElementById('btnStatistikKunjungan');
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            if (!yearlyChartContainer || !chartSection || !chartButton) return;

            const yearLabels = @json($chartYearLabels ?? []);
            const yearSeries = @json($chartYearSeries ?? []);

            const yearlyHasData = Array.isArray(yearSeries) && yearSeries.some((value) => Number(value) > 0);

            let yearlyChart = null;

            const getChartThemeOptions = () => {
                const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                return {
                    legendLabelColor: isLight ? '#334155' : '#dbeafe',
                    axisLabelColor: isLight ? '#475569' : '#bfd6ef',
                    axisTitleColor: isLight ? '#1e293b' : '#e2efff',
                    gridBorderColor: isLight ? 'rgba(148, 163, 184, 0.38)' : 'rgba(148, 163, 184, 0.28)',
                    noDataColor: isLight ? '#475569' : '#dbeafe',
                    tooltipTheme: isLight ? 'light' : 'dark',
                };
            };

            const applyChartTheme = () => {
                if (!yearlyChart) return;

                const themeOptions = getChartThemeOptions();
                yearlyChart.updateOptions({
                    legend: {
                        labels: {
                            colors: themeOptions.legendLabelColor,
                        },
                    },
                    xaxis: {
                        labels: {
                            style: {
                                colors: themeOptions.axisLabelColor,
                            },
                        },
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: themeOptions.axisLabelColor,
                            },
                        },
                        title: {
                            style: {
                                color: themeOptions.axisTitleColor,
                            },
                        },
                    },
                    grid: {
                        borderColor: themeOptions.gridBorderColor,
                    },
                    tooltip: {
                        theme: themeOptions.tooltipTheme,
                    },
                    noData: {
                        style: {
                            colors: themeOptions.noDataColor,
                        },
                    },
                }, false, true, false);
            };

            const createCharts = () => {
                if (typeof ApexCharts === 'undefined') return;

                if (!yearlyChart) {
                    const themeOptions = getChartThemeOptions();
                    yearlyChart = new ApexCharts(yearlyChartContainer, {
                        chart: {
                            type: 'line',
                            height: 340,
                            toolbar: { show: true },
                        },
                        series: yearlyHasData
                            ? [
                                {
                                    name: 'Jumlah Kunjungan',
                                    type: 'column',
                                    data: yearSeries,
                                },
                                {
                                    name: 'Tren Kunjungan',
                                    type: 'line',
                                    data: yearSeries,
                                },
                            ]
                            : [],
                        colors: ['#14b8a6', '#60a5fa'],
                        stroke: {
                            width: [0, 3],
                            curve: 'smooth',
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 8,
                                columnWidth: '46%',
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        markers: {
                            size: 4,
                            strokeWidth: 2,
                            strokeColors: '#0b1f4a',
                            hover: {
                                sizeOffset: 2,
                            },
                        },
                        fill: {
                            opacity: [0.9, 1],
                        },
                        xaxis: {
                            categories: yearlyHasData ? yearLabels : [],
                            labels: {
                                style: {
                                    colors: themeOptions.axisLabelColor,
                                },
                            },
                            axisBorder: {
                                color: themeOptions.gridBorderColor,
                            },
                            axisTicks: {
                                color: themeOptions.gridBorderColor,
                            },
                        },
                        yaxis: {
                            min: 0,
                            forceNiceScale: true,
                            tickAmount: 5,
                            labels: {
                                formatter: (value) => Number(value).toFixed(0),
                                style: {
                                    colors: themeOptions.axisLabelColor,
                                },
                            },
                            title: {
                                text: 'Jumlah Kunjungan',
                                style: {
                                    color: themeOptions.axisTitleColor,
                                    fontWeight: 700,
                                },
                            },
                        },
                        grid: {
                            borderColor: themeOptions.gridBorderColor,
                            strokeDashArray: 4,
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: themeOptions.legendLabelColor,
                            },
                        },
                        tooltip: {
                            enabledOnSeries: [0],
                            shared: true,
                            intersect: false,
                            theme: themeOptions.tooltipTheme,
                            y: {
                                formatter: (value) => `${value} kunjungan`,
                            },
                        },
                        noData: {
                            text: 'Belum ada data kunjungan tahunan',
                            style: {
                                colors: themeOptions.noDataColor,
                            },
                        },
                    });

                    yearlyChart.render();
                }
            };

            const ensureChartVisible = () => {
                if (!chartSection.classList.contains('is-visible') || chartSection.hasAttribute('hidden')) return;
                createCharts();
                if (yearlyChart) yearlyChart.resize();
            };

            chartButton.addEventListener('click', () => {
                setTimeout(ensureChartVisible, 60);
            });

            window.addEventListener('resize', () => {
                if (yearlyChart) yearlyChart.resize();
            });

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    setTimeout(() => {
                        applyChartTheme();
                    }, 0);
                });
            }

            if (chartSection.classList.contains('is-visible') && !chartSection.hasAttribute('hidden')) {
                createCharts();
            }
        })();

        (function popupAlurPenggunaan() {
            const btnOpen = document.getElementById('btnAlurPenggunaan');
            const btnClose = document.getElementById('btnCloseAlur');
            const modal = document.getElementById('alurPenggunaanModal');
            if (!btnOpen || !btnClose || !modal) return;

            const syncModalScrollLock = () => {
                const hasOpenModal = !!document.querySelector('.alur-modal.is-open, .login-entry-modal.is-open');
                document.body.classList.toggle('has-modal-open', hasOpenModal);
            };

            const openModal = () => {
                modal.removeAttribute('hidden');
                modal.classList.add('is-open');
                modal.removeAttribute('aria-hidden');
                syncModalScrollLock();
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('hidden', 'hidden');
                modal.setAttribute('aria-hidden', 'true');
                syncModalScrollLock();
            };

            btnOpen.addEventListener('click', () => {
                if (modal.classList.contains('is-open')) {
                    closeModal();
                } else {
                    openModal();
                }
            });

            btnClose.addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                const target = event.target;
                if (target instanceof HTMLElement && target.dataset.closeAlur === '1') {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();

        (function makeDateInputFullyClickable() {
            const dateInputs = Array.from(document.querySelectorAll('input[type="date"]'));
            if (!dateInputs.length) return;

            dateInputs.forEach((input) => {
                if (!(input instanceof HTMLInputElement)) return;

                input.addEventListener('click', () => {
                    if (input.disabled || input.readOnly || typeof input.showPicker !== 'function') {
                        return;
                    }

                    try {
                        input.showPicker();
                    } catch (error) {
                        // Browser menolak showPicker pada kondisi tertentu. Fokus biasa tetap berjalan.
                    }
                });
            });
        })();

        (function validateGuestRequestForm() {
            const form = document.getElementById('guestRequestForm');
            const warningEl = document.getElementById('formWarningAlert');
            const loadingEl = document.getElementById('formLoadingAlert');
            const submitBtn = form instanceof HTMLFormElement
                ? form.querySelector('button[type="submit"]')
                : null;
            if (!form || !warningEl) return;

            const requiredInputs = Array.from(form.querySelectorAll('[data-required-label]'));
            if (requiredInputs.length === 0) return;
            const defaultSubmitLabel = submitBtn instanceof HTMLButtonElement
                ? String(submitBtn.textContent || '').trim()
                : 'Kirim Permohonan';
            const loadingSubmitLabel = 'Mengirim Permohonan...';
            let isSubmitting = false;
            let submitResetTimer = null;

            const isFilled = (el) => {
                if (el instanceof HTMLInputElement && el.type === 'file') {
                    return (el.files?.length ?? 0) > 0;
                }

                return String(el.value ?? '').trim() !== '';
            };

            const setInvalidState = (el, invalid) => {
                el.classList.toggle('field-invalid', invalid);
                el.setAttribute('aria-invalid', invalid ? 'true' : 'false');
            };

            const getMissing = () => requiredInputs.filter((el) => !isFilled(el));

            let hideWarningTimer = null;

            const clearSubmitResetTimer = () => {
                if (submitResetTimer) {
                    window.clearTimeout(submitResetTimer);
                    submitResetTimer = null;
                }
            };

            const setSubmittingState = (active) => {
                isSubmitting = active;
                form.classList.toggle('is-submitting', active);

                if (submitBtn instanceof HTMLButtonElement) {
                    submitBtn.disabled = active;
                    submitBtn.setAttribute('aria-busy', active ? 'true' : 'false');
                    submitBtn.textContent = active ? loadingSubmitLabel : defaultSubmitLabel;
                }
            };

            const hideLoading = () => {
                if (!(loadingEl instanceof HTMLElement)) return;
                loadingEl.classList.remove('is-visible');
                loadingEl.hidden = true;
                loadingEl.setAttribute('aria-hidden', 'true');
            };

            const showLoading = () => {
                if (!(loadingEl instanceof HTMLElement)) return;
                loadingEl.hidden = false;
                loadingEl.removeAttribute('aria-hidden');
                requestAnimationFrame(() => {
                    loadingEl.classList.add('is-visible');
                });
            };

            const clearHideWarningTimer = () => {
                if (hideWarningTimer) {
                    window.clearTimeout(hideWarningTimer);
                    hideWarningTimer = null;
                }
            };

            const hideWarning = (immediate = false) => {
                clearHideWarningTimer();

                if (immediate) {
                    warningEl.classList.remove('is-visible', 'is-hiding');
                    warningEl.hidden = true;
                    warningEl.textContent = '';
                    return;
                }

                if (warningEl.hidden || warningEl.classList.contains('is-hiding')) {
                    return;
                }

                warningEl.classList.remove('is-visible');
                warningEl.classList.add('is-hiding');
                window.setTimeout(() => {
                    if (!warningEl.classList.contains('is-hiding')) return;
                    warningEl.classList.remove('is-hiding');
                    warningEl.hidden = true;
                    warningEl.textContent = '';
                }, 280);
            };

            const showWarning = (message) => {
                hideWarning(true);

                const iconEl = document.createElement('span');
                iconEl.className = 'form-warning-popup-icon';
                iconEl.setAttribute('aria-hidden', 'true');
                iconEl.textContent = '!';

                const textEl = document.createElement('p');
                textEl.className = 'form-warning-popup-text';
                textEl.textContent = message;

                warningEl.append(iconEl, textEl);
                warningEl.hidden = false;

                requestAnimationFrame(() => {
                    warningEl.classList.add('is-visible');
                });

                hideWarningTimer = window.setTimeout(() => {
                    hideWarning(false);
                }, 5200);
            };

            hideLoading();

            form.addEventListener('submit', (event) => {
                if (isSubmitting) {
                    event.preventDefault();
                    return;
                }

                const missing = getMissing();
                requiredInputs.forEach((el) => setInvalidState(el, false));

                if (missing.length === 0) {
                    hideWarning(true);
                    setSubmittingState(true);
                    showLoading();
                    clearSubmitResetTimer();
                    submitResetTimer = window.setTimeout(() => {
                        setSubmittingState(false);
                        hideLoading();
                    }, 18000);
                    return;
                }

                event.preventDefault();
                setSubmittingState(false);
                hideLoading();
                clearSubmitResetTimer();
                missing.forEach((el) => setInvalidState(el, true));

                const labels = missing.map((el) => el.getAttribute('data-required-label') || 'Kolom wajib');
                showWarning(`Lengkapi: ${labels.join(', ')}.`);

                const first = missing[0];
                first.focus({ preventScroll: true });
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            requiredInputs.forEach((el) => {
                const eventName =
                    (el instanceof HTMLInputElement && el.type === 'file') || el instanceof HTMLSelectElement
                        ? 'change'
                        : 'input';
                el.addEventListener(eventName, () => {
                    if (isFilled(el)) {
                        setInvalidState(el, false);
                    }

                    if (getMissing().length === 0) {
                        hideWarning(true);
                    }
                });
            });

            window.addEventListener('pageshow', () => {
                setSubmittingState(false);
                hideLoading();
                clearSubmitResetTimer();
            });
        })();

        (function hydrateWilayahSelects() {
            const provinceSelect = document.getElementById('instansi_provinsi');
            const regencySelect = document.getElementById('instansi_kabupaten_kota');
            if (!(provinceSelect instanceof HTMLSelectElement) || !(regencySelect instanceof HTMLSelectElement)) return;

            const oldProvinceValue = (provinceSelect.dataset.oldValue || '').trim();
            const oldRegencyValue = (regencySelect.dataset.oldValue || '').trim();
            const datasetUrl = @json(asset('data/wilayah/indonesia-region.json'));

            const normalize = (value) => String(value || '').trim().toUpperCase();

            const createOption = (value, label) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                return option;
            };

            const resetRegency = (placeholder = 'Pilih kabupaten/kota instansi') => {
                regencySelect.innerHTML = '';
                regencySelect.appendChild(createOption('', placeholder));
                regencySelect.value = '';
                regencySelect.disabled = true;
            };

            const findOptionByValue = (selectEl, targetValue) => {
                const normalizedTarget = normalize(targetValue);
                return Array.from(selectEl.options).find((option) => normalize(option.value) === normalizedTarget) || null;
            };

            const getSelectedProvinceId = () => {
                const selected = provinceSelect.options[provinceSelect.selectedIndex];
                return selected ? String(selected.dataset.id || '') : '';
            };

            const renderRegencies = (regenciesByProvince, provinceId, selectedRegencyValue = '') => {
                const regencies = Array.isArray(regenciesByProvince[provinceId]) ? regenciesByProvince[provinceId] : [];
                resetRegency();

                if (!provinceId || regencies.length === 0) {
                    return;
                }

                regencies.forEach((row) => {
                    const regencyName = String(row?.name || '').trim();
                    if (!regencyName) return;
                    regencySelect.appendChild(createOption(regencyName, regencyName));
                });

                regencySelect.disabled = false;

                if (selectedRegencyValue) {
                    const selected = findOptionByValue(regencySelect, selectedRegencyValue);
                    if (selected) {
                        regencySelect.value = selected.value;
                    }
                }
            };

            fetch(datasetUrl, { cache: 'force-cache' })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Gagal mengambil data wilayah');
                    }

                    return response.json();
                })
                .then((payload) => {
                    const provinces = Array.isArray(payload?.provinces) ? payload.provinces : [];
                    const regenciesByProvince = payload?.regencies_by_province && typeof payload.regencies_by_province === 'object'
                        ? payload.regencies_by_province
                        : {};

                    provinceSelect.innerHTML = '';
                    provinceSelect.appendChild(createOption('', 'Pilih provinsi instansi'));

                    provinces.forEach((row) => {
                        const provinceId = String(row?.id || '').trim();
                        const provinceName = String(row?.name || '').trim();
                        if (!provinceId || !provinceName) return;

                        const option = createOption(provinceName, provinceName);
                        option.dataset.id = provinceId;
                        provinceSelect.appendChild(option);
                    });

                    if (oldProvinceValue) {
                        const oldProvinceOption = findOptionByValue(provinceSelect, oldProvinceValue);
                        if (oldProvinceOption) {
                            provinceSelect.value = oldProvinceOption.value;
                        }
                    }

                    renderRegencies(regenciesByProvince, getSelectedProvinceId(), oldRegencyValue);

                    provinceSelect.addEventListener('change', () => {
                        renderRegencies(regenciesByProvince, getSelectedProvinceId());
                    });
                })
                .catch(() => {
                    provinceSelect.innerHTML = '';
                    provinceSelect.appendChild(createOption('', 'Data provinsi gagal dimuat'));
                    resetRegency('Data kabupaten/kota gagal dimuat');
                });
        })();

        (function sanitizeHistoryLegacyMarkup() {
            const historySection = document.getElementById('riwayat-progress-tamu');
            if (!historySection) return;

            historySection.querySelectorAll('.history-row-simple').forEach((row) => {
                const fields = Array.from(row.querySelectorAll('.history-field'));
                const nameField = fields.find((field) => {
                    const label = field.querySelector('.history-field-label');
                    return label && label.textContent && label.textContent.trim().toLowerCase() === 'nama pemohon';
                });

                if (nameField) {
                    nameField.remove();
                }
            });

            const chip = historySection.querySelector('.chip');
            if (chip && chip.textContent) {
                chip.textContent = chip.textContent.replace(/data terbaru/gi, 'data');
            }
        })();
    </script>
</body>
</html>
