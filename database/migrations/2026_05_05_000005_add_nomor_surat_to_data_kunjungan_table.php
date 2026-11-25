<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'nomor_surat')) {
                $table->string('nomor_surat', 120)->nullable()->after('nama');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (Schema::hasColumn('data_kunjungan', 'nomor_surat')) {
                $table->dropColumn('nomor_surat');
            }
        });
    }
};
