<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'balasan_path')) {
                $table->string('balasan_path')->nullable()->after('lampiran_data');
            }

            if (!Schema::hasColumn('data_kunjungan', 'balasan_nama')) {
                $table->string('balasan_nama')->nullable()->after('balasan_path');
            }

            if (!Schema::hasColumn('data_kunjungan', 'balasan_mime')) {
                $table->string('balasan_mime', 120)->nullable()->after('balasan_nama');
            }

            if (!Schema::hasColumn('data_kunjungan', 'balasan_data')) {
                $table->longText('balasan_data')->nullable()->after('balasan_mime');
            }

            if (!Schema::hasColumn('data_kunjungan', 'balasan_uploaded_by')) {
                $table->unsignedBigInteger('balasan_uploaded_by')->nullable()->after('balasan_data');
            }

            if (!Schema::hasColumn('data_kunjungan', 'balasan_uploaded_at')) {
                $table->timestamp('balasan_uploaded_at')->nullable()->after('balasan_uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            $columns = [
                'balasan_uploaded_at',
                'balasan_uploaded_by',
                'balasan_data',
                'balasan_mime',
                'balasan_nama',
                'balasan_path',
            ];

            $drop = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('data_kunjungan', $column)));
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
