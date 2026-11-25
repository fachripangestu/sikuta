<?php

namespace App\Http\Controllers;

use App\Models\Tamu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuestFormController extends Controller
{
    public function index(): View
    {
        try {
            $recentKunjungan = Tamu::orderByDesc('created_at')->get();
            $yearlyRows = Tamu::query()
                ->selectRaw('YEAR(COALESCE(tanggal_usulan, created_at)) as tahun, COUNT(*) as total')
                ->whereNotNull('created_at')
                ->groupBy(DB::raw('YEAR(COALESCE(tanggal_usulan, created_at))'))
                ->orderBy(DB::raw('YEAR(COALESCE(tanggal_usulan, created_at))'))
                ->get();
            $chartYearLabels = $yearlyRows->pluck('tahun')->map(fn ($year) => (string) $year)->values()->all();
            $chartYearSeries = $yearlyRows->pluck('total')->map(fn ($count) => (int) $count)->values()->all();
            $dbDown = false;
        } catch (\Throwable $e) {
            Log::error('Gagal membuka halaman depan karena koneksi database.', ['message' => $e->getMessage()]);
            $recentKunjungan = collect();
            $chartYearLabels = [];
            $chartYearSeries = [];
            $dbDown = true;
        }

        return view('guest-form-v2', compact(
            'recentKunjungan',
            'chartYearLabels',
            'chartYearSeries',
            'dbDown'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nomor_surat' => 'required|string|max:120',
            'instansi' => 'required|string|max:255',
            'instansi_provinsi' => 'required|string|max:120',
            'instansi_kabupaten_kota' => 'required|string|max:120',
            'jabatan' => 'required|string|max:255',
            'telepon' => 'required|string|max:30',
            'tujuan' => 'required|string|max:255',
            'tanggal_usulan' => 'required|date',
            'jumlah_peserta' => 'required|integer|min:1|max:5000',
            'keperluan' => 'required|string',
            'lampiran' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'nama.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'nomor_surat.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'instansi.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'instansi_provinsi.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'instansi_kabupaten_kota.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'jabatan.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'telepon.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'tujuan.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'tanggal_usulan.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'jumlah_peserta.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'keperluan.required' => 'Semua kolom wajib harus diisi sebelum mengirim permohonan.',
            'lampiran.required' => 'Lampiran wajib diunggah (PDF/JPG/PNG).',
        ]);

        $path = null;
        $lampiranNama = null;
        $lampiranMime = null;
        $lampiranData = null;

        if ($request->hasFile('lampiran')) {
            $uploadedLampiran = $request->file('lampiran');

            $lampiranNama = trim((string) $uploadedLampiran->getClientOriginalName());
            $lampiranMime = trim((string) ($uploadedLampiran->getClientMimeType() ?: $uploadedLampiran->getMimeType() ?: 'application/octet-stream'));

            $rawLampiran = @file_get_contents($uploadedLampiran->getRealPath());
            if ($rawLampiran !== false) {
                $lampiranData = base64_encode($rawLampiran);
            }

            try {
                $path = $uploadedLampiran->store('uploads_tamu', 'public');
            } catch (\Throwable $e) {
                Log::warning('Gagal menyimpan lampiran fisik ke storage publik.', ['message' => $e->getMessage()]);
                $path = null;
            }
        }

        try {
            Tamu::create([
                'nama' => $validated['nama'],
                'nomor_surat' => $validated['nomor_surat'],
                'instansi' => $validated['instansi'],
                'instansi_provinsi' => $validated['instansi_provinsi'],
                'instansi_kabupaten_kota' => $validated['instansi_kabupaten_kota'],
                'jabatan' => $validated['jabatan'],
                'telepon' => $validated['telepon'],
                'tujuan' => $validated['tujuan'],
                'tanggal_usulan' => $validated['tanggal_usulan'],
                'jumlah_peserta' => $validated['jumlah_peserta'],
                'keperluan' => $validated['keperluan'],
                'lampiran_path' => $path,
                'lampiran_nama' => $lampiranNama ?: null,
                'lampiran_mime' => $lampiranMime ?: null,
                'lampiran_data' => $lampiranData ?: null,
                'status' => 'menunggu',
                'workflow_step' => 'data_entry',
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan data tamu.', ['message' => $e->getMessage()]);

            return back()
                ->withErrors(['nama' => 'Data belum bisa disimpan karena koneksi database tidak tersedia. Jalankan MySQL terlebih dulu.'])
                ->withInput();
        }

        return redirect()
            ->route('guest.form')
            ->with('guest_success', 'Permohonan kunjungan berhasil dikirim dan sedang menunggu verifikasi internal.');
    }

    public function balasanRaw(Tamu $tamu)
    {
        return $this->streamDocument(
            dbData: (string) ($tamu->balasan_data ?? ''),
            dbMime: (string) ($tamu->balasan_mime ?? ''),
            dbName: (string) ($tamu->balasan_nama ?? ''),
            path: (string) ($tamu->balasan_path ?? ''),
            fallbackFilename: 'balasan-' . $tamu->id,
            unavailableMessage: 'Dokumen surat balasan belum tersedia.',
            notFoundMessage: 'Dokumen surat balasan tidak ditemukan.'
        );
    }

    private function streamDocument(
        string $dbData,
        string $dbMime,
        string $dbName,
        string $path,
        string $fallbackFilename,
        string $unavailableMessage,
        string $notFoundMessage
    ) {
        $dbData = trim($dbData);
        if ($dbData !== '') {
            $decoded = base64_decode($dbData, true);

            if ($decoded !== false) {
                $mimeType = trim($dbMime) !== '' ? trim($dbMime) : 'application/octet-stream';
                $filename = trim($dbName) !== '' ? trim($dbName) : $fallbackFilename;

                return response($decoded, 200, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
                ]);
            }
        }

        $relativePath = trim($path);
        if ($relativePath === '') {
            abort(404, $unavailableMessage);
        }

        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');
        if (str_starts_with($relativePath, 'public/')) {
            $relativePath = substr($relativePath, 7);
        }

        if (!Storage::disk('public')->exists($relativePath)) {
            abort(404, $notFoundMessage);
        }

        $mimeType = trim($dbMime);
        if ($mimeType === '') {
            try {
                $mimeType = (string) (Storage::disk('public')->mimeType($relativePath) ?: 'application/octet-stream');
            } catch (\Throwable $e) {
                $mimeType = 'application/octet-stream';
            }
        }

        $filename = trim($dbName);
        if ($filename === '') {
            $filename = basename($relativePath) ?: $fallbackFilename;
        }

        return response()->stream(function () use ($relativePath): void {
            $stream = Storage::disk('public')->readStream($relativePath);
            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }
}
