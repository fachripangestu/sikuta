<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->migrateLegacySekretarisPimpinan();
        $this->migrateLegacyInvestigasiOperator();

        $accounts = [
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'username' => env('ADMIN_USERNAME', 'admin'),
                'email' => env('ADMIN_EMAIL', 'admin@localhost'),
                'password' => env('ADMIN_PASSWORD'),
                'role' => 'admin',
            ],
            [
                'name' => 'Data Entry',
                'username' => env('DATA_ENTRY_USERNAME', 'data_entry'),
                'email' => env('DATA_ENTRY_EMAIL', 'data.entry@localhost'),
                'password' => env('DATA_ENTRY_PASSWORD'),
                'role' => 'data_entry',
            ],
            [
                'name' => 'Sekretaris Pimpinan',
                'username' => env('SEKRETARIS_PIMPINAN_USERNAME', 'sekretaris_pimpinan'),
                'email' => env('SEKRETARIS_PIMPINAN_EMAIL', 'sekretaris.pimpinan@localhost'),
                'password' => env('SEKRETARIS_PIMPINAN_PASSWORD'),
                'role' => 'sekretaris_pimpinan',
            ],
            [
                'name' => 'Subbagian Umum',
                'username' => env('SUBBAG_UMUM_USERNAME', 'subbag_umum'),
                'email' => env('SUBBAG_UMUM_EMAIL', 'subbag.umum@localhost'),
                'password' => env('SUBBAG_UMUM_PASSWORD'),
                'role' => 'subbag_umum',
            ],
            [
                'name' => 'Operator Bidang 1',
                'username' => env('OPERATOR_BIDANG_1_USERNAME', 'operator_bidang_1'),
                'email' => env('OPERATOR_BIDANG_1_EMAIL', 'operator.bidang1@localhost'),
                'password' => env('OPERATOR_BIDANG_1_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Bidang 2',
                'username' => env('OPERATOR_BIDANG_2_USERNAME', 'operator_bidang_2'),
                'email' => env('OPERATOR_BIDANG_2_EMAIL', 'operator.bidang2@localhost'),
                'password' => env('OPERATOR_BIDANG_2_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Bidang 3',
                'username' => env('OPERATOR_BIDANG_3_USERNAME', 'operator_bidang_3'),
                'email' => env('OPERATOR_BIDANG_3_EMAIL', 'operator.bidang3@localhost'),
                'password' => env('OPERATOR_BIDANG_3_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Bidang 4',
                'username' => env('OPERATOR_BIDANG_4_USERNAME', 'operator_bidang_4'),
                'email' => env('OPERATOR_BIDANG_4_EMAIL', 'operator.bidang4@localhost'),
                'password' => env('OPERATOR_BIDANG_4_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Bidang 5',
                'username' => env('OPERATOR_BIDANG_5_USERNAME', 'operator_bidang_5'),
                'email' => env('OPERATOR_BIDANG_5_EMAIL', 'operator.bidang5@localhost'),
                'password' => env('OPERATOR_BIDANG_5_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Bidang Investigasi',
                'username' => env('OPERATOR_BIDANG_INVESTIGASI_USERNAME', env('OPERATOR_BIDANG_INVESTIGAS_USERNAME', 'operator_bidang_investigasi')),
                'email' => env('OPERATOR_BIDANG_INVESTIGASI_EMAIL', env('OPERATOR_BIDANG_INVESTIGAS_EMAIL', 'operator.bidang.investigasi@localhost')),
                'password' => env('OPERATOR_BIDANG_INVESTIGASI_PASSWORD', env('OPERATOR_BIDANG_INVESTIGAS_PASSWORD')),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Wilayah JB',
                'username' => env('OPERATOR_WILAYAH_JB_USERNAME', 'operator_wilayah_jb'),
                'email' => env('OPERATOR_WILAYAH_JB_EMAIL', 'operator.wilayah.jb@localhost'),
                'password' => env('OPERATOR_WILAYAH_JB_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Wilayah JS',
                'username' => env('OPERATOR_WILAYAH_JS_USERNAME', 'operator_wilayah_js'),
                'email' => env('OPERATOR_WILAYAH_JS_EMAIL', 'operator.wilayah.js@localhost'),
                'password' => env('OPERATOR_WILAYAH_JS_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Wilayah JT',
                'username' => env('OPERATOR_WILAYAH_JT_USERNAME', 'operator_wilayah_jt'),
                'email' => env('OPERATOR_WILAYAH_JT_EMAIL', 'operator.wilayah.jt@localhost'),
                'password' => env('OPERATOR_WILAYAH_JT_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Wilayah JP',
                'username' => env('OPERATOR_WILAYAH_JP_USERNAME', 'operator_wilayah_jp'),
                'email' => env('OPERATOR_WILAYAH_JP_EMAIL', 'operator.wilayah.jp@localhost'),
                'password' => env('OPERATOR_WILAYAH_JP_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Wilayah JU',
                'username' => env('OPERATOR_WILAYAH_JU_USERNAME', 'operator_wilayah_ju'),
                'email' => env('OPERATOR_WILAYAH_JU_EMAIL', 'operator.wilayah.ju@localhost'),
                'password' => env('OPERATOR_WILAYAH_JU_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
            [
                'name' => 'Operator Wilayah KS',
                'username' => env('OPERATOR_WILAYAH_KS_USERNAME', 'operator_wilayah_ks'),
                'email' => env('OPERATOR_WILAYAH_KS_EMAIL', 'operator.wilayah.ks@localhost'),
                'password' => env('OPERATOR_WILAYAH_KS_PASSWORD'),
                'role' => 'bidang_tujuan',
            ],
        ];

        $activeOperatorUsernames = collect($accounts)
            ->where('role', 'bidang_tujuan')
            ->pluck('username')
            ->filter()
            ->values()
            ->all();

        foreach ($accounts as $account) {
            if (!is_string($account['password']) || $account['password'] === '') {
                continue;
            }

            $email = $this->resolveUniqueEmail(
                (string) $account['email'],
                (string) $account['username']
            );

            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'email' => $email,
                    'role' => $account['role'],
                    'password' => Hash::make($account['password']),
                    'email_verified_at' => now(),
                ]
            );
        }

        User::query()
            ->where('role', 'bidang_tujuan')
            ->whereIn('username', ['operator_bidang'])
            ->whereNotIn('username', $activeOperatorUsernames)
            ->delete();
    }

    private function migrateLegacySekretarisPimpinan(): void
    {
        $legacyUsername = 'asisten_pimpinan';
        $legacyEmail = 'asisten.pimpinan@localhost';
        $newUsername = 'sekretaris_pimpinan';
        $newEmail = 'sekretaris.pimpinan@localhost';
        $newName = 'Sekretaris Pimpinan';

        $legacyUser = User::query()->where('username', $legacyUsername)->first();
        if (!$legacyUser) {
            return;
        }

        $newUsernameExists = User::query()
            ->where('username', $newUsername)
            ->where('id', '!=', $legacyUser->id)
            ->exists();

        if ($newUsernameExists) {
            $legacyUser->delete();
            return;
        }

        $legacyUser->username = $newUsername;
        $legacyUser->role = 'sekretaris_pimpinan';
        $legacyUser->name = $newName;

        if ($legacyUser->email === $legacyEmail) {
            $legacyUser->email = $newEmail;
        }

        $legacyUser->save();
    }

    private function migrateLegacyInvestigasiOperator(): void
    {
        $legacyUsername = 'operator_bidang_investigas';
        $legacyEmail = 'operator.bidang.investigas@localhost';
        $newUsername = 'operator_bidang_investigasi';
        $newEmail = 'operator.bidang.investigasi@localhost';
        $newName = 'Operator Bidang Investigasi';

        $legacyUser = User::query()->where('username', $legacyUsername)->first();
        if (!$legacyUser) {
            return;
        }

        $newUsernameExists = User::query()
            ->where('username', $newUsername)
            ->where('id', '!=', $legacyUser->id)
            ->exists();

        if (!$newUsernameExists) {
            $legacyUser->username = $newUsername;
        }

        $legacyUser->name = $newName;

        if ($legacyUser->email === $legacyEmail) {
            $legacyUser->email = $newEmail;
        }

        $legacyUser->save();
    }

    private function resolveUniqueEmail(string $email, string $username): string
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            $email = strtolower(trim($username)) . '@localhost';
        }

        [$localPart, $domainPart] = array_pad(explode('@', $email, 2), 2, 'localhost');
        $localPart = preg_replace('/[^a-z0-9._-]+/', '', $localPart) ?: 'admin';
        $domainPart = preg_replace('/[^a-z0-9.-]+/', '', $domainPart) ?: 'localhost';

        $candidate = $localPart . '@' . $domainPart;
        $counter = 2;

        while (
            User::query()
                ->where('email', $candidate)
                ->where('username', '!=', $username)
                ->exists()
        ) {
            $candidate = $localPart . $counter . '@' . $domainPart;
            $counter++;
        }

        return $candidate;
    }
}
