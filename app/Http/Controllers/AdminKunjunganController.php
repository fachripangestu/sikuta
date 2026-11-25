<?php

namespace App\Http\Controllers;

use App\Models\Tamu;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminKunjunganController extends Controller
{
    public function verify(Request $request, Tamu $tamu): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:initial_review,approve,reschedule,confirm_visit',
            'note' => 'nullable|string|max:1000',
            'operator_user_id' => 'nullable|integer|min:1',
        ]);

        $adminRole = (string) $request->session()->get('admin_role');
        $adminUserId = (int) $request->session()->get('admin_user_id');

        if ($tamu->workflow_step !== $adminRole) {
            return back()->withErrors(['verify' => 'Data ini tidak berada pada antrean verifikasi level Anda.']);
        }

        if ($adminRole === 'bidang_tujuan') {
            if (is_null($tamu->assigned_operator_id)) {
                return back()->withErrors(['verify' => 'Data ini belum dialokasikan oleh Sekretaris Pimpinan ke operator bidang/wilayah tertentu.']);
            }

            if ((int) $tamu->assigned_operator_id !== $adminUserId) {
                return back()->withErrors(['verify' => 'Data ini dialokasikan ke operator bidang/wilayah lain.']);
            }
        }

        if ($tamu->workflow_step === 'selesai') {
            return back()->withErrors(['verify' => 'Data kunjungan ini sudah selesai diverifikasi.']);
        }

        $now = now();
        $updates = [];

        if (!empty($validated['note'])) {
            $updates['verification_note'] = trim($validated['note']);
        }

        if ($adminRole === 'data_entry') {
            if ($validated['action'] !== 'initial_review') {
                return back()->withErrors(['verify' => 'Data Entry hanya dapat melakukan verifikasi awal.']);
            }

            $updates = array_merge($updates, [
                'entry_verified_by' => $adminUserId,
                'entry_verified_at' => $now,
                'assigned_operator_id' => null,
                'status' => 'diproses',
                'workflow_step' => 'sekretaris_pimpinan',
            ]);
            $flash = 'Verifikasi awal selesai. Permohonan diteruskan ke Sekretaris Pimpinan.';
        } elseif ($adminRole === 'sekretaris_pimpinan') {
            if (!in_array($validated['action'], ['approve', 'reschedule'], true)) {
                return back()->withErrors(['verify' => 'Sekretaris Pimpinan hanya dapat menerima atau menunda/reschedule permohonan.']);
            }

            $updates = array_merge($updates, [
                'assistant_verified_by' => $adminUserId,
                'assistant_verified_at' => $now,
            ]);

            if ($validated['action'] === 'approve') {
                $operatorUserId = (int) ($validated['operator_user_id'] ?? 0);
                if ($operatorUserId <= 0) {
                    return back()->withErrors(['verify' => 'Pilih operator bidang/wilayah tujuan sebelum menyetujui permohonan.']);
                }

                $operatorUser = User::query()
                    ->where('id', $operatorUserId)
                    ->where('role', 'bidang_tujuan')
                    ->where('is_active', true)
                    ->first();

                if (!$operatorUser) {
                    return back()->withErrors(['verify' => 'Operator bidang/wilayah yang dipilih tidak valid atau sedang nonaktif.']);
                }

                $updates['status'] = 'diterima';
                $updates['assigned_operator_id'] = (int) $operatorUser->id;
                $updates['workflow_step'] = 'bidang_tujuan';
                $flash = 'Permohonan diterima Sekretaris Pimpinan dan diteruskan ke Operator ' . trim((string) $operatorUser->name) . '.';
            } else {
                $rescheduleNote = trim((string) ($validated['note'] ?? ''));
                if ($rescheduleNote === '') {
                    return back()->withErrors(['verify' => 'Keterangan penundaan wajib diisi saat memilih Ditunda.']);
                }

                $updates['status'] = 'ditunda';
                $updates['verification_note'] = $rescheduleNote;
                $updates['reschedule_at'] = null;
                $updates['assigned_operator_id'] = null;
                $updates['workflow_step'] = 'subbag_umum';
                $flash = 'Permohonan ditunda. Keterangan penundaan diteruskan ke Subbagian Umum untuk unggah surat balasan.';
            }
        } elseif ($adminRole === 'bidang_tujuan') {
            if ($validated['action'] !== 'confirm_visit') {
                return back()->withErrors(['verify' => 'Operator Bidang/Wilayah hanya dapat mengonfirmasi kunjungan.']);
            }

            $updates = array_merge($updates, [
                'bidang_verified_by' => $adminUserId,
                'bidang_verified_at' => $now,
                'status' => 'terkonfirmasi',
                'workflow_step' => 'subbag_umum',
            ]);
            $flash = 'Kunjungan terkonfirmasi. Permohonan diteruskan ke Subbagian Umum untuk unggah surat balasan.';
        } else {
            return back()->withErrors(['verify' => 'Role admin tidak dikenali untuk proses verifikasi.']);
        }

        try {
            $tamu->update($updates);
        } catch (\Throwable $e) {
            Log::error('Gagal memproses verifikasi berjenjang.', ['message' => $e->getMessage()]);

            return back()->withErrors(['verify' => 'Verifikasi gagal karena koneksi database tidak tersedia.']);
        }

        return back()
            ->with('admin_notice', $flash)
            ->with('verified_id', (int) $tamu->id);
    }

    public function lampiran(Tamu $tamu)
    {
        $filename = trim((string) ($tamu->lampiran_nama ?? ''));
        if ($filename === '') {
            $filename = 'Lampiran Kunjungan #' . $tamu->id;
        }

        return view('admin.lampiran', [
            'tamu' => $tamu,
            'filename' => $filename,
            'rawRoute' => route('admin.kunjungan.lampiran.raw', $tamu),
            'viewerTitle' => 'Lampiran ' . $filename,
        ]);
    }

    public function lampiranRaw(Tamu $tamu)
    {
        return $this->streamDocument(
            dbData: (string) ($tamu->lampiran_data ?? ''),
            dbMime: (string) ($tamu->lampiran_mime ?? ''),
            dbName: (string) ($tamu->lampiran_nama ?? ''),
            path: (string) ($tamu->lampiran_path ?? ''),
            fallbackFilename: 'lampiran-' . $tamu->id,
            unavailableMessage: 'Lampiran tidak tersedia.',
            notFoundMessage: 'Lampiran tidak ditemukan.'
        );
    }

    public function uploadBalasan(Request $request, Tamu $tamu): RedirectResponse
    {
        $adminRole = (string) $request->session()->get('admin_role');
        if ($adminRole !== 'subbag_umum') {
            return back()->withErrors(['verify' => 'Hanya Subbagian Umum yang dapat mengunggah dokumen balasan.']);
        }

        $statusKey = strtolower(trim((string) ($tamu->status ?? '')));
        $isSubbagQueue = $tamu->workflow_step === 'subbag_umum';
        $isConfirmed = $statusKey === 'terkonfirmasi';
        $isRescheduled = $statusKey === 'ditunda';
        $hasBalasan = !empty($tamu->balasan_uploaded_at) || !empty($tamu->balasan_path) || !empty($tamu->balasan_data);
        if (!$isSubbagQueue && !$isConfirmed && !$isRescheduled && !$hasBalasan) {
            return back()->withErrors(['verify' => 'Dokumen balasan hanya dapat diunggah untuk permohonan berstatus terkonfirmasi atau ditunda.']);
        }

        $validated = $request->validate([
            'balasan_dokumen' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'balasan_dokumen.required' => 'Dokumen balasan wajib diunggah (PDF/JPG/PNG).',
            'balasan_dokumen.mimes' => 'Dokumen balasan wajib berformat PDF/JPG/PNG.',
            'balasan_dokumen.max' => 'Ukuran dokumen balasan maksimal 5 MB.',
        ]);

        if (is_null($tamu->bidang_verified_at) && !$isRescheduled) {
            return back()->withErrors(['verify' => 'Dokumen balasan hanya dapat diunggah setelah disetujui oleh Operator Bidang/Wilayah.']);
        }

        $uploadedBalasan = $validated['balasan_dokumen'];
        $balasanNama = trim((string) $uploadedBalasan->getClientOriginalName());
        $balasanMime = trim((string) ($uploadedBalasan->getClientMimeType() ?: $uploadedBalasan->getMimeType() ?: 'application/octet-stream'));
        $balasanData = null;

        try {
            $rawBalasan = $uploadedBalasan->get();
        } catch (\Throwable $e) {
            $rawBalasan = @file_get_contents($uploadedBalasan->getRealPath());
        }

        if (is_string($rawBalasan) && $rawBalasan !== '') {
            $balasanData = base64_encode($rawBalasan);
        }

        try {
            $balasanPath = $uploadedBalasan->store('uploads_balasan', 'public');
        } catch (\Throwable $e) {
            Log::warning('Gagal menyimpan dokumen balasan fisik ke storage publik.', ['message' => $e->getMessage()]);

            $balasanPath = null;
        }

        try {
            $nextStatus = $isRescheduled ? 'ditunda' : 'selesai';
            $tamu->update([
                'balasan_path' => $balasanPath,
                'balasan_nama' => $balasanNama !== '' ? $balasanNama : null,
                'balasan_mime' => $balasanMime !== '' ? $balasanMime : null,
                'balasan_data' => $balasanData ?: null,
                'balasan_uploaded_by' => (int) $request->session()->get('admin_user_id'),
                'balasan_uploaded_at' => now(),
                'status' => $nextStatus,
                'workflow_step' => 'selesai',
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan dokumen balasan permohonan kunjungan.', ['message' => $e->getMessage()]);

            return back()->withErrors(['verify' => 'Dokumen balasan gagal disimpan karena koneksi database tidak tersedia.']);
        }

        $notice = $isRescheduled
            ? 'Dokumen balasan penundaan berhasil diunggah oleh Subbagian Umum.'
            : 'Dokumen balasan berhasil diunggah oleh Subbagian Umum.';

        return back()
            ->with('admin_notice', $notice)
            ->with('verified_id', (int) $tamu->id);
    }

    public function deleteBalasan(Request $request, Tamu $tamu): RedirectResponse
    {
        $adminRole = (string) $request->session()->get('admin_role');
        if ($adminRole !== 'subbag_umum') {
            return back()->withErrors(['verify' => 'Hanya Subbagian Umum yang dapat menghapus dokumen balasan.']);
        }

        $hasBalasan = !empty($tamu->balasan_uploaded_at) || !empty($tamu->balasan_path) || !empty($tamu->balasan_data);
        if (!$hasBalasan) {
            return back()->withErrors(['verify' => 'Dokumen balasan belum tersedia untuk dihapus.']);
        }

        $this->deleteStoredBalasanFile((string) ($tamu->balasan_path ?? ''));

        $statusKey = strtolower(trim((string) ($tamu->status ?? '')));
        $hasRescheduleNote = trim((string) ($tamu->verification_note ?? '')) !== '';
        $wasRescheduleFlow = is_null($tamu->bidang_verified_at)
            && $hasRescheduleNote
            && in_array($statusKey, ['ditunda', 'selesai'], true);
        $nextStatus = $wasRescheduleFlow
            ? 'ditunda'
            : (!is_null($tamu->bidang_verified_at) ? 'terkonfirmasi' : (string) ($tamu->status ?? 'menunggu'));
        $nextWorkflowStep = ($wasRescheduleFlow || !is_null($tamu->bidang_verified_at))
            ? 'subbag_umum'
            : (string) ($tamu->workflow_step ?? 'data_entry');

        try {
            $tamu->update([
                'balasan_path' => null,
                'balasan_nama' => null,
                'balasan_mime' => null,
                'balasan_data' => null,
                'balasan_uploaded_by' => null,
                'balasan_uploaded_at' => null,
                'status' => $nextStatus,
                'workflow_step' => $nextWorkflowStep,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus dokumen balasan permohonan kunjungan.', ['message' => $e->getMessage()]);

            return back()->withErrors(['verify' => 'Dokumen balasan gagal dihapus karena koneksi database tidak tersedia.']);
        }

        return back()
            ->with('admin_notice', 'Dokumen balasan berhasil dihapus. Silakan unggah dokumen balasan terbaru.')
            ->with('verified_id', (int) $tamu->id);
    }

    public function balasan(Tamu $tamu)
    {
        $filename = trim((string) ($tamu->balasan_nama ?? ''));
        if ($filename === '') {
            $filename = 'Dokumen Balasan #' . $tamu->id;
        }

        if (trim((string) ($tamu->balasan_data ?? '')) === '' && trim((string) ($tamu->balasan_path ?? '')) === '') {
            abort(404, 'Dokumen balasan tidak tersedia.');
        }

        return view('admin.lampiran', [
            'tamu' => $tamu,
            'filename' => $filename,
            'rawRoute' => route('admin.kunjungan.balasan.raw', $tamu),
            'viewerTitle' => 'Dokumen Balasan ' . $filename,
        ]);
    }

    public function balasanRaw(Tamu $tamu)
    {
        return $this->streamDocument(
            dbData: (string) ($tamu->balasan_data ?? ''),
            dbMime: (string) ($tamu->balasan_mime ?? ''),
            dbName: (string) ($tamu->balasan_nama ?? ''),
            path: (string) ($tamu->balasan_path ?? ''),
            fallbackFilename: 'balasan-' . $tamu->id,
            unavailableMessage: 'Dokumen balasan tidak tersedia.',
            notFoundMessage: 'Dokumen balasan tidak ditemukan.'
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

        $absolutePath = Storage::disk('public')->path($relativePath);
        $mimeType = Storage::disk('public')->mimeType($relativePath) ?: 'application/octet-stream';
        $filename = trim($dbName) !== '' ? trim($dbName) : basename($relativePath);

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    private function deleteStoredBalasanFile(string $path): void
    {
        $relativePath = trim($path);
        if ($relativePath === '') {
            return;
        }

        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');
        if (str_starts_with($relativePath, 'public/')) {
            $relativePath = substr($relativePath, 7);
        }

        try {
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus dokumen balasan fisik dari storage publik.', ['message' => $e->getMessage()]);
        }
    }
}
