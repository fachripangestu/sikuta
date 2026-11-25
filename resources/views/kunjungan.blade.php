<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kunjungan Tamu Studi Tiru Inspektorat DKI Jakarta - SIKUTA</title>
    @php
        $faviconPath = public_path('images/logo-sikuta-full.png');
        $faviconVersion = file_exists($faviconPath) ? (string) filemtime($faviconPath) : (string) time();
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
    @php
        $kunjunganCssPath = public_path('css/kunjungan.css');
        $kunjunganCssVersion = file_exists($kunjunganCssPath) ? (string) filemtime($kunjunganCssPath) : (string) time();
        $kunjunganCssInline = file_exists($kunjunganCssPath) ? file_get_contents($kunjunganCssPath) : '';
    @endphp
    <link rel="stylesheet" href="{{ asset('css/kunjungan.css') }}?v={{ $kunjunganCssVersion }}">
    @if (!empty($kunjunganCssInline))
        <style id="kunjungan-inline-css">{!! $kunjunganCssInline !!}</style>
    @endif
</head>
<body class="dashboard-page">
    @php
        $dbDownAdmin = $dbDownAdmin ?? false;
        $queueKunjungan = $queueKunjungan ?? collect();
        $recentKunjungan = $recentKunjungan ?? collect();
        $operatorUsers = $operatorUsers ?? collect();
        $adminUser = $adminUser ?? null;
        $adminProfilePhotoPath = trim((string) ($adminUser?->profile_photo_path ?? ''));
        $adminProfilePhotoVersion = ($adminProfilePhotoPath !== '' && file_exists(public_path($adminProfilePhotoPath)))
            ? (string) filemtime(public_path($adminProfilePhotoPath))
            : (string) (file_exists(public_path('images/user-avatar.svg')) ? filemtime(public_path('images/user-avatar.svg')) : time());
        $adminProfilePhotoUrl = $adminProfilePhotoPath !== '' && file_exists(public_path($adminProfilePhotoPath))
            ? asset($adminProfilePhotoPath) . '?v=' . $adminProfilePhotoVersion
            : asset('images/user-avatar.svg') . '?v=' . $adminProfilePhotoVersion;

        $activeRoleLabel = $roleLabels[$adminRole] ?? 'Admin';
        $canManageAccounts = ($adminRole ?? '') === 'admin';
        $homeRouteName = match ($adminRole) {
            'admin' => 'admin.accounts',
            'data_entry' => 'admin.dashboard.data_entry',
            'sekretaris_pimpinan' => 'admin.dashboard.sekretaris_pimpinan',
            'bidang_tujuan' => 'admin.dashboard.bidang_tujuan',
            'subbag_umum' => 'admin.dashboard.subbag_umum',
            default => 'admin.dashboard',
        };

        $pendingCount = (int) ($notificationSnapshot['pending_count'] ?? $queueKunjungan->count());
        $approvedCount = $recentKunjungan->whereIn('status', ['diterima', 'terkonfirmasi', 'selesai'])->count();
        $rescheduleCount = $recentKunjungan->where('status', 'ditunda')->count();
        $justVerifiedId = (int) session('verified_id', 0);

        $isSubbagRole = $adminRole === 'subbag_umum';
        $showBalasanColumn = $isSubbagRole;
        $pendingLabel = $isSubbagRole ? 'Menunggu Upload Balasan' : 'Menunggu Verifikasi';

        $popupMessages = [];
        if ($dbDownAdmin || ($notificationSnapshot['db_down'] ?? false)) {
            $popupMessages[] = [
                'type' => 'error',
                'text' => 'Koneksi database belum aktif. Jalankan MySQL di XAMPP agar notifikasi berjenjang dan verifikasi real time dapat digunakan.',
            ];
        }
        if (session('admin_notice')) {
            $popupMessages[] = [
                'type' => 'success',
                'text' => (string) session('admin_notice'),
            ];
        }
        if ($errors->any()) {
            $popupMessages[] = [
                'type' => 'error',
                'text' => (string) $errors->first(),
            ];
        }
        $showCenteredLoginPopup = false;
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
                                    src="{{ $adminProfilePhotoUrl }}"
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
                            <a class="headnav-account-item" href="{{ route('admin.profile') }}">Edit Profil</a>
                            <form method="post" action="{{ route('admin.logout') }}" class="headnav-account-form">
                                @csrf
                                <button class="headnav-account-item headnav-account-item-logout" type="submit">Logout</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            @if (!empty($popupMessages))
                <div id="statusPopupStack" class="status-popup-stack{{ $showCenteredLoginPopup ? ' status-popup-stack-center' : '' }}" aria-live="polite" aria-atomic="true">
                    @foreach ($popupMessages as $popupMessage)
                        <div class="status-popup status-popup-{{ $popupMessage['type'] ?? 'success' }}" data-popup-item>
                            <span class="status-popup-icon" aria-hidden="true">
                                @if (($popupMessage['type'] ?? '') === 'error')
                                    !
                                @else
                                    <svg viewBox="0 0 20 20" focusable="false" aria-hidden="true">
                                        <path d="M7.9 13.7 4.8 10.6a1 1 0 0 1 1.4-1.4l1.7 1.7 5.9-5.9a1 1 0 1 1 1.4 1.4l-7.3 7.3a1 1 0 0 1-1.4 0Z" fill="currentColor"></path>
                                    </svg>
                                @endif
                            </span>
                            <p>{{ $popupMessage['text'] ?? '' }}</p>
                            <button type="button" class="status-popup-close" data-popup-close aria-label="Tutup notifikasi">&times;</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <section class="stat-grid">
                <article class="stat-card">
                    <div class="stat-icon stat-blue">&#128101;</div>
                    <div>
                        <div class="stat-value">{{ $totalKunjungan }}</div>
                        <div class="stat-label">Total Permohonan</div>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon stat-yellow">&#9201;</div>
                    <div>
                        <div class="stat-value">{{ $pendingCount }}</div>
                        <div class="stat-label">{{ $pendingLabel }}</div>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon stat-green">&#128196;</div>
                    <div>
                        <div class="stat-value">{{ $approvedCount }}</div>
                        <div class="stat-label">Disetujui</div>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon stat-orange">&#9888;</div>
                    <div>
                        <div class="stat-value">{{ $rescheduleCount }}</div>
                        <div class="stat-label">Perlu Reschedule</div>
                    </div>
                </article>
            </section>

            <section class="content-grid">
                <section class="card table-card">
                    <div class="card-head">
                        <div>
                            <h3>Progress Permohonan Kunjungan</h3>
                            <p>Status permohonan berdasarkan proses terakhir.</p>
                        </div>
                        <span class="calendar-icon">&#128197;</span>
                    </div>

                    <livewire:admin-kunjungan-table
                        :admin-role="$adminRole"
                        :admin-user-id="$adminUserId"
                        :show-balasan-column="$showBalasanColumn"
                        :is-subbag-role="$isSubbagRole"
                        :just-verified-id="$justVerifiedId"
                        :operator-users="$operatorUsers->map(fn ($operatorUser) => ['id' => (int) $operatorUser->id, 'name' => (string) $operatorUser->name, 'username' => (string) $operatorUser->username])->values()->all()"
                        :workflow-labels="$workflowLabels"
                        :key="'admin-kunjungan-table-' . $adminRole . '-' . $adminUserId"
                    />
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

        (function setupAccountDropdownAutoClose() {
            const menus = Array.from(document.querySelectorAll('.headnav-account-menu'));
            if (!menus.length) return;

            const closeMenu = (menu) => {
                if (menu?.hasAttribute('open')) {
                    menu.removeAttribute('open');
                }
            };

            const closeAllMenus = (exceptMenu = null) => {
                menus.forEach((menu) => {
                    if (menu !== exceptMenu) {
                        closeMenu(menu);
                    }
                });
            };

            menus.forEach((menu) => {
                const trigger = menu.querySelector('summary');
                if (!trigger) return;

                trigger.addEventListener('click', () => {
                    window.setTimeout(() => {
                        if (menu.hasAttribute('open')) {
                            closeAllMenus(menu);
                        }
                    }, 0);
                });
            });

            document.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Node)) return;

                menus.forEach((menu) => {
                    if (!menu.contains(target)) {
                        closeMenu(menu);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAllMenus();
                }
            });
        })();

        (function setupProfileNavigationTransition() {
            let isLeaving = false;
            const profileLinks = Array.from(document.querySelectorAll('.headnav-account-item[href]')).filter((link) => {
                if (!(link instanceof HTMLAnchorElement)) return false;
                const url = new URL(link.href, window.location.href);
                return url.origin === window.location.origin && url.pathname.replace(/\/+$/, '') === '/admin/profile';
            });
            if (!profileLinks.length) return;

            const normalizePath = (path) => String(path || '/').replace(/\/+$/, '') || '/';
            const currentPath = normalizePath(window.location.pathname);
            if (!currentPath.startsWith('/admin/dashboard')) return;

            const resetLeavingState = () => {
                isLeaving = false;
                document.body.classList.remove('page-leaving');
            };

            profileLinks.forEach((link) => {
                link.addEventListener('click', (event) => {
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
                    if (isLeaving) return;

                    isLeaving = true;
                    document.body.classList.add('page-leaving');
                    window.setTimeout(() => {
                        window.location.assign(link.href);
                    }, 220);
                });
            });

            window.addEventListener('pageshow', resetLeavingState);
            resetLeavingState();
        })();

        (function setupAdminDashboardLoginLogoutTransition() {
            const body = document.body;
            if (!body) return;

            const normalizePath = (path) => String(path || '/').replace(/\/+$/, '') || '/';
            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

        (function setupAssistantActionPanels() {
            const setOpenPanel = (form, panelKey = '') => {
                const panels = Array.from(form.querySelectorAll('[data-action-panel]'));
                panels.forEach((panel) => {
                    const key = String(panel.getAttribute('data-action-panel') || '').trim();
                    if (key === panelKey && panelKey !== '') {
                        panel.removeAttribute('hidden');
                    } else {
                        panel.setAttribute('hidden', 'hidden');
                    }
                });

                const approveInput = form.querySelector('[data-panel-input="approve"]');
                if (approveInput instanceof HTMLSelectElement) {
                    const canApprove = approveInput.options.length > 1;
                    const enableApprove = panelKey === 'approve' && canApprove;
                    approveInput.disabled = !enableApprove;
                    approveInput.required = enableApprove;
                }

                const rescheduleInput = form.querySelector('[data-panel-input="reschedule"]');
                if (rescheduleInput instanceof HTMLInputElement || rescheduleInput instanceof HTMLTextAreaElement) {
                    const enableReschedule = panelKey === 'reschedule';
                    rescheduleInput.disabled = !enableReschedule;
                    rescheduleInput.required = enableReschedule;
                }

                const toggles = Array.from(form.querySelectorAll('[data-action-toggle]'));
                toggles.forEach((toggle) => {
                    const key = String(toggle.getAttribute('data-action-toggle') || '').trim();
                    const active = panelKey !== '' && key === panelKey;
                    toggle.classList.toggle('is-active', active);
                    toggle.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            };

            document.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;

                const toggle = target.closest('[data-action-toggle]');
                if (!(toggle instanceof HTMLButtonElement) || toggle.disabled) return;

                const form = toggle.closest('.table-verify-form.with-operator');
                if (!(form instanceof HTMLFormElement)) return;

                const panelKey = String(toggle.getAttribute('data-action-toggle') || '').trim();
                const isActive = toggle.classList.contains('is-active');
                setOpenPanel(form, isActive ? '' : panelKey);

                if (isActive || panelKey === '') return;

                const focusTarget = form.querySelector(`[data-panel-input="${panelKey}"]`);
                if (focusTarget instanceof HTMLElement && !focusTarget.hasAttribute('disabled')) {
                    focusTarget.focus({ preventScroll: true });
                }
            });
        })();

        (function setupBalasanUploadIconSubmit() {
            document.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement) || !target.classList.contains('table-upload-input')) {
                    return;
                }

                if (!target.files || target.files.length === 0) {
                    return;
                }

                const form = target.closest('form');
                if (form instanceof HTMLFormElement) {
                    form.submit();
                }
            });
        })();

        (function setupBalasanActionChoice() {
            const menuSelector = '.table-upload-choice-menu';

            const closeMenu = (menu) => {
                if (menu instanceof HTMLDetailsElement && menu.open) {
                    menu.removeAttribute('open');
                }
            };

            const closeAllMenus = (exceptMenu = null) => {
                const menus = Array.from(document.querySelectorAll(menuSelector));
                menus.forEach((menu) => {
                    if (!(menu instanceof HTMLDetailsElement)) return;
                    if (exceptMenu && menu === exceptMenu) return;
                    closeMenu(menu);
                });
            };

            document.addEventListener('toggle', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLDetailsElement) || !target.matches(menuSelector)) {
                    return;
                }

                if (target.open) {
                    closeAllMenus(target);
                }
            }, true);

            document.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) return;

                const button = target.closest('.table-upload-choice-item');
                if (button instanceof HTMLButtonElement && !button.disabled) {
                    const action = String(button.dataset.choiceAction || '').trim();
                    const menu = button.closest(menuSelector);

                    if (action === 'reupload') {
                        const inputId = String(button.dataset.uploadInputId || '').trim();
                        const uploadInput = inputId ? document.getElementById(inputId) : null;
                        if (uploadInput instanceof HTMLInputElement) {
                            uploadInput.click();
                        }
                        closeMenu(menu);
                        return;
                    }

                    if (action === 'delete') {
                        const deleteFormId = String(button.dataset.deleteFormId || '').trim();
                        const deleteForm = deleteFormId ? document.getElementById(deleteFormId) : null;
                        const shouldDelete = window.confirm('Hapus dokumen balasan ini?');

                        if (shouldDelete && deleteForm instanceof HTMLFormElement) {
                            deleteForm.submit();
                            return;
                        }

                        closeMenu(menu);
                        return;
                    }
                }

                if (!target.closest(menuSelector)) {
                    closeAllMenus();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeAllMenus();
                }
            });
        })();
    </script>
    @livewireScripts
</body>
</html>
