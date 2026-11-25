<?php

namespace App\Livewire;

use App\Services\AdminNotificationService;
use Livewire\Component;

class AdminNotifications extends Component
{
    public string $role = '';

    public int $pendingCount = 0;
    public int $notifCount = 0;
    public int $unreadCount = 0;

    public array $progressItems = [];

    public string $generatedAt = '-';

    public bool $dbDown = false;

    public bool $openPanel = false;

    public function mount(?string $role = null): void
    {
        $this->role = (string) ($role ?: session('admin_role', ''));
        $this->refreshPayload();
    }

    public function refreshPayload(): void
    {
        $payload = app(AdminNotificationService::class)->buildPayload(
            $this->role,
            (int) session('admin_user_id', 0)
        );

        $this->pendingCount = (int) ($payload['pending_count'] ?? 0);
        $pendingItems = is_array($payload['pending_items'] ?? null) ? $payload['pending_items'] : [];

        if ($this->role === 'subbag_umum') {
            $pendingItems = array_values(array_filter($pendingItems, function ($item) {
                if (!is_array($item)) {
                    return false;
                }

                return $this->isSubbagNotifiableStatus((string) ($item['status'] ?? ''));
            }));
        }

        $queueProgress = array_map(function (array $item) {
            return [
                'id' => (int) ($item['id'] ?? 0),
                'status' => strtolower(trim((string) ($item['status'] ?? ''))),
                'status_label' => (string) ($item['status_label'] ?? '-'),
                'asal_instansi' => (string) ($item['asal_instansi'] ?? ($item['instansi'] ?? '-')),
                'verification_note' => trim((string) ($item['verification_note'] ?? '')),
                'operator_label' => trim((string) ($item['operator_label'] ?? '')),
                'source_type' => 'queue',
                'sort_unix' => (int) ($item['updated_at_unix'] ?? 0),
            ];
        }, $pendingItems);

        $sortedItems = collect($queueProgress)
            ->sortByDesc(fn (array $item) => (int) ($item['sort_unix'] ?? 0))
            ->values()
            ->all();

        $maxVisibleItems = $this->role === 'subbag_umum' ? 5 : 1;
        $visibleItems = array_slice($sortedItems, 0, $maxVisibleItems);
        $this->progressItems = array_map(function (array $item) {
            $statusKey = strtolower(trim((string) ($item['status'] ?? '')));

            return [
                'id' => (int) ($item['id'] ?? 0),
                'status_label' => (string) ($item['status_label'] ?? '-'),
                'asal_instansi' => (string) ($item['asal_instansi'] ?? '-'),
                'keterangan' => $this->resolveKeteranganStatus($statusKey, $item),
                'sort_unix' => (int) ($item['sort_unix'] ?? 0),
            ];
        }, $visibleItems);
        $this->generatedAt = (string) ($payload['generated_at'] ?? '-');
        $this->dbDown = (bool) ($payload['db_down'] ?? false);
        $this->notifCount = $this->pendingCount;

        $seenAt = (int) session($this->seenSessionKey(), 0);
        $this->unreadCount = count(array_filter($this->progressItems, function (array $item) use ($seenAt) {
            return (int) ($item['sort_unix'] ?? 0) > $seenAt;
        }));

        if ($this->openPanel && $this->unreadCount > 0) {
            $this->markAsRead();
        }
    }

    public function togglePanel(): void
    {
        $this->openPanel = !$this->openPanel;
        if ($this->openPanel) {
            $this->markAsRead();
        }
    }

    public function closePanel(): void
    {
        $this->openPanel = false;
    }

    public function markAsRead(): void
    {
        $latest = (int) collect($this->progressItems)->max(function (array $item) {
            return (int) ($item['sort_unix'] ?? 0);
        });

        if ($latest <= 0) {
            $latest = now()->timezone('Asia/Jakarta')->timestamp;
        }

        session()->put($this->seenSessionKey(), $latest);
        $this->unreadCount = 0;
    }

    private function seenSessionKey(): string
    {
        $roleKey = trim($this->role) !== '' ? $this->role : 'admin';
        $adminUserId = (string) session('admin_user_id', 'guest');

        return 'admin_notif_seen_at_' . $adminUserId . '_' . $roleKey;
    }

    private function resolveKeteranganStatus(string $statusKey, array $item = []): string
    {
        $sourceType = strtolower(trim((string) ($item['source_type'] ?? '')));
        if ($sourceType === 'queue') {
            if ($this->role === 'subbag_umum' && $statusKey === 'ditunda') {
                $verificationNote = trim((string) ($item['verification_note'] ?? ''));
                if ($verificationNote !== '') {
                    return 'Keterangan tundaan: ' . $verificationNote;
                }

                return 'Permohonan ditunda oleh Sekretaris Pimpinan dan menunggu surat balasan penundaan.';
            }

            $prevRoleLabel = $this->resolvePreviousRoleLabel();
            if ($prevRoleLabel !== '') {
                if ($prevRoleLabel === 'Sekretaris Pimpinan') {
                    return 'Sudah disetujui Sekretaris Pimpinan.';
                }
                if ($prevRoleLabel === 'Operator Bidang/Wilayah') {
                    $operatorLabel = trim((string) ($item['operator_label'] ?? ''));
                    if ($operatorLabel !== '') {
                        return 'Sudah diterima oleh Operator Bidang/Wilayah: ' . $operatorLabel . '.';
                    }

                    return 'Sudah diterima oleh Operator Bidang/Wilayah.';
                }

                return 'Sudah diverifikasi oleh ' . $prevRoleLabel . '.';
            }

            return 'Permohonan baru dari tamu dan siap diverifikasi.';
        }

        $eventMessage = trim((string) ($item['event_message'] ?? ''));
        if ($eventMessage !== '') {
            return $eventMessage;
        }

        if (in_array($statusKey, ['diterima', 'approved', 'terkonfirmasi', 'selesai'], true)) {
            return 'Sudah disetujui';
        }

        return 'Sudah diverifikasi';
    }

    private function resolvePreviousRoleLabel(): string
    {
        return match ($this->role) {
            'sekretaris_pimpinan' => 'Data Entry',
            'bidang_tujuan' => 'Sekretaris Pimpinan',
            'subbag_umum' => 'Operator Bidang/Wilayah',
            default => '',
        };
    }

    private function isSubbagNotifiableStatus(string $status): bool
    {
        $statusKey = strtolower(trim($status));
        if ($statusKey === '') {
            return false;
        }

        return !in_array($statusKey, ['selesai', 'ditolak', 'rejected'], true);
    }

    public function render()
    {
        return view('livewire.admin-notifications');
    }
}
