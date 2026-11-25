<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'asisten_pimpinan')
                ->update(['role' => 'sekretaris_pimpinan']);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
            DB::table('users')
                ->where('name', 'Asisten Pimpinan')
                ->update(['name' => 'Sekretaris Pimpinan']);
        }

        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'username') &&
            Schema::hasColumn('users', 'email')
        ) {
            $legacyUser = DB::table('users')
                ->select('id')
                ->where('username', 'asisten_pimpinan')
                ->first();

            $newUsernameExists = DB::table('users')
                ->where('username', 'sekretaris_pimpinan')
                ->exists();

            if ($legacyUser && !$newUsernameExists) {
                DB::table('users')
                    ->where('id', $legacyUser->id)
                    ->update(['username' => 'sekretaris_pimpinan']);
            }

            DB::table('users')
                ->where('username', 'sekretaris_pimpinan')
                ->where('email', 'asisten.pimpinan@localhost')
                ->update(['email' => 'sekretaris.pimpinan@localhost']);
        }

        if (Schema::hasTable('data_kunjungan') && Schema::hasColumn('data_kunjungan', 'workflow_step')) {
            DB::table('data_kunjungan')
                ->where('workflow_step', 'asisten_pimpinan')
                ->update(['workflow_step' => 'sekretaris_pimpinan']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'sekretaris_pimpinan')
                ->update(['role' => 'asisten_pimpinan']);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
            DB::table('users')
                ->where('name', 'Sekretaris Pimpinan')
                ->update(['name' => 'Asisten Pimpinan']);
        }

        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'username') &&
            Schema::hasColumn('users', 'email')
        ) {
            $newUser = DB::table('users')
                ->select('id')
                ->where('username', 'sekretaris_pimpinan')
                ->first();

            $legacyUsernameExists = DB::table('users')
                ->where('username', 'asisten_pimpinan')
                ->exists();

            if ($newUser && !$legacyUsernameExists) {
                DB::table('users')
                    ->where('id', $newUser->id)
                    ->update(['username' => 'asisten_pimpinan']);
            }

            DB::table('users')
                ->where('username', 'asisten_pimpinan')
                ->where('email', 'sekretaris.pimpinan@localhost')
                ->update(['email' => 'asisten.pimpinan@localhost']);
        }

        if (Schema::hasTable('data_kunjungan') && Schema::hasColumn('data_kunjungan', 'workflow_step')) {
            DB::table('data_kunjungan')
                ->where('workflow_step', 'sekretaris_pimpinan')
                ->update(['workflow_step' => 'asisten_pimpinan']);
        }
    }
};
