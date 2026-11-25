<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Akun Admin - SIKUTA</title>
    @php
        $faviconPath = public_path('images/logo-sikuta-full.png');
        $faviconVersion = file_exists($faviconPath) ? (string) filemtime($faviconPath) : (string) time();
        $kunjunganCssPath = public_path('css/kunjungan.css');
        $kunjunganCssVersion = file_exists($kunjunganCssPath) ? (string) filemtime($kunjunganCssPath) : (string) time();
        $kunjunganCssInline = file_exists($kunjunganCssPath) ? file_get_contents($kunjunganCssPath) : '';
    @endphp
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-sikuta-full.png') }}?v={{ $faviconVersion }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
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
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('css/kunjungan.css') }}?v={{ $kunjunganCssVersion }}">
    @if (!empty($kunjunganCssInline))
        <style id="kunjungan-inline-css">{!! $kunjunganCssInline !!}</style>
    @endif
</head>
<body>
    @php
        $dbDownAdmin = $dbDownAdmin ?? false;
        $accountsByRole = $accountsByRole ?? collect();
        $canManageAccounts = $canManageAccounts ?? false;
        $roleLabels = $roleLabels ?? [];
        $currentAdminId = (int) ($adminUser->id ?? 0);
        $headerProfilePhotoPath = trim((string) ($adminUser?->profile_photo_path ?? ''));
        $headerProfilePhotoVersion = ($headerProfilePhotoPath !== '' && file_exists(public_path($headerProfilePhotoPath)))
            ? (string) filemtime(public_path($headerProfilePhotoPath))
            : (string) (file_exists(public_path('images/user-avatar.svg')) ? filemtime(public_path('images/user-avatar.svg')) : time());
        $headerProfilePhotoUrl = $headerProfilePhotoPath !== '' && file_exists(public_path($headerProfilePhotoPath))
            ? asset($headerProfilePhotoPath) . '?v=' . $headerProfilePhotoVersion
            : asset('images/user-avatar.svg') . '?v=' . $headerProfilePhotoVersion;
        $homeRouteName = match ($adminRole) {
            'admin' => 'admin.accounts',
            'data_entry' => 'admin.dashboard.data_entry',
            'sekretaris_pimpinan' => 'admin.dashboard.sekretaris_pimpinan',
            'bidang_tujuan' => 'admin.dashboard.bidang_tujuan',
            'subbag_umum' => 'admin.dashboard.subbag_umum',
            default => 'admin.dashboard',
        };
    @endphp

    <div class="dashboard-shell">
        <main class="main-area">
            <header class="headnav">
                <div class="headnav-left">
                    <a class="headnav-brand headnav-brand-link" href="{{ route($homeRouteName) }}" aria-label="Refresh halaman">
                        <img
                            class="headnav-logo"
                            src="{{ asset('images/logo-sikuta-full.png') }}?v={{ file_exists(public_path('images/logo-sikuta-full.png')) ? filemtime(public_path('images/logo-sikuta-full.png')) : time() }}"
                            alt="Logo SIKUTA"
                        >
                        <div class="headnav-brand-text">
                            <strong>SIKUTA</strong>
                            <small>Sistem Informasi Kunjungan Tamu</small>
                            <small class="headnav-live-time headnav-live-time-brand" id="headnavLiveTime">{{ now()->timezone('Asia/Jakarta')->format('d M Y H:i:s') }} WIB</small>
                        </div>
                    </a>
                </div>
                <div class="headnav-actions">
                    <div class="headnav-action-stack">
                        <livewire:admin-notifications :role="$adminRole" />
                        <button class="headnav-btn headnav-theme-toggle" id="adminThemeToggleBtn" type="button" aria-pressed="false" aria-label="Aktifkan mode terang">
                            <span class="headnav-theme-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M12 4.5a.9.9 0 0 1 .9.9V7a.9.9 0 0 1-1.8 0V5.4a.9.9 0 0 1 .9-.9Zm0 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.5-3.5a.9.9 0 0 1-.9.9H17a.9.9 0 0 1 0-1.8h1.6a.9.9 0 0 1 .9.9Zm-13.6 0a.9.9 0 0 1-.9.9H3.4a.9.9 0 1 1 0-1.8H5a.9.9 0 0 1 .9.9Zm9.16-5.56a.9.9 0 0 1 1.27 0l1.13 1.13a.9.9 0 1 1-1.28 1.28L15.06 7.7a.9.9 0 0 1 0-1.27Zm-7.12 7.12a.9.9 0 0 1 1.27 0 .9.9 0 0 1 0 1.27l-1.13 1.13a.9.9 0 0 1-1.27-1.28l1.13-1.12Zm8.4 2.22a.9.9 0 0 1 1.27 0l1.13 1.13a.9.9 0 1 1-1.28 1.27l-1.12-1.13a.9.9 0 0 1 0-1.27Zm-8.4-8.4a.9.9 0 0 1 0 1.28L6.8 9.8a.9.9 0 1 1-1.27-1.27l1.12-1.13a.9.9 0 0 1 1.28 0ZM12 17a.9.9 0 0 1 .9.9v1.6a.9.9 0 0 1-1.8 0v-1.6a.9.9 0 0 1 .9-.9Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span class="headnav-theme-label">Mode</span>
                        </button>
                    </div>
                    <details class="headnav-account-menu">
                        <summary class="headnav-account-trigger" aria-label="Buka menu akun admin">
                            <span class="headnav-account-avatar">
                                <img
                                    src="{{ $headerProfilePhotoUrl }}"
                                    alt="Profil admin"
                                >
                            </span>
                        </summary>
                        <div class="headnav-account-panel">
                            <div class="headnav-account-panel-user">
                                <strong>{{ $adminUserName }}</strong>
                                <small>{{ $activeRoleLabel }}</small>
                            </div>
                            @if ($canManageAccounts)
                                <a class="headnav-account-item is-active" href="{{ route('admin.accounts') }}">Manajemen Akun</a>
                            @endif
                            <a class="headnav-account-item" href="{{ route('admin.profile') }}">Edit Profil</a>
                            <form method="post" action="{{ route('admin.logout') }}" class="headnav-account-form">
                                @csrf
                                <button class="headnav-account-item headnav-account-item-logout" type="submit">Logout</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>
            @if ($dbDownAdmin || ($notificationSnapshot['db_down'] ?? false))
                <div class="notice error">Koneksi database belum aktif. Jalankan MySQL di XAMPP agar manajemen akun bisa digunakan.</div>
            @endif

            @if (session('admin_notice'))
                <div class="notice success">{{ session('admin_notice') }}</div>
            @endif

            @if ($errors->any())
                <div class="notice error">{{ $errors->first() }}</div>
            @endif

            <section class="content-grid account-grid">
                <section class="card table-card">
                    <div class="card-head">
                        <div>
                            <h3>Tambah Akun</h3>
                            <p>Buat akun admin baru untuk proses verifikasi berjenjang.</p>
                        </div>
                    </div>

                    @if (!$canManageAccounts)
                        <div class="empty">Hanya Admin yang dapat menambah, reset, nonaktifkan, dan hapus akun.</div>
                    @else
                        <form method="post" action="{{ route('admin.accounts.store') }}" class="account-form">
                            @csrf
                            <div class="account-field">
                                <label for="account-username">Username</label>
                                <input id="account-username" type="text" name="username" value="{{ old('username') }}" required>
                            </div>
                            <div class="account-field">
                                <label for="account-name">Nama Tampilan</label>
                                <input id="account-name" type="text" name="name" value="{{ old('name') }}" required>
                            </div>
                            <div class="account-field">
                                <label for="account-role">Role</label>
                                <select id="account-role" name="role" required>
                                    @foreach ($roleLabels as $roleKey => $roleLabel)
                                        <option value="{{ $roleKey }}" @selected(old('role') === $roleKey)>{{ $roleLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="account-field">
                                <label for="account-password">Password</label>
                                <input id="account-password" type="password" name="password" required>
                            </div>
                            <div class="account-actions">
                                <button class="action-btn approve" type="submit">Buat Akun</button>
                            </div>
                        </form>
                    @endif
                </section>

                <section class="card table-card">
                    <div class="card-head">
                        <div>
                            <h3>Daftar Akun</h3>
                            <p>Total akun: {{ $accountsByRole->flatten(1)->count() }}. Atur status, reset password, atau hapus akun.</p>
                        </div>
                    </div>

                    @if ($accountsByRole->isEmpty())
                        <div class="empty">Belum ada akun admin yang tersimpan.</div>
                    @else
                        <div class="account-list-wrap">
                            @foreach ($accountsByRole as $role => $items)
                                <div class="account-group">
                                    <div class="account-group-title">{{ $roleLabels[$role] ?? ucfirst(str_replace('_', ' ', (string) $role)) }}</div>
                                    <table class="account-table">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Nama</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $item)
                                                @php
                                                    $isSelf = (int) $item->id === $currentAdminId;
                                                @endphp
                                                <tr>
                                                    <td>{{ $item->username ?: '-' }}</td>
                                                    <td>{{ $item->name ?: '-' }}</td>
                                                    <td>
                                                        <span class="status {{ $item->is_active ? 'approved' : 'rejected' }}">
                                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="account-row-actions">
                                                            @if ($canManageAccounts)
                                                                <form method="post" action="{{ route('admin.accounts.toggle_status', $item) }}">
                                                                    @csrf
                                                                    <button class="account-btn warn" type="submit" @disabled($isSelf)>
                                                                        {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                                    </button>
                                                                </form>
                                                                <form method="post" action="{{ route('admin.accounts.reset_password', $item) }}">
                                                                    @csrf
                                                                    <button class="account-btn" type="submit">Reset</button>
                                                                </form>
                                                                <form method="post" action="{{ route('admin.accounts.delete', $item) }}" onsubmit="return confirm('Hapus akun {{ $item->username }}?');">
                                                                    @csrf
                                                                    <button class="account-btn danger" type="submit" @disabled($isSelf)>Hapus</button>
                                                                </form>
                                                            @else
                                                                <span class="table-action-muted">Read only</span>
                                                            @endif
                                                            @if ($isSelf)
                                                                <span class="self-chip">Akun Anda</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </section>
        </main>
    </div>
    <script>
        (function setupAdminThemeToggle() {
            const root = document.documentElement;
            const toggleBtn = document.getElementById('adminThemeToggleBtn');
            if (!toggleBtn) return;

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
            };

            applyTheme(getCurrentTheme());

            toggleBtn.addEventListener('click', () => {
                const nextTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
                animateThemeTransition();
                applyTheme(nextTheme);

                try {
                    localStorage.setItem('sikuta_theme', nextTheme);
                } catch (error) {
                    // Abaikan jika browser memblokir localStorage.
                }
            });
        })();

        (function setupHeadnavLiveTime() {
            const target = document.getElementById('headnavLiveTime');
            if (!target) return;

            const formatter = new Intl.DateTimeFormat('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta',
            });

            const render = () => {
                const parts = formatter.formatToParts(new Date());
                const values = {};
                parts.forEach((part) => {
                    if (part.type !== 'literal') {
                        values[part.type] = part.value;
                    }
                });

                target.textContent = `${values.day} ${values.month} ${values.year} ${values.hour}:${values.minute}:${values.second} WIB`;
            };

            render();
            window.setInterval(render, 1000);
        })();

        (function setupStatusPopup() {
            const stack = document.getElementById('statusPopupStack');
            if (!stack) return;

            const items = Array.from(stack.querySelectorAll('[data-popup-item]'));
            if (!items.length) return;

            const removeIfEmpty = () => {
                if (!stack.querySelector('[data-popup-item]')) {
                    stack.remove();
                }
            };

            const hideItem = (item) => {
                if (!item || item.classList.contains('is-hiding')) return;
                item.classList.add('is-hiding');
                window.setTimeout(() => {
                    item.remove();
                    removeIfEmpty();
                }, 280);
            };

            items.forEach((item, index) => {
                const closeBtn = item.querySelector('[data-popup-close]');
                const showDelay = 80 + (index * 100);
                const autoHideDelay = 5200 + (index * 450);

                window.setTimeout(() => {
                    item.classList.add('is-visible');
                }, showDelay);

                window.setTimeout(() => {
                    hideItem(item);
                }, autoHideDelay);

                closeBtn?.addEventListener('click', () => {
                    hideItem(item);
                });
            });
        })();

        (function setupAdminLoginLogoutTransition() {
            const body = document.body;
            if (!body) return;

            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const normalizePath = (path) => String(path || '/').replace(/\/+$/, '') || '/';

            if (!prefersReducedMotion) {
                body.classList.add('page-entering');
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(() => {
                        body.classList.remove('page-entering');
                    });
                });
            }

            if (prefersReducedMotion) return;

            const logoutForms = Array.from(document.querySelectorAll('form.headnav-account-form[action]')).filter((form) => {
                if (!(form instanceof HTMLFormElement)) return false;

                const actionUrl = new URL(form.action, window.location.href);
                return actionUrl.origin === window.location.origin && normalizePath(actionUrl.pathname) === '/admin/logout';
            });
            if (!logoutForms.length) return;

            logoutForms.forEach((form) => {
                let isSubmitting = false;
                form.addEventListener('submit', (event) => {
                    if (event.defaultPrevented || isSubmitting) {
                        return;
                    }

                    event.preventDefault();
                    isSubmitting = true;
                    body.classList.add('page-leaving');

                    window.setTimeout(() => {
                        form.submit();
                    }, 240);
                });
            });
        })();
    </script>

    @livewireScripts
</body>
</html>

