<?php

use App\Models\Tamu;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kunjungan:import-riwayat {--file= : Path file CSV riwayat} {--url= : URL CSV (opsional)} {--delimiter=, : Delimiter CSV} {--dry-run : Simulasi tanpa simpan data}', function () {
    $delimiter = (string) ($this->option('delimiter') ?: ',');
    $fileOption = trim((string) ($this->option('file') ?: ''));
    $urlOption = trim((string) ($this->option('url') ?: ''));
    $dryRun = (bool) $this->option('dry-run');

    $csvRaw = '';
    $sourceLabel = '';
    $fallbackFile = storage_path('app/import_riwayat_kunjungan.csv');

    if ($urlOption !== '') {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => "User-Agent: Mozilla/5.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($urlOption, false, $context);
        if ($response === false || trim($response) === '') {
            $this->error('Gagal mengambil CSV dari URL. Pastikan link publik bisa diakses tanpa login.');
            return Command::FAILURE;
        }

        $csvRaw = $response;
        $sourceLabel = $urlOption;
    } else {
        $path = $fileOption !== '' ? $fileOption : $fallbackFile;
        if (!Str::startsWith($path, [DIRECTORY_SEPARATOR, 'C:\\', 'D:\\', 'E:\\'])) {
            $path = base_path($path);
        }

        if (!is_file($path)) {
            $this->error("File CSV tidak ditemukan: {$path}");
            $this->line('Simpan dulu export Google Sheet ke: storage/app/import_riwayat_kunjungan.csv');
            return Command::FAILURE;
        }

        $csvRaw = (string) file_get_contents($path);
        $sourceLabel = $path;
    }

    $csvRaw = trim($csvRaw);
    if ($csvRaw === '') {
        $this->error('CSV kosong.');
        return Command::FAILURE;
    }

    $normalizeHeader = function (string $value): string {
        $value = Str::lower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = str_replace(['_', '-', '.', ':'], ' ', $value);
        $value = preg_replace('/[^a-z0-9 ]/', '', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    };

    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $csvRaw);
    rewind($stream);

    $allRows = [];
    while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
        $allRows[] = $row;
    }
    fclose($stream);

    if (empty($allRows)) {
        $this->error('CSV tidak memiliki data baris.');
        return Command::FAILURE;
    }

    $headerIndex = null;
    foreach ($allRows as $rowIndex => $rowCells) {
        $normalizedCells = array_map(fn ($cell) => $normalizeHeader((string) $cell), $rowCells);
        $hasNoSurat = in_array('no surat', $normalizedCells, true);
        $hasInstansi = in_array('pengirim surat asal instansi', $normalizedCells, true)
            || in_array('asal instansi', $normalizedCells, true)
            || in_array('instansi', $normalizedCells, true);

        if ($hasNoSurat && $hasInstansi) {
            $headerIndex = $rowIndex;
            break;
        }
    }

    if ($headerIndex === null) {
        $headerIndex = 0;
    }

    $headersRaw = $allRows[$headerIndex] ?? [];
    if (empty($headersRaw)) {
        $this->error('Header CSV tidak terbaca.');
        return Command::FAILURE;
    }

    $headers = array_map(fn ($h) => $normalizeHeader((string) $h), $headersRaw);

    $pick = function (array $row, array $aliases) use ($normalizeHeader): string {
        foreach ($aliases as $alias) {
            $key = $normalizeHeader($alias);
            if (array_key_exists($key, $row)) {
                $value = trim((string) ($row[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return '';
    };

    $parseDate = function (string $value): ?Carbon {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $idMonthMap = [
            'januari' => 'january',
            'februari' => 'february',
            'maret' => 'march',
            'april' => 'april',
            'mei' => 'may',
            'juni' => 'june',
            'juli' => 'july',
            'agustus' => 'august',
            'september' => 'september',
            'oktober' => 'october',
            'nopember' => 'november',
            'november' => 'november',
            'desember' => 'december',
        ];

        $value = Str::lower($value);
        foreach ($idMonthMap as $id => $en) {
            $value = preg_replace('/\b' . preg_quote($id, '/') . '\b/u', $en, $value) ?? $value;
        }
        $value = trim(str_replace([' ,', ', '], ' ', $value));

        if (is_numeric($value)) {
            $excelSerial = (float) $value;
            if ($excelSerial > 20000) {
                return Carbon::create(1899, 12, 30)->addDays((int) floor($excelSerial));
            }
        }

        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'd/m/y',
            'd.m.Y',
            'd M Y',
            'd F Y',
            'm/d/Y',
            'd-m-Y H:i',
            'd/m/Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                // lanjut format berikutnya
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    };

    $normalizeStatus = function (string $raw): array {
        $value = Str::lower(trim($raw));

        if ($value === '' || Str::contains($value, ['selesai', 'terkonfirmasi'])) {
            return ['status' => 'selesai', 'workflow' => 'selesai'];
        }

        if (Str::contains($value, ['ditunda', 'reschedule', 'jadwal ulang'])) {
            return ['status' => 'ditunda', 'workflow' => 'sekretaris_pimpinan'];
        }

        if (Str::contains($value, ['ditolak', 'rejected', 'tolak'])) {
            return ['status' => 'ditolak', 'workflow' => 'selesai'];
        }

        if (Str::contains($value, ['menunggu', 'pending'])) {
            return ['status' => 'menunggu', 'workflow' => 'data_entry'];
        }

        if (Str::contains($value, ['diproses', 'proses', 'operator'])) {
            return ['status' => 'diproses', 'workflow' => 'bidang_tujuan'];
        }

        if (Str::contains($value, ['diterima', 'disetujui', 'approved'])) {
            return ['status' => 'diterima', 'workflow' => 'bidang_tujuan'];
        }

        return ['status' => 'selesai', 'workflow' => 'selesai'];
    };

    $totalRow = 0;
    $createdCount = 0;
    $updatedCount = 0;
    $skippedCount = 0;

    for ($rowIdx = $headerIndex + 1; $rowIdx < count($allRows); $rowIdx++) {
        $rowRaw = $allRows[$rowIdx];
        $totalRow++;
        $rowAssoc = [];
        foreach ($headers as $idx => $headerKey) {
            $rowAssoc[$headerKey] = isset($rowRaw[$idx]) ? trim((string) $rowRaw[$idx]) : '';
        }

        if (collect($rowAssoc)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
            $skippedCount++;
            continue;
        }

        $nama = $pick($rowAssoc, ['nama pemohon', 'nama', 'pemohon', 'name']);
        $instansi = $pick($rowAssoc, ['instansi pemohon', 'instansi', 'asal instansi', 'pengirim surat asal instansi', 'nama instansi']);
        $provinsi = $pick($rowAssoc, ['asal provinsi', 'provinsi', 'provinsi instansi', 'instansi provinsi']);
        $kabKota = $pick($rowAssoc, ['asal kabupaten/kota', 'kabupaten/kota', 'kabupaten kota', 'kota', 'kabupaten', 'instansi kabupaten kota']);
        $nomorSurat = $pick($rowAssoc, ['nomor surat', 'no surat', 'nomor_surat', 'no. surat']);
        $jabatan = $pick($rowAssoc, ['jabatan pemohon', 'jabatan']);
        $telepon = $pick($rowAssoc, ['nomor wa', 'no wa', 'telepon', 'hp', 'whatsapp']);
        $tujuan = $pick($rowAssoc, ['tujuan', 'jenis kunjungan', 'judul kunjungan']);
        $keperluan = $pick($rowAssoc, ['keperluan', 'perihal keperluan', 'perihal', 'keterangan', 'deskripsi', 'catatan']);
        $tanggalRaw = $pick($rowAssoc, ['tanggal usulan', 'tanggal surat', 'tanggal', 'tgl', 'tanggal kunjungan']);
        $jumlahRaw = $pick($rowAssoc, ['jumlah pemohon kunjungan', 'jumlah peserta', 'jumlah', 'peserta']);
        $statusRaw = $pick($rowAssoc, ['status permohonan', 'status']);

        if ($nama === '' && $instansi === '') {
            $skippedCount++;
            continue;
        }

        $tanggalUsulan = $parseDate($tanggalRaw);
        $jumlahPeserta = (int) preg_replace('/[^0-9]/', '', $jumlahRaw);
        $jumlahPeserta = $jumlahPeserta > 0 ? $jumlahPeserta : 1;

        $statusData = $normalizeStatus($statusRaw);
        $status = $statusData['status'];
        $workflowStep = $statusData['workflow'];
        $timeStamp = $tanggalUsulan ? $tanggalUsulan->copy()->startOfDay() : now();

        $where = Tamu::query();
        if ($nomorSurat !== '') {
            $where->where('nomor_surat', $nomorSurat);
        }
        $where->where('nama', $nama !== '' ? $nama : '-');
        $where->where('instansi', $instansi !== '' ? $instansi : '-');
        if ($tanggalUsulan) {
            $where->whereDate('tanggal_usulan', $tanggalUsulan->toDateString());
        }

        $existing = $where->first();

        $payload = [
            'nama' => $nama !== '' ? $nama : '-',
            'nomor_surat' => $nomorSurat !== '' ? $nomorSurat : null,
            'instansi' => $instansi !== '' ? $instansi : '-',
            'instansi_provinsi' => $provinsi !== '' ? $provinsi : null,
            'instansi_kabupaten_kota' => $kabKota !== '' ? $kabKota : null,
            'jabatan' => $jabatan !== '' ? $jabatan : '-',
            'telepon' => $telepon !== '' ? $telepon : null,
            'tujuan' => $tujuan !== '' ? $tujuan : 'Kunjungan Studi Tiru',
            'tanggal_usulan' => $tanggalUsulan?->toDateString(),
            'jumlah_peserta' => $jumlahPeserta,
            'keperluan' => $keperluan !== '' ? $keperluan : 'Riwayat kunjungan terdahulu (import).',
            'status' => $status,
            'workflow_step' => $workflowStep,
            'verification_note' => 'Import riwayat terdahulu dari: ' . $sourceLabel,
        ];

        if ($existing) {
            if (!$dryRun) {
                $existing->fill(array_filter($payload, fn ($v) => $v !== null && $v !== ''))->save();
            }
            $updatedCount++;
            continue;
        }

        if (!$dryRun) {
            $newRow = new Tamu();
            $newRow->fill($payload);
            $newRow->created_at = $timeStamp;
            $newRow->updated_at = $timeStamp;
            $newRow->save();
        }

        $createdCount++;
    }

    $this->info('Import riwayat selesai.');
    $this->line("Sumber      : {$sourceLabel}");
    $this->line("Total baris : {$totalRow}");
    $this->line("Data baru   : {$createdCount}");
    $this->line("Update data : {$updatedCount}");
    $this->line("Dilewati    : {$skippedCount}");
    $this->line($dryRun ? 'Mode        : DRY RUN (tanpa simpan).' : 'Mode        : SIMPAN KE DATABASE.');

    return Command::SUCCESS;
})->purpose('Import riwayat kunjungan terdahulu dari CSV ke data_kunjungan.');
