<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const SECRETARY_ROLE = 'sekretaris_pimpinan';
    private const SECRETARY_USERNAME = 'sekretaris_pimpinan';

    private const ALLOWED_ROLES = [
        'admin',
        'data_entry',
        'sekretaris_pimpinan',
        'bidang_tujuan',
        'subbag_umum',
    ];

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_authenticated')) {
            $adminRole = (string) $request->session()->get('admin_role');

            return redirect()->route($this->resolveDashboardRoute($adminRole));
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:80',
            'password' => 'required|string',
        ]);

        $inputUsername = trim((string) ($credentials['username'] ?? ''));
        $usernameCandidates = $this->resolveUsernameCandidates($inputUsername);
        $usernameCandidatesLower = array_map(static fn ($value) => strtolower((string) $value), $usernameCandidates);
        $orderSql = $this->buildUsernamePriorityOrderSql($usernameCandidatesLower);

        try {
            $user = User::query()
                ->where(function ($query) use ($usernameCandidatesLower) {
                    foreach ($usernameCandidatesLower as $candidate) {
                        $query->orWhereRaw('LOWER(username) = ?', [$candidate]);
                    }
                })
                ->orderByRaw($orderSql, $usernameCandidatesLower)
                ->first();
        } catch (\Throwable $e) {
            Log::error('Gagal login admin karena koneksi database.', ['message' => $e->getMessage()]);

            return back()
                ->withErrors(['username' => 'Login belum bisa diproses karena koneksi database tidak tersedia.'])
                ->onlyInput('username');
        }

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'Username atau password salah.'])
                ->onlyInput('username');
        }

        if (!$user->is_active) {
            return back()
                ->withErrors(['username' => 'Akun admin ini sedang dinonaktifkan. Hubungi Admin untuk aktivasi ulang.'])
                ->onlyInput('username');
        }

        if (!in_array($user->role, self::ALLOWED_ROLES, true)) {
            return back()
                ->withErrors(['username' => 'Akun admin belum memiliki level jabatan yang valid.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);
        $request->session()->put('admin_user_id', $user->id);
        $request->session()->put('admin_user_name', $user->name);
        $request->session()->put('admin_role', $user->role);

        try {
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => (string) $request->ip(),
                'last_login_user_agent' => (string) ($request->userAgent() ?? ''),
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Gagal menyimpan metadata login admin.', ['message' => $e->getMessage()]);
        }

        return redirect()->route($this->resolveDashboardRoute($user->role));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'admin_authenticated',
            'admin_user_id',
            'admin_user_name',
            'admin_role',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('admin_logout_transition', true);
    }

    private function resolveDashboardRoute(?string $role): string
    {
        return match ($role) {
            'admin' => 'admin.accounts',
            'data_entry' => 'admin.dashboard.data_entry',
            'sekretaris_pimpinan' => 'admin.dashboard.sekretaris_pimpinan',
            'bidang_tujuan' => 'admin.dashboard.bidang_tujuan',
            'subbag_umum' => 'admin.dashboard.subbag_umum',
            default => 'admin.dashboard',
        };
    }

    /**
     * Build username candidates from common aliases so login is resilient
     * when user types role labels instead of exact account usernames.
     *
     * @return array<int, string>
     */
    private function resolveUsernameCandidates(string $inputUsername): array
    {
        $inputUsername = trim($inputUsername);
        if ($inputUsername === '') {
            return [];
        }

        $normalizedWithUnderscore = strtolower(trim(preg_replace('/\s+/', '_', $inputUsername) ?? $inputUsername));
        $normalizedSlug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $inputUsername), '_'));
        $normalizedCompact = str_replace('_', '', $normalizedSlug);

        $candidates = [
            $inputUsername,
            strtolower($inputUsername),
            $normalizedWithUnderscore,
            $normalizedSlug,
        ];

        $aliasMap = [
            'administrator' => ['admin'],
            'manajemen_akun' => ['admin'],
            'dataentry' => ['data_entry'],
            'data_entry' => ['data_entry'],
            'sekretaris' => [self::SECRETARY_USERNAME],
            'sekretarispimpinan' => [self::SECRETARY_USERNAME],
            'sekretaris_pimpinan' => [self::SECRETARY_USERNAME],
            'subbagianumum' => ['subbag_umum'],
            'subbagian_umum' => ['subbag_umum'],
            'subbagumum' => ['subbag_umum'],
            'subbag_umum' => ['subbag_umum'],
        ];
        $aliasRoleMap = [
            'administrator' => 'admin',
            'manajemen_akun' => 'admin',
            'dataentry' => 'data_entry',
            'data_entry' => 'data_entry',
            'sekretaris' => self::SECRETARY_ROLE,
            'sekretarispimpinan' => self::SECRETARY_ROLE,
            'sekretaris_pimpinan' => self::SECRETARY_ROLE,
            'subbagianumum' => 'subbag_umum',
            'subbagian_umum' => 'subbag_umum',
            'subbagumum' => 'subbag_umum',
            'subbag_umum' => 'subbag_umum',
        ];

        foreach ([$normalizedWithUnderscore, $normalizedSlug, $normalizedCompact] as $normalizedKey) {
            if (isset($aliasMap[$normalizedKey])) {
                foreach ($aliasMap[$normalizedKey] as $aliasUsername) {
                    $candidates[] = $aliasUsername;
                }
            }
            if (isset($aliasRoleMap[$normalizedKey])) {
                $roleUsername = $this->resolvePrimaryUsernameByRole($aliasRoleMap[$normalizedKey]);
                if ($roleUsername !== null) {
                    $candidates[] = $roleUsername;
                }
            }
        }

        if (in_array($normalizedCompact, ['operatorbidang', 'operatorwilayah', 'operatorbidangwilayah'], true)
            || in_array($normalizedSlug, ['operator_bidang', 'operator_wilayah', 'operator_bidang_wilayah'], true)
        ) {
            $defaultOperatorUsername = $this->resolveDefaultOperatorUsername();
            if ($defaultOperatorUsername !== null) {
                $candidates[] = $defaultOperatorUsername;
            }
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($value) => trim((string) $value), $candidates),
            static fn ($value) => $value !== ''
        )));
    }

    private function resolveDefaultOperatorUsername(): ?string
    {
        return $this->resolvePrimaryUsernameByRole('bidang_tujuan');
    }

    private function resolvePrimaryUsernameByRole(string $role): ?string
    {
        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('username');
    }

    private function buildUsernamePriorityOrderSql(array $usernameCandidatesLower): string
    {
        $sql = 'CASE LOWER(username)';

        foreach ($usernameCandidatesLower as $index => $username) {
            $priority = $index + 1;
            $sql .= " WHEN ? THEN {$priority}";
        }

        return $sql . ' ELSE 999 END';
    }
}
