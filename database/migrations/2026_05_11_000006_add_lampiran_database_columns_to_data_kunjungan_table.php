<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_kunjungan')) {
            return;
        }

        Schema::table('data_kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_kunjungan', 'lampiran_nama')) {
                $table->string('lampiran_nama')->nullable()->after('lampiran_path');
            }

            if (!Schema::hasColumn('data_kunjungan', 'lampiran_mime')) {
                $table->string('lampiran_mime', 120)->nullable()->after('lampiran_nama');
            }

            if (!Schema::hasColumn('data_kunjungan', 'lampiran_data')) {
                $table->longText('lampiran_data')->nullable()->after('lampiran_mime');
            }
        });

        DB::table('data_kunjungan')
            ->select(['id', 'lampiran_path'])
            ->whereNotNull('lampiran_path')
            ->where('lampiran_path', '<>', '')
            ->where(function ($query) {
                $query->whereNull('lampiran_data')->orWhere('lampiran_data', '');
            })
            ->orderBy('id')
            ->chunkById(50, function ($rows) {
                foreach ($rows as $row) {
                    $relativePath = trim((string) ($row->lampiran_path ?? ''));
                    if ($relativePath === '') {
                        continue;
                    }

                    $relativePath = str_replace('\\', '/', $relativePath);
                    $relativePath = ltrim($relativePath, '/');
                    if (str_starts_with($relativePath, 'public/')) {
                        $relativePath = substr($relativePath, 7);
                    }

                    if ($relativePath === '' || !Storage::disk('public')->exists($relativePath)) {
                        continue;
                    }

                    $binaryContent = Storage::disk('public')->get($relativePath);
                    if ($binaryContent === false || $binaryContent === null) {
                        continue;
                    }

                    DB::table('data_kunjungan')
                        ->where('id', $row->id)
                        ->update([
                            'lampiran_nama' => basename($relativePath),
                            'lampiran_mime' => Storage::disk('public')->mimeType($relativePath) ?: 'application/octet-stream',
                            'lampiran_data' => base64_encode($binaryContent),
                        ]);
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

            foreach (['lampiran_nama', 'lampiran_mime', 'lampiran_data'] as $column) {
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
