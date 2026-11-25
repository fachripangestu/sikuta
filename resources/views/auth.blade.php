<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Sistem Kunjungan Tamu Studi Tiru Inspektorat DKI Jakarta</title>
    @php
        $faviconPath = public_path('images/logo-sikuta-full.png');
        $faviconVersion = file_exists($faviconPath) ? (string) filemtime($faviconPath) : (string) time();
    @endphp
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css?v=20260414') }}">
</head>
<body>
    @php
        $toastMessage = session('guest_success');
    @endphp
    <div id="toast" class="toast">{{ $toastMessage }}</div>

    <div class="shell">
        <div class="header-brand">
            <img class="badge-logo" src="{{ asset('images/logo-dki-jaya-raya.png') }}" alt="Logo DKI Jakarta">
            <div class="brand-meta">
                <div class="brand-title">SI KUTA</div>
                <small class="brand-subtitle">Sistem Kunjungan Tamu Studi Tiru Inspektorat DKI Jakarta</small>
            </div>
            <button class="theme-toggle push-right" id="themeToggleAuth" type="button">Tema</button>
        </div>

        <div class="card">
            <div class="tabs">
                <button class="tab active" data-target="#pane-pengelola" type="button">Pengelola</button>
                <button class="tab" data-target="#pane-tamu" type="button">Tamu</button>
            </div>

            {{-- Alert area shared --}}
            @if($errors->any())
                <div class="alert alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Pengelola --}}
            <div class="pane active" id="pane-pengelola">
                <h1>Masuk Pengelola</h1>
                <p class="sub">Kelola jadwal, tamu, dan laporan kunjungan.</p>
                <form method="post" action="{{ url('/login') }}">
                    @csrf
                    <label for="admin-email">Email</label>
                    <input id="admin-email" name="email" type="email" placeholder="admin@domain.com" value="{{ old('email') }}" required>

                    <label for="admin-password">Kata sandi</label>
                    <input id="admin-password" name="password" type="password" placeholder="••••••••" required>

                    <div class="mt-14">
                        <button class="btn" type="submit">Masuk</button>
                    </div>
                </form>
            </div>

            {{-- Tamu --}}
            <div class="pane" id="pane-tamu">
                <h1>Masuk Tamu</h1>
                <p class="sub">Masuk cukup dengan kode kunjungan tanpa email / password.</p>
                <form method="post" action="{{ route('guest.form.submit') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid-2">
                        <div>
                            <label>Nama</label>
                            <input type="text" name="nama" required>
                        </div>
                        <div>
                            <label>Instansi</label>
                            <input type="text" name="instansi" required>
                        </div>
                        <div>
                            <label>Jabatan</label>
                            <input type="text" name="jabatan" required>
                        </div>
                        <div>
                            <label>No. Telepon</label>
                            <input type="text" name="telepon" required>
                        </div>
                        <div>
                            <label>Perihal</label>
                            <input type="text" name="tujuan" required>
                        </div>
                        <div>
                            <label>Lampiran (PDF/JPG/PNG)</label>
                            <input type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="mt-12">
                        <label>Perihal Kunjungan</label>
                        <textarea name="keperluan" placeholder="Tulis perihal kunjungan" required></textarea>
                    </div>
                    <div class="actions mt-14">
                        <button class="btn" type="submit">Kirim</button>
                    </div>
                </form>
            </div>

            <div class="footer-link">
                Belum punya akun pengelola? <a href="#">Hubungi admin</a>
            </div>
        </div>
    </div>

    <script>
        const tabs = document.querySelectorAll('.tab');
        const panes = document.querySelectorAll('.pane');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.querySelector(tab.dataset.target)?.classList.add('active');
            });
        });

        (function showToast() {
            const toast = document.getElementById('toast');
            if (!toast || !toast.textContent.trim()) return;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 4000);
        })();

        (function themeToggleAuth() {
            const btn = document.getElementById('themeToggleAuth');
            if (!btn) return;
            const root = document.documentElement;
            const body = document.body;
            const animateThemeTransition = () => {
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                root.classList.remove('theme-transitioning');
                void root.offsetWidth;
                root.classList.add('theme-transitioning');
                window.setTimeout(() => root.classList.remove('theme-transitioning'), 360);
            };
            const applyTheme = (mode) => {
                if (mode === 'light') {
                    root.setAttribute('data-theme', 'light');
                    body.setAttribute('data-theme', 'light');
                    localStorage.setItem('theme', 'light');
                    btn.textContent = 'Tema Gelap';
                } else {
                    root.removeAttribute('data-theme');
                    body.removeAttribute('data-theme');
                    localStorage.removeItem('theme');
                    btn.textContent = 'Tema Terang';
                }
            };
            const stored = localStorage.getItem('theme');
            applyTheme(stored === 'light' ? 'light' : 'light');
            btn.addEventListener('click', () => {
                const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                animateThemeTransition();
                applyTheme(next);
            });
        })();
    </script>
</body>
</html>
