<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'tanggal_usulan')) {
                $table->date('tanggal_usulan')->nullable()->after('tujuan');
            }

            if (!Schema::hasColumn('data_kunjungan', 'jumlah_peserta')) {
                $table->unsignedInteger('jumlah_peserta')->nullable()->after('tanggal_usulan');
            }

            if (!Schema::hasColumn('data_kunjungan', 'reschedule_at')) {
                $table->date('reschedule_at')->nullable()->after('verification_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            $columns = ['tanggal_usulan', 'jumlah_peserta', 'reschedule_at'];

            $drop = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('data_kunjungan', $column)));
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
