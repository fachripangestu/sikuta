<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Admin - SIKUTA</title>
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
        $profilePhotoPath = trim((string) ($adminUser->profile_photo_path ?? ''));
        $profilePhotoVersion = ($profilePhotoPath !== '' && file_exists(public_path($profilePhotoPath)))
            ? (string) filemtime(public_path($profilePhotoPath))
            : (string) time();
        $profilePhotoUrl = $profilePhotoPath !== '' && file_exists(public_path($profilePhotoPath))
            ? asset($profilePhotoPath) . '?v=' . $profilePhotoVersion
            : asset('images/user-avatar.svg') . '?v=' . (file_exists(public_path('images/user-avatar.svg')) ? filemtime(public_path('images/user-avatar.svg')) : time());
        $statusLabel = ($adminUser->is_active ?? false) ? 'Aktif' : 'Nonaktif';
        $canManageAccounts = $canManageAccounts ?? ($adminRole === 'admin');
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
                                    src="{{ $profilePhotoUrl }}"
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
                                <a class="headnav-account-item" href="{{ route('admin.accounts') }}">Manajemen Akun</a>
                            @endif
                            <a class="headnav-account-item is-active" href="{{ route('admin.profile') }}">Edit Profil</a>
                            <form method="post" action="{{ route('admin.logout') }}" class="headnav-account-form">
                                @csrf
                                <button class="headnav-account-item headnav-account-item-logout" type="submit">Logout</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>
            @if (($notificationSnapshot['db_down'] ?? false))
                <div class="notice error">Koneksi database belum aktif. Jalankan MySQL di XAMPP agar profil dapat disimpan.</div>
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
                            <h3>Edit Profil</h3>
                            <p>Perbarui nama tampilan, password, dan foto profil akun admin Anda.</p>
                        </div>
                    </div>

                    <form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="account-form">
                        @csrf
                        <div class="account-field">
                            <label for="profile-username">Username</label>
                            <input id="profile-username" type="text" value="{{ $adminUser->username }}" disabled>
                        </div>
                        <div class="account-field">
                            <label for="profile-name">Nama Tampilan</label>
                            <input id="profile-name" type="text" name="name" value="{{ old('name', $adminUser->name) }}" required>
                        </div>
                        <div class="account-field account-field-row">
                            <div>
                                <label for="profile-password">Password Baru</label>
                                <input id="profile-password" type="password" name="new_password" placeholder="Kosongkan jika tidak diubah">
                            </div>
                            <div>
                                <label for="profile-password-confirmation">Konfirmasi Password Baru</label>
                                <input id="profile-password-confirmation" type="password" name="new_password_confirmation" placeholder="Ulangi password baru">
                            </div>
                        </div>
                        <div class="account-field">
                            <label for="profile-photo">Foto Profil (JPG/PNG, max 2 MB)</label>
                            <input id="profile-photo" type="file" name="profile_photo" accept=".jpg,.jpeg,.png">
                        </div>
                        <label class="account-check">
                            <input type="checkbox" name="remove_photo" value="1">
                            Hapus foto profil saat menyimpan perubahan
                        </label>

                        <div class="account-actions">
                            <button class="action-btn approve" type="submit">Simpan Perubahan</button>
                            <a class="headnav-btn headnav-btn-link" href="{{ route('admin.dashboard') }}">Kembali</a>
                        </div>
                    </form>
                </section>

                <section class="card table-card">
                    <div class="card-head">
                        <div>
                            <h3>Info Akun</h3>
                            <p>Ringkasan status login dan data akun saat ini.</p>
                        </div>
                    </div>

                    <div class="profile-preview-wrap">
                        <img class="profile-preview-image" src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $adminUser->name }}">
                        <div class="profile-preview-meta">
                            <strong>{{ $adminUser->name }}</strong>
                            <span>{{ $adminUser->username }}</span>
                        </div>
                    </div>

                    <div class="profile-info-list">
                        <div class="profile-info-row">
                            <span>Role</span>
                            <strong>{{ $roleLabels[$adminUser->role] ?? $adminUser->role }}</strong>
                        </div>
                        <div class="profile-info-row">
                            <span>Status</span>
                            <strong>{{ $statusLabel }}</strong>
                        </div>
                        <div class="profile-info-row">
                            <span>Login Terakhir</span>
                            <strong>{{ $adminUser->last_login_at?->timezone('Asia/Jakarta')?->format('d M Y H:i') ?? '-' }}</strong>
                        </div>
                        <div class="profile-info-row">
                            <span>IP Terakhir</span>
                            <strong>{{ $adminUser->last_login_ip ?: '-' }}</strong>
                        </div>
                        <div class="profile-info-row">
                            <span>Device Terakhir</span>
                            <strong>{{ \Illuminate\Support\Str::limit((string) ($adminUser->last_login_user_agent ?? '-'), 70) }}</strong>
                        </div>
                        <div class="profile-info-row">
                            <span>Diperbarui</span>
                            <strong>{{ $adminUser->updated_at?->timezone('Asia/Jakarta')?->format('d M Y H:i') ?? '-' }}</strong>
                        </div>
                    </div>
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

