<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'assigned_operator_id')) {
                $table->unsignedBigInteger('assigned_operator_id')->nullable()->after('assistant_verified_at');
                $table->index('assigned_operator_id', 'data_kunjungan_assigned_operator_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (Schema::hasColumn('data_kunjungan', 'assigned_operator_id')) {
                try {
                    $table->dropIndex('data_kunjungan_assigned_operator_idx');
                } catch (\Throwable) {
                    // Abaikan jika index sudah tidak ada.
                }
                $table->dropColumn('assigned_operator_id');
            }
        });
    }
};
