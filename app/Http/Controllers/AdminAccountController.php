<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAccountController extends Controller
{
    private const MANAGE_ROLE = 'admin';

    private const ALLOWED_ROLES = [
        'admin',
        'data_entry',
        'sekretaris_pimpinan',
        'bidang_tujuan',
        'subbag_umum',
    ];

    public function __construct(
        private readonly AdminNotificationService $notificationService
    ) {
    }

    public function accounts(Request $request): View|RedirectResponse
    {
        $context = $this->buildContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $canManageAccounts = $this->canManageAccounts($context['adminRole']);

        try {
            $accounts = User::query()
                ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'data_entry' THEN 2 WHEN 'sekretaris_pimpinan' THEN 3 WHEN 'bidang_tujuan' THEN 4 WHEN 'subbag_umum' THEN 5 ELSE 99 END")
                ->orderBy('name')
                ->get();
            $dbDownAdmin = false;
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil data manajemen akun.', ['message' => $e->getMessage()]);
            $accounts = collect();
            $dbDownAdmin = true;
        }

        $accountsByRole = $accounts->groupBy('role');

        return view('admin.accounts', array_merge($context, [
            'canManageAccounts' => $canManageAccounts,
            'accounts' => $accounts,
            'accountsByRole' => $accountsByRole,
            'dbDownAdmin' => $dbDownAdmin,
        ]));
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $context = $this->buildContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        if (!$this->canManageAccounts($context['adminRole'])) {
            return back()->withErrors(['account' => 'Hanya Admin yang dapat menambah akun.']);
        }

        $validated = $request->validate([
            'username' => 'required|string|max:80|alpha_dash|unique:users,username',
            'name' => 'required|string|max:120',
            'role' => ['required', Rule::in(self::ALLOWED_ROLES)],
            'password' => 'required|string|min:6|max:120',
        ]);

        try {
            $email = $this->generateUniqueLocalEmail((string) $validated['username']);

            User::create([
                'username' => trim((string) $validated['username']),
                'name' => trim((string) $validated['name']),
                'email' => $email,
                'role' => (string) $validated['role'],
                'password' => Hash::make((string) $validated['password']),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menambah akun admin.', ['message' => $e->getMessage()]);

            return back()
                ->withErrors(['account' => 'Akun baru gagal dibuat karena koneksi database tidak tersedia.'])
                ->withInput();
        }

        return back()->with('admin_notice', 'Akun baru berhasil dibuat.');
    }

    public function toggleAccountStatus(Request $request, User $user): RedirectResponse
    {
        $context = $this->buildContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        if (!$this->canManageAccounts($context['adminRole'])) {
            return back()->withErrors(['account' => 'Hanya Admin yang dapat mengubah status akun.']);
        }

        if ((int) $context['adminUser']->id === (int) $user->id) {
            return back()->withErrors(['account' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan sendiri.']);
        }

        if (
            $user->role === self::MANAGE_ROLE &&
            $user->is_active &&
            User::query()->where('role', self::MANAGE_ROLE)->where('is_active', true)->count() <= 1
        ) {
            return back()->withErrors(['account' => 'Minimal harus ada satu akun Admin yang aktif.']);
        }

        try {
            $user->is_active = !$user->is_active;
            $user->save();
        } catch (\Throwable $e) {
            Log::error('Gagal mengubah status akun admin.', ['message' => $e->getMessage()]);

            return back()->withErrors(['account' => 'Status akun gagal diperbarui karena koneksi database tidak tersedia.']);
        }

        $statusLabel = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('admin_notice', "Akun {$user->username} berhasil {$statusLabel}.");
    }

    public function resetAccountPassword(Request $request, User $user): RedirectResponse
    {
        $context = $this->buildContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        if (!$this->canManageAccounts($context['adminRole'])) {
            return back()->withErrors(['account' => 'Hanya Admin yang dapat mereset password akun.']);
        }

        $temporaryPassword = Str::random(24);

        try {
            $user->password = Hash::make($temporaryPassword);
            $user->save();
        } catch (\Throwable $e) {
            Log::error('Gagal reset password akun admin.', ['message' => $e->getMessage()]);

            return back()->withErrors(['account' => 'Password akun gagal direset karena koneksi database tidak tersedia.']);
        }

        return back()->with('admin_notice', "Password baru akun {$user->username}: {$temporaryPassword}");
    }

    public function deleteAccount(Request $request, User $user): RedirectResponse
    {
        $context = $this->buildContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        if (!$this->canManageAccounts($context['adminRole'])) {
            return back()->withErrors(['account' => 'Hanya Admin yang dapat menghapus akun.']);
        }

        if ((int) $context['adminUser']->id === (int) $user->id) {
            return back()->withErrors(['account' => 'Akun yang sedang digunakan tidak dapat dihapus sendiri.']);
        }

        if (
            $user->role === self::MANAGE_ROLE &&
            User::query()->where('role', self::MANAGE_ROLE)->count() <= 1
        ) {
            return back()->withErrors(['account' => 'Minimal harus ada satu akun Admin.']);
        }

        try {
            $this->removeProfilePhotoFile((string) ($user->profile_photo_path ?? ''));
            $username = (string) $user->username;
            $user->delete();
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus akun admin.', ['message' => $e->getMessage()]);

            return back()->withErrors(['account' => 'Akun gagal dihapus karena koneksi database tidak tersedia.']);
        }

        return back()->with('admin_notice', "Akun {$username} berhasil dihapus.");
    }

    public function profile(Request $request): View|RedirectResponse
    {
        $context = $this->buildContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return view('admin.profile', $context);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $context = $this->buildContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }

        /** @var User $adminUser */
        $adminUser = $context['adminUser'];

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'new_password' => 'nullable|string|min:6|max:120|confirmed',
            'profile_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'remove_photo' => 'nullable|boolean',
        ]);

        try {
            $adminUser->name = trim((string) $validated['name']);

            $removePhoto = (bool) ($validated['remove_photo'] ?? false);
            $uploadedPhoto = $request->file('profile_photo');

            if ($removePhoto && !$uploadedPhoto) {
                $this->removeProfilePhotoFile((string) ($adminUser->profile_photo_path ?? ''));
                $adminUser->profile_photo_path = null;
            }

            if ($uploadedPhoto) {
                $storedPath = $this->storeProfilePhoto($uploadedPhoto, (string) ($adminUser->profile_photo_path ?? ''));
                $adminUser->profile_photo_path = $storedPath;
            }

            $newPassword = trim((string) ($validated['new_password'] ?? ''));
            if ($newPassword !== '') {
                $adminUser->password = Hash::make($newPassword);
            }

            $adminUser->save();
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui profil admin.', ['message' => $e->getMessage()]);

            return back()->withErrors(['profile' => 'Profil gagal diperbarui karena koneksi database tidak tersedia.']);
        }

        $request->session()->put('admin_user_name', $adminUser->name);

        return back()->with('admin_notice', 'Profil berhasil diperbarui.');
    }

    private function buildContext(Request $request): array|RedirectResponse
    {
        $adminRole = (string) $request->session()->get('admin_role');
        $adminUserId = (int) $request->session()->get('admin_user_id');

        if ($adminUserId <= 0 || $adminRole === '') {
            return redirect()->route('admin.login');
        }

        try {
            $adminUser = User::find($adminUserId);
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil data akun admin aktif.', ['message' => $e->getMessage()]);
            $adminUser = null;
        }

        if (!$adminUser) {
            $request->session()->forget([
                'admin_authenticated',
                'admin_user_id',
                'admin_user_name',
                'admin_role',
            ]);

            return redirect()->route('admin.login');
        }

        $request->session()->put('admin_user_name', $adminUser->name);

        $roleLabels = $this->roleLabels();
        $activeRoleLabel = $roleLabels[$adminRole] ?? 'Admin';
        $showAdminName = strcasecmp(trim((string) $adminUser->name), trim((string) $activeRoleLabel)) !== 0;

        return [
            'adminRole' => $adminRole,
            'adminUser' => $adminUser,
            'adminUserName' => (string) $adminUser->name,
            'roleLabels' => $roleLabels,
            'activeRoleLabel' => $activeRoleLabel,
            'showAdminName' => $showAdminName,
            'notificationSnapshot' => $this->notificationService->buildPayload($adminRole, (int) $adminUser->id),
            'canManageAccounts' => $this->canManageAccounts($adminRole),
        ];
    }

    private function canManageAccounts(string $role): bool
    {
        return $role === self::MANAGE_ROLE;
    }

    private function roleLabels(): array
    {
        return array_merge(
            ['admin' => 'Admin'],
            $this->notificationService->roleLabels()
        );
    }

    private function generateUniqueLocalEmail(string $username): string
    {
        $base = strtolower(trim($username));
        $base = preg_replace('/[^a-z0-9_.-]+/', '', $base) ?: 'admin';

        $candidate = $base . '@localhost';
        $counter = 2;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = $base . $counter . '@localhost';
            $counter++;
        }

        return $candidate;
    }

    private function storeProfilePhoto(\Illuminate\Http\UploadedFile $file, string $currentPath): string
    {
        $targetDirectory = public_path('images/profile-admin');
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'jpg'));
        $filename = 'admin-' . now()->format('YmdHis') . '-' . Str::random(8) . '.' . $extension;
        $file->move($targetDirectory, $filename);

        $this->removeProfilePhotoFile($currentPath);

        return 'images/profile-admin/' . $filename;
    }

    private function removeProfilePhotoFile(string $path): void
    {
        $normalized = str_replace('\\', '/', trim($path));
        if ($normalized === '' || !str_starts_with($normalized, 'images/profile-admin/')) {
            return;
        }

        $absolutePath = public_path($normalized);
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
