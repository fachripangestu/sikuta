<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 80)->nullable()->unique()->after('name');
            }
        });

        if (!Schema::hasColumn('users', 'username')) {
            return;
        }

        $existing = [];
        $rows = DB::table('users')
            ->select('id', 'role', 'name', 'username')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $current = trim((string) ($row->username ?? ''));
            if ($current !== '') {
                $existing[$current] = true;
                continue;
            }

            $base = match ((string) ($row->role ?? '')) {
                'data_entry' => 'data_entry',
                'sekretaris_pimpinan' => 'sekretaris_pimpinan',
                'bidang_tujuan' => 'operator_bidang',
                default => '',
            };

            if ($base === '') {
                $name = strtolower(trim((string) ($row->name ?? '')));
                $name = preg_replace('/[^a-z0-9]+/', '_', $name) ?? '';
                $base = trim($name, '_');
            }

            if ($base === '') {
                $base = 'user';
            }

            $candidate = $base;
            $counter = 2;
            while (isset($existing[$candidate])) {
                $candidate = $base . '_' . $counter;
                $counter++;
            }

            DB::table('users')
                ->where('id', $row->id)
                ->update(['username' => $candidate]);

            $existing[$candidate] = true;
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
