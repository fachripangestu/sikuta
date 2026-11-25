<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tamus') && !Schema::hasTable('data_kunjungan')) {
            Schema::rename('tamus', 'data_kunjungan');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_kunjungan') && !Schema::hasTable('tamus')) {
            Schema::rename('data_kunjungan', 'tamus');
        }
    }
};
