<?php

namespace App\Services;

use App\Models\Tamu;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminNotificationService
{
    private const ROLE_LABELS = [
        'data_entry' => 'Data Entry',
        'sekretaris_pimpinan' => 'Sekretaris Pimpinan',
        'bidang_tujuan' => 'Operator Bidang/Wilayah',
        'subbag_umum' => 'Subbagian Umum',
    ];

    private const WORKFLOW_LABELS = [
        'data_entry' => 'Menunggu Verifikasi Data Entry',
        'sekretaris_pimpinan' => 'Menunggu Telaah Sekretaris Pimpinan',
        'bidang_tujuan' => 'Menunggu Konfirmasi Operator Bidang/Wilayah',
        'subbag_umum' => 'Menunggu Surat Balasan Subbagian Umum',
        'tindak_lanjut' => 'Menunggu Tindak Lanjut',
        'selesai' => 'Proses Selesai',
    ];

    private const STATUS_LABELS = [
        'menunggu' => 'Menunggu Verifikasi',
        'pending' => 'Menunggu Verifikasi',
        'diproses' => 'Diproses',
        'diterima' => 'Disetujui',
        'terkonfirmasi' => 'Terkonfirmasi',
        'selesai' => 'Selesai',
        'ditunda' => 'Reschedule',
        'ditolak' => 'Ditolak',
        'rejected' => 'Ditolak',
    ];

    public function roleLabels(): array
    {
        return self::ROLE_LABELS;
    }

    public function workflowLabels(): array
    {
        return self::WORKFLOW_LABELS;
    }

    public function buildPayload(string $role, ?int $adminUserId = null): array
    {
        $adminUserId = (int) ($adminUserId ?? 0);

        try {
            $queueQuery = Tamu::query()
                ->with([
                    'assignedOperator:id,name,username',
                    'bidangVerifier:id,name,username',
                ])
                ->where('workflow_step', $role);
            if ($role === 'bidang_tujuan' && $adminUserId > 0) {
                $queueQuery->where('assigned_operator_id', $adminUserId);
            }

            $queueQuery->orderByDesc('updated_at');

            $pendingCount = (clone $queueQuery)->count();
            $pendingItems = (clone $queueQuery)->limit(8)->get();

            $roleQueueCounts = collect(self::ROLE_LABELS)->map(function (string $label, string $queueRole) use ($role, $adminUserId) {
                $rolePendingQuery = Tamu::query()->where('workflow_step', $queueRole);
                if ($queueRole === 'bidang_tujuan' && $role === 'bidang_tujuan' && $adminUserId > 0) {
                    $rolePendingQuery->where('assigned_operator_id', $adminUserId);
                }

                return [
                    'role' => $queueRole,
                    'role_label' => $label,
                    'pending_count' => $rolePendingQuery->count(),
                ];
            })->values()->all();

            $dataEntryEvents = Tamu::query()
                ->with([
                    'assignedOperator:id,name,username',
                    'bidangVerifier:id,name,username',
                ])
                ->whereNotNull('entry_verified_at')
                ->orderByDesc('entry_verified_at')
                ->limit(4)
                ->get()
                ->map(function (Tamu $row) {
                    $eventAt = optional($row->entry_verified_at)->timezone('Asia/Jakarta');
                    $statusKey = strtolower(trim((string) ($row->status ?? 'menunggu')));

                    return [
                        'id' => $row->id,
                        'role' => 'data_entry',
                        'role_label' => 'Data Entry',
                        'nama' => $row->nama ?: '-',
                        'asal_instansi' => $this->resolveAsalInstansi($row),
                        'status' => $statusKey,
                        'status_label' => $this->resolveStatusLabel($statusKey),
                        'subject' => trim(($row->instansi ?: 'Instansi belum diisi') . ' | ' . ($row->tujuan ?: '-')),
                        'message' => 'Verifikasi awal selesai dan diteruskan ke Sekretaris Pimpinan.',
                        'event_at' => $eventAt ? $eventAt->format('d M Y H:i') : '-',
                        'event_at_unix' => $eventAt?->timestamp ?? 0,
                        'event_at_sort' => $eventAt ? $eventAt->format('Y-m-d H:i:s') : '',
                    ];
                });

            $assistantEvents = Tamu::query()
                ->with([
                    'assignedOperator:id,name,username',
                    'bidangVerifier:id,name,username',
                ])
                ->whereNotNull('assistant_verified_at')
                ->orderByDesc('assistant_verified_at')
                ->limit(4)
                ->get()
                ->map(function (Tamu $row) {
                    $eventAt = optional($row->assistant_verified_at)->timezone('Asia/Jakarta');
                    $statusKey = strtolower((string) ($row->status ?? ''));
                    $verificationNote = trim((string) ($row->verification_note ?? ''));
                    $assignedOperatorLabel = $this->resolveAssignedOperatorLabel($row);
                    $message = $statusKey === 'ditunda'
                        ? (
                            $verificationNote !== ''
                                ? 'Sekretaris Pimpinan menunda permohonan. Keterangan: ' . $verificationNote
                                : 'Sekretaris Pimpinan menunda permohonan dan meneruskan ke Subbagian Umum.'
                        )
                        : (
                            $assignedOperatorLabel !== ''
                                ? 'Sekretaris Pimpinan menerima permohonan dan meneruskan ke Operator Bidang/Wilayah: ' . $assignedOperatorLabel . '.'
                                : 'Sekretaris Pimpinan menerima permohonan dan meneruskan ke Operator Bidang/Wilayah.'
                        );

                    return [
                        'id' => $row->id,
                        'role' => 'sekretaris_pimpinan',
                        'role_label' => 'Sekretaris Pimpinan',
                        'nama' => $row->nama ?: '-',
                        'asal_instansi' => $this->resolveAsalInstansi($row),
                        'status' => $statusKey,
                        'status_label' => $this->resolveStatusLabel($statusKey),
                        'subject' => trim(($row->instansi ?: 'Instansi belum diisi') . ' | ' . ($row->tujuan ?: '-')),
                        'message' => $message,
                        'operator_label' => $assignedOperatorLabel,
                        'event_at' => $eventAt ? $eventAt->format('d M Y H:i') : '-',
                        'event_at_unix' => $eventAt?->timestamp ?? 0,
                        'event_at_sort' => $eventAt ? $eventAt->format('Y-m-d H:i:s') : '',
                    ];
                });

            $operatorEventsQuery = Tamu::query()
                ->with([
                    'assignedOperator:id,name,username',
                    'bidangVerifier:id,name,username',
                ])
                ->whereNotNull('bidang_verified_at')
                ->orderByDesc('bidang_verified_at');

            if ($role === 'bidang_tujuan' && $adminUserId > 0) {
                $operatorEventsQuery->where(function ($query) use ($adminUserId) {
                    $query->where('bidang_verified_by', $adminUserId)
                        ->orWhere('assigned_operator_id', $adminUserId);
                });
            }

            $operatorEvents = $operatorEventsQuery
                ->limit(4)
                ->get()
                ->map(function (Tamu $row) {
                    $eventAt = optional($row->bidang_verified_at)->timezone('Asia/Jakarta');
                    $statusKey = strtolower(trim((string) ($row->status ?? 'menunggu')));
                    $operatorLabel = $this->resolveOperatorActorLabel($row);
                    $message = $operatorLabel !== ''
                        ? 'Operator Bidang/Wilayah ' . $operatorLabel . ' mengonfirmasi kunjungan dan meneruskan ke Subbagian Umum.'
                        : 'Operator Bidang/Wilayah mengonfirmasi kunjungan dan meneruskan ke Subbagian Umum.';

                    return [
                        'id' => $row->id,
                        'role' => 'bidang_tujuan',
                        'role_label' => 'Operator Bidang/Wilayah',
                        'nama' => $row->nama ?: '-',
                        'asal_instansi' => $this->resolveAsalInstansi($row),
                        'status' => $statusKey,
                        'status_label' => $this->resolveStatusLabel($statusKey),
                        'subject' => trim(($row->instansi ?: 'Instansi belum diisi') . ' | ' . ($row->tujuan ?: '-')),
                        'message' => $message,
                        'operator_label' => $operatorLabel,
                        'event_at' => $eventAt ? $eventAt->format('d M Y H:i') : '-',
                        'event_at_unix' => $eventAt?->timestamp ?? 0,
                        'event_at_sort' => $eventAt ? $eventAt->format('Y-m-d H:i:s') : '',
                    ];
                });

            $subbagEvents = Tamu::query()
                ->with([
                    'assignedOperator:id,name,username',
                    'bidangVerifier:id,name,username',
                ])
                ->whereNotNull('balasan_uploaded_at')
                ->orderByDesc('balasan_uploaded_at')
                ->limit(4)
                ->get()
                ->map(function (Tamu $row) {
                    $eventAt = optional($row->balasan_uploaded_at)->timezone('Asia/Jakarta');
                    $statusKey = strtolower(trim((string) ($row->status ?? 'selesai')));

                    return [
                        'id' => $row->id,
                        'role' => 'subbag_umum',
                        'role_label' => 'Subbagian Umum',
                        'nama' => $row->nama ?: '-',
                        'asal_instansi' => $this->resolveAsalInstansi($row),
                        'status' => $statusKey,
                        'status_label' => $this->resolveStatusLabel($statusKey),
                        'subject' => trim(($row->instansi ?: 'Instansi belum diisi') . ' | ' . ($row->tujuan ?: '-')),
                        'message' => 'Subbagian Umum mengunggah surat balasan permohonan kunjungan.',
                        'operator_label' => $this->resolveOperatorActorLabel($row),
                        'event_at' => $eventAt ? $eventAt->format('d M Y H:i') : '-',
                        'event_at_unix' => $eventAt?->timestamp ?? 0,
                        'event_at_sort' => $eventAt ? $eventAt->format('Y-m-d H:i:s') : '',
                    ];
                });

            $recentProcessEvents = collect()
                ->merge($dataEntryEvents->all())
                ->merge($assistantEvents->all())
                ->merge($operatorEvents->all())
                ->merge($subbagEvents->all())
                ->sortByDesc('event_at_sort')
                ->take(12)
                ->values()
                ->map(function (array $event) {
                    unset($event['event_at_sort']);

                    return $event;
                })
                ->all();

            return [
                'role' => $role,
                'role_label' => self::ROLE_LABELS[$role] ?? $role,
                'pending_count' => $pendingCount,
                'pending_items' => $pendingItems->map(function (Tamu $row) {
                    $statusKey = strtolower(trim((string) $row->status));
                    return [
                        'id' => $row->id,
                        'nama' => $row->nama,
                        'instansi' => $row->instansi,
                        'asal_instansi' => $this->resolveAsalInstansi($row),
                        'tujuan' => $row->tujuan,
                        'status' => $statusKey,
                        'status_label' => $this->resolveStatusLabel($statusKey),
                        'workflow_step' => $row->workflow_step,
                        'workflow_label' => self::WORKFLOW_LABELS[$row->workflow_step] ?? $row->workflow_step,
                        'verification_note' => trim((string) ($row->verification_note ?? '')),
                        'operator_label' => $this->resolveOperatorActorLabel($row),
                        'updated_at' => optional($row->updated_at)->timezone('Asia/Jakarta')->format('d M Y H:i') ?: '-',
                        'updated_at_unix' => optional($row->updated_at)->timezone('Asia/Jakarta')->timestamp ?? 0,
                    ];
                })->values()->all(),
                'role_queue_counts' => $roleQueueCounts,
                'recent_process_events' => $recentProcessEvents,
                'generated_at' => now()->timezone('Asia/Jakarta')->format('d M Y H:i:s'),
                'db_down' => false,
            ];
        } catch (Throwable $e) {
            Log::error('Gagal mengambil notifikasi berjenjang.', ['message' => $e->getMessage()]);

            $isDatabaseDown = $this->isDatabaseConnectionIssue($e);

            return [
                'role' => $role,
                'role_label' => self::ROLE_LABELS[$role] ?? $role,
                'pending_count' => 0,
                'pending_items' => [],
                'role_queue_counts' => collect(self::ROLE_LABELS)->map(fn (string $label, string $queueRole) => [
                    'role' => $queueRole,
                    'role_label' => $label,
                    'pending_count' => 0,
                ])->values()->all(),
                'recent_process_events' => [],
                'generated_at' => now()->timezone('Asia/Jakarta')->format('d M Y H:i:s'),
                'db_down' => $isDatabaseDown,
            ];
        }
    }

    private function resolveStatusLabel(string $statusKey): string
    {
        $statusKey = strtolower(trim($statusKey));
        return self::STATUS_LABELS[$statusKey] ?? ucfirst($statusKey !== '' ? $statusKey : 'menunggu');
    }

    private function resolveAsalInstansi(Tamu $row): string
    {
        $instansi = trim((string) ($row->instansi ?? ''));
        return $instansi !== '' ? $instansi : 'Instansi belum diisi';
    }

    private function resolveOperatorActorLabel(Tamu $row): string
    {
        $verifiedLabel = $this->resolveVerifiedOperatorLabel($row);
        if ($verifiedLabel !== '') {
            return $verifiedLabel;
        }

        return $this->resolveAssignedOperatorLabel($row);
    }

    private function resolveAssignedOperatorLabel(Tamu $row): string
    {
        $operator = $row->assignedOperator;
        if (!$operator) {
            return '';
        }

        return $this->formatOperatorLabel(
            name: (string) ($operator->name ?? ''),
            username: (string) ($operator->username ?? '')
        );
    }

    private function resolveVerifiedOperatorLabel(Tamu $row): string
    {
        $operator = $row->bidangVerifier;
        if (!$operator) {
            return '';
        }

        return $this->formatOperatorLabel(
            name: (string) ($operator->name ?? ''),
            username: (string) ($operator->username ?? '')
        );
    }

    private function formatOperatorLabel(string $name, string $username): string
    {
        $name = trim($name);
        $username = trim($username);

        if ($name !== '' && $username !== '') {
            return $name . ' (' . $username . ')';
        }
        if ($name !== '') {
            return $name;
        }
        if ($username !== '') {
            return $username;
        }

        return '';
    }

    private function isDatabaseConnectionIssue(Throwable $e): bool
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof QueryException) {
                $message = strtolower($current->getMessage());

                if (
                    str_contains($message, 'connection refused')
                    || str_contains($message, 'server has gone away')
                    || str_contains($message, 'no such file or directory')
                    || str_contains($message, 'name or service not known')
                    || str_contains($message, 'access denied')
                    || str_contains($message, 'sqlstate[hy000] [2002]')
                    || str_contains($message, 'sqlstate[hy000] [2006]')
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
