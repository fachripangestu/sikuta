<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SIKUTA</title>
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
    <link rel="stylesheet" href="{{ asset('css/admin-login.css') }}?v={{ file_exists(public_path('css/admin-login.css')) ? filemtime(public_path('css/admin-login.css')) : time() }}">
</head>
@php
    $showLogoutTransition = (bool) session('admin_logout_transition');
@endphp
<body class="{{ $showLogoutTransition ? 'is-login-processing' : '' }}">

    @if ($errors->any())
        <div id="loginPopupStack" class="login-popup-stack" aria-live="assertive" aria-atomic="true">
            <div class="login-popup login-popup-error" data-login-popup>
                <span class="login-popup-icon" aria-hidden="true">!</span>
                <p>{{ $errors->first() }}</p>
                <button type="button" class="login-popup-close" data-login-popup-close aria-label="Tutup popup error">&times;</button>
            </div>
        </div>
    @endif

    <div
        id="loginProgressPopup"
        class="login-progress-popup{{ $showLogoutTransition ? ' is-visible' : '' }}"
        role="status"
        aria-live="polite"
        aria-hidden="{{ $showLogoutTransition ? 'false' : 'true' }}"
        data-default-title="Memproses login..."
        data-default-subtitle="Mohon tunggu sebentar"
        data-logout-title="Logout berhasil"
        data-logout-subtitle="Memuat halaman login admin..."
    >
        <div class="login-progress-card">
            <span class="login-progress-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                    <path d="M12 3.4a8.6 8.6 0 1 0 8.6 8.6A8.61 8.61 0 0 0 12 3.4Zm0 14.84a1.1 1.1 0 1 1 0-2.2 1.1 1.1 0 0 1 0 2.2Zm1.05-4.86a1.05 1.05 0 0 1-2.1 0V8.72a1.05 1.05 0 0 1 2.1 0Z" fill="currentColor"/>
                </svg>
            </span>
            <div class="login-progress-copy">
                <strong data-login-progress-title>{{ $showLogoutTransition ? 'Logout berhasil' : 'Memproses login...' }}</strong>
                <small data-login-progress-subtitle>{{ $showLogoutTransition ? 'Memuat halaman login admin...' : 'Mohon tunggu sebentar' }}</small>
            </div>
        </div>
    </div>

    <button
        class="theme-toggle"
        id="themeToggleLoginBtn"
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

    <div class="card">
        <h1>Login Pengelola</h1>
        <div class="login-logo-wrap">
            <img
                class="login-logo"
                src="{{ asset('images/logo-sikuta-login.png') }}?v={{ file_exists(public_path('images/logo-sikuta-login.png')) ? filemtime(public_path('images/logo-sikuta-login.png')) : time() }}"
                alt="Logo SIKUTA"
            >
        </div>
        <div class="role-info">
            <strong>Login Khusus:</strong>
            <ul>
                <li>Admin (Manajemen Akun)</li>
                <li>Data Entry</li>
                <li>Sekretaris Pimpinan</li>
                <li>Operator Bidang/Wilayah</li>
                <li>Subbagian Umum</li>
            </ul>
        </div>

        <div class="panel">
            <form id="adminLoginForm" method="post" action="{{ route('admin.login.submit') }}" novalidate>
                @csrf
                <label for="username">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    autocomplete="username"
                    required
                >

                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                >
                <div class="password-tools">
                    <label class="password-toggle" for="toggle-password">
                        <span>Tampilkan Password</span>
                        <input id="toggle-password" type="checkbox">
                    </label>
                </div>

                <button class="btn" type="submit">Login</button>
            </form>
        </div>

        <a class="back" href="{{ route('guest.form') }}">Kembali ke halaman utama</a>
    </div>

    <script>
        (function setupLoginThemeToggle() {
            const root = document.documentElement;
            const toggleBtn = document.getElementById('themeToggleLoginBtn');
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

        (function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleInput = document.getElementById('toggle-password');
            if (!passwordInput || !toggleInput) return;

            toggleInput.addEventListener('change', () => {
                passwordInput.type = toggleInput.checked ? 'text' : 'password';
            });
        })();

        (function setupLoginPopupAndValidation() {
            const ensurePopupStack = () => {
                let stack = document.getElementById('loginPopupStack');
                if (stack) return stack;

                stack = document.createElement('div');
                stack.id = 'loginPopupStack';
                stack.className = 'login-popup-stack';
                stack.setAttribute('aria-live', 'assertive');
                stack.setAttribute('aria-atomic', 'true');
                document.body.appendChild(stack);
                return stack;
            };

            const showLoginPopup = (message) => {
                const stack = ensurePopupStack();
                stack.innerHTML = `
                    <div class="login-popup login-popup-error" data-login-popup>
                        <span class="login-popup-icon" aria-hidden="true">!</span>
                        <p></p>
                        <button type="button" class="login-popup-close" data-login-popup-close aria-label="Tutup popup error">&times;</button>
                    </div>
                `;

                const popup = stack.querySelector('[data-login-popup]');
                const textEl = popup?.querySelector('p');
                const closeBtn = stack.querySelector('[data-login-popup-close]');
                if (!popup || !textEl) return;

                textEl.textContent = message;

                const hidePopup = () => {
                    if (popup.classList.contains('is-hiding')) return;
                    popup.classList.add('is-hiding');
                    window.setTimeout(() => stack.remove(), 260);
                };

                requestAnimationFrame(() => {
                    popup.classList.add('is-visible');
                });

                window.setTimeout(hidePopup, 5200);
                closeBtn?.addEventListener('click', hidePopup);
            };

            const serverPopup = document.querySelector('#loginPopupStack [data-login-popup]');
            if (serverPopup) {
                const closeBtn = serverPopup.querySelector('[data-login-popup-close]');
                const hidePopup = () => {
                    if (serverPopup.classList.contains('is-hiding')) return;
                    serverPopup.classList.add('is-hiding');
                    window.setTimeout(() => {
                        const stack = document.getElementById('loginPopupStack');
                        stack?.remove();
                    }, 260);
                };

                requestAnimationFrame(() => {
                    serverPopup.classList.add('is-visible');
                });
                window.setTimeout(hidePopup, 5200);
                closeBtn?.addEventListener('click', hidePopup);
            }

            const form = document.getElementById('adminLoginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            if (!form || !usernameInput || !passwordInput) return;

            form.addEventListener('submit', (event) => {
                const username = String(usernameInput.value || '').trim();
                const password = String(passwordInput.value || '');

                if (!username) {
                    event.preventDefault();
                    showLoginPopup('Isi username terlebih dahulu');
                    usernameInput.focus();
                    return;
                }

                if (!password) {
                    event.preventDefault();
                    showLoginPopup('Isi password terlebih dahulu');
                    passwordInput.focus();
                }
            });
        })();

        (function setupLoginTransitionToDashboard() {
            const body = document.body;
            if (!body) return;
            const shouldShowLogoutTransition = @json($showLogoutTransition);

            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!prefersReducedMotion) {
                body.classList.add('page-entering');
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(() => {
                        body.classList.remove('page-entering');
                    });
                });
            }

            const form = document.getElementById('adminLoginForm');
            const progressPopup = document.getElementById('loginProgressPopup');
            const progressTitle = progressPopup?.querySelector('[data-login-progress-title]');
            const progressSubtitle = progressPopup?.querySelector('[data-login-progress-subtitle]');
            const submitButton = form?.querySelector('button[type="submit"]');
            let isSubmitting = false;
            let logoutPopupTimeoutId = null;

            const defaultTitle = progressPopup?.getAttribute('data-default-title') || 'Memproses login...';
            const defaultSubtitle = progressPopup?.getAttribute('data-default-subtitle') || 'Mohon tunggu sebentar';
            const logoutTitle = progressPopup?.getAttribute('data-logout-title') || 'Logout berhasil';
            const logoutSubtitle = progressPopup?.getAttribute('data-logout-subtitle') || 'Memuat halaman login admin...';

            const setProgressCopy = (titleText, subtitleText) => {
                if (progressTitle instanceof HTMLElement) {
                    progressTitle.textContent = titleText;
                }
                if (progressSubtitle instanceof HTMLElement) {
                    progressSubtitle.textContent = subtitleText;
                }
            };

            const showLoginProgressPopup = (titleText = defaultTitle, subtitleText = defaultSubtitle) => {
                if (!(progressPopup instanceof HTMLElement)) return;
                setProgressCopy(titleText, subtitleText);
                progressPopup.setAttribute('aria-hidden', 'false');
                progressPopup.classList.add('is-visible');
            };

            const hideLoginProgressPopup = () => {
                if (!(progressPopup instanceof HTMLElement)) return;
                progressPopup.classList.remove('is-visible');
                progressPopup.setAttribute('aria-hidden', 'true');
                setProgressCopy(defaultTitle, defaultSubtitle);
            };

            if (shouldShowLogoutTransition) {
                showLoginProgressPopup(logoutTitle, logoutSubtitle);
                body.classList.add('is-login-processing');

                const logoutPopupDuration = prefersReducedMotion ? 860 : 1150;
                logoutPopupTimeoutId = window.setTimeout(() => {
                    if (isSubmitting) return;
                    hideLoginProgressPopup();
                    body.classList.remove('is-login-processing');
                }, logoutPopupDuration);
            }

            if (!(form instanceof HTMLFormElement)) return;

            form.addEventListener('submit', (event) => {
                if (event.defaultPrevented || isSubmitting) {
                    return;
                }

                event.preventDefault();
                isSubmitting = true;
                if (logoutPopupTimeoutId !== null) {
                    window.clearTimeout(logoutPopupTimeoutId);
                }

                showLoginProgressPopup();
                body.classList.add('is-login-processing');

                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Memproses...';
                    submitButton.setAttribute('aria-busy', 'true');
                }

                const leavingDelay = prefersReducedMotion ? 0 : 90;
                const submitDelay = prefersReducedMotion ? 90 : 420;
                window.setTimeout(() => {
                    body.classList.add('page-leaving');
                }, leavingDelay);

                window.setTimeout(() => {
                    form.submit();
                }, submitDelay);
            });
        })();
    </script>
</body>
</html>
