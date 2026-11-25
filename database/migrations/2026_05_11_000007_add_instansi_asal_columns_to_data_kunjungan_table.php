<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_kunjungan')) {
            return;
        }

        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'instansi_provinsi')) {
                $table->string('instansi_provinsi', 120)->nullable()->after('instansi');
            }

            if (!Schema::hasColumn('data_kunjungan', 'instansi_kabupaten_kota')) {
                $table->string('instansi_kabupaten_kota', 120)->nullable()->after('instansi_provinsi');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_kunjungan')) {
            return;
        }

        Schema::table('data_kunjungan', function (Blueprint $table) {
            $drop = [];

            foreach (['instansi_provinsi', 'instansi_kabupaten_kota'] as $column) {
                if (Schema::hasColumn('data_kunjungan', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
