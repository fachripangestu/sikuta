<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'status')) {
                $table->string('status', 40)->default('menunggu')->after('lampiran_path');
            }
        });

        DB::table('data_kunjungan')
            ->whereNull('status')
            ->update(['status' => 'menunggu']);
    }

    public function down(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (Schema::hasColumn('data_kunjungan', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
