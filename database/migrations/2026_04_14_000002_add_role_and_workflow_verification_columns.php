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
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)->default('data_entry')->after('email');
            }
        });

        DB::table('users')
            ->whereNull('role')
            ->update(['role' => 'data_entry']);

        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'workflow_step')) {
                $table->string('workflow_step', 40)->default('data_entry')->after('status');
            }
            if (!Schema::hasColumn('data_kunjungan', 'entry_verified_by')) {
                $table->unsignedBigInteger('entry_verified_by')->nullable()->after('workflow_step');
            }
            if (!Schema::hasColumn('data_kunjungan', 'entry_verified_at')) {
                $table->timestamp('entry_verified_at')->nullable()->after('entry_verified_by');
            }
            if (!Schema::hasColumn('data_kunjungan', 'assistant_verified_by')) {
                $table->unsignedBigInteger('assistant_verified_by')->nullable()->after('entry_verified_at');
            }
            if (!Schema::hasColumn('data_kunjungan', 'assistant_verified_at')) {
                $table->timestamp('assistant_verified_at')->nullable()->after('assistant_verified_by');
            }
            if (!Schema::hasColumn('data_kunjungan', 'bidang_verified_by')) {
                $table->unsignedBigInteger('bidang_verified_by')->nullable()->after('assistant_verified_at');
            }
            if (!Schema::hasColumn('data_kunjungan', 'bidang_verified_at')) {
                $table->timestamp('bidang_verified_at')->nullable()->after('bidang_verified_by');
            }
            if (!Schema::hasColumn('data_kunjungan', 'verification_note')) {
                $table->text('verification_note')->nullable()->after('bidang_verified_at');
            }
        });

        DB::table('data_kunjungan')
            ->whereIn('status', ['diterima', 'selesai'])
            ->update(['workflow_step' => 'selesai']);

        DB::table('data_kunjungan')
            ->whereIn('status', ['ditolak', 'rejected'])
            ->update(['workflow_step' => 'selesai']);

        DB::table('data_kunjungan')
            ->whereNull('workflow_step')
            ->update(['workflow_step' => 'data_entry']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::table('data_kunjungan', function (Blueprint $table) {
            $columns = [
                'workflow_step',
                'entry_verified_by',
                'entry_verified_at',
                'assistant_verified_by',
                'assistant_verified_at',
                'bidang_verified_by',
                'bidang_verified_at',
                'verification_note',
            ];

            $drop = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('data_kunjungan', $column)));

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
