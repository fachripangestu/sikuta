<?php

namespace App\Livewire;

use App\Models\Tamu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;
use Livewire\Component;
use Livewire\WithPagination;

class AdminKunjunganTable extends Component
{
    use WithPagination;

    private const STATUS_OPTIONS = [
        'menunggu' => 'Menunggu Verifikasi',
        'diproses' => 'Diproses',
        'diterima' => 'Disetujui',
        'ditunda' => 'Reschedule',
        'terkonfirmasi' => 'Terkonfirmasi',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    public string $adminRole = '';
    public int $adminUserId = 0;
    public bool $showBalasanColumn = false;
    public bool $isSubbagRole = false;
    public int $justVerifiedId = 0;

    /**
     * @var array<int, array{id:int,name:string,username:string}>
     */
    public array $operatorUsers = [];

    /**
     * @var array<string, string>
     */
    public array $workflowLabels = [];

    public string $search = '';
    public string $statusFilter = '';
    public string $workflowFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 12;
    public bool $dbDown = false;
    public bool $showFilters = false;

    protected string $pageName = 'kunjungan-page';

    public function mount(
        ?string $adminRole = null,
        int $adminUserId = 0,
        bool $showBalasanColumn = false,
        bool $isSubbagRole = false,
        int $justVerifiedId = 0,
        array $operatorUsers = [],
        array $workflowLabels = []
    ): void {
        $this->adminRole = (string) ($adminRole ?? '');
        $this->adminUserId = $adminUserId;
        $this->showBalasanColumn = $showBalasanColumn;
        $this->isSubbagRole = $isSubbagRole;
        $this->justVerifiedId = $justVerifiedId;
        $this->operatorUsers = $this->normalizeOperatorUsers($operatorUsers);
        $this->workflowLabels = $this->normalizeWorkflowLabels($workflowLabels);
        $this->perPage = in_array($this->perPage, [12, 25, 50], true) ? $this->perPage : 12;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'workflowFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            if (!in_array($this->perPage, [12, 25, 50], true)) {
                $this->perPage = 12;
            }

            $this->resetPage(pageName: $this->pageName);
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->workflowFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->perPage = 12;

        $this->resetPage(pageName: $this->pageName);
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function goToPreviousPage(): void
    {
        $this->previousPage(pageName: $this->pageName);
    }

    public function goToNextPage(): void
    {
        $this->nextPage(pageName: $this->pageName);
    }

    public function goToPage(int $page): void
    {
        $targetPage = max(1, $page);
        $this->setPage($targetPage, pageName: $this->pageName);
    }

    public function render()
    {
        try {
            $rows = $this->buildRowsQuery()->paginate($this->perPage, ['*'], $this->pageName);
            $this->dbDown = false;
        } catch (Throwable $e) {
            Log::error('Gagal memuat data kunjungan dashboard Livewire.', ['message' => $e->getMessage()]);
            $rows = $this->emptyPaginator();
            $this->dbDown = $this->isDatabaseConnectionIssue($e);
        }

        return view('livewire.admin-kunjungan-table', [
            'rows' => $rows,
            'statusOptions' => $this->statusOptions(),
            'workflowOptions' => $this->workflowOptions(),
            'hasActiveFilters' => $this->hasActiveFilters(),
            'emptyMessage' => $this->isSubbagRole
                ? 'Belum ada permohonan yang perlu upload surat balasan.'
                : 'Belum ada data permohonan kunjungan yang bisa ditampilkan saat ini.',
        ]);
    }

    private function buildRowsQuery()
    {
        $query = Tamu::query();

        $search = trim($this->search);
        if ($search !== '') {
            $keyword = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search) . '%';
            $query->where(function ($builder) use ($keyword) {
                $builder->where('nama', 'like', $keyword)
                    ->orWhere('instansi', 'like', $keyword)
                    ->orWhere('tujuan', 'like', $keyword)
                    ->orWhere('keperluan', 'like', $keyword)
                    ->orWhere('instansi_provinsi', 'like', $keyword)
                    ->orWhere('telepon', 'like', $keyword)
                    ->orWhere('nomor_surat', 'like', $keyword);
            });
        }

        $normalizedStatus = strtolower(trim($this->statusFilter));
        if (array_key_exists($normalizedStatus, self::STATUS_OPTIONS)) {
            if ($normalizedStatus === 'ditolak') {
                $query->whereRaw('LOWER(status) IN (?, ?)', ['ditolak', 'rejected']);
            } else {
                $query->whereRaw('LOWER(status) = ?', [$normalizedStatus]);
            }
        }

        $normalizedWorkflow = trim($this->workflowFilter);
        if ($normalizedWorkflow !== '' && array_key_exists($normalizedWorkflow, $this->workflowOptions())) {
            $query->where('workflow_step', $normalizedWorkflow);
        }

        [$fromDate, $toDate] = $this->resolveDateRange();
        if ($fromDate !== '') {
            $query->whereDate('tanggal_usulan', '>=', $fromDate);
        }
        if ($toDate !== '') {
            $query->whereDate('tanggal_usulan', '<=', $toDate);
        }

        return $query->orderByDesc('updated_at');
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    /**
     * @return array<string, string>
     */
    private function workflowOptions(): array
    {
        $options = [];
        foreach ($this->workflowLabels as $step => $label) {
            $stepKey = trim((string) $step);
            $labelText = trim((string) $label);
            if ($stepKey === '' || $labelText === '') {
                continue;
            }

            $options[$stepKey] = $labelText;
        }

        return $options;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveDateRange(): array
    {
        $from = trim($this->dateFrom);
        $to = trim($this->dateTo);
        if ($from === '' || $to === '') {
            return [$from, $to];
        }

        return strcmp($from, $to) <= 0 ? [$from, $to] : [$to, $from];
    }

    /**
     * @param  array<int, mixed>  $operatorUsers
     * @return array<int, array{id:int,name:string,username:string}>
     */
    private function normalizeOperatorUsers(array $operatorUsers): array
    {
        $normalized = [];
        foreach ($operatorUsers as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'name' => trim((string) ($item['name'] ?? '-')),
                'username' => trim((string) ($item['username'] ?? '-')),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $workflowLabels
     * @return array<string, string>
     */
    private function normalizeWorkflowLabels(array $workflowLabels): array
    {
        $normalized = [];
        foreach ($workflowLabels as $key => $label) {
            $step = trim((string) $key);
            $text = trim((string) $label);
            if ($step === '' || $text === '') {
                continue;
            }

            $normalized[$step] = $text;
        }

        return $normalized;
    }

    private function emptyPaginator(): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: max(1, $this->perPage),
            currentPage: 1,
            options: ['pageName' => $this->pageName]
        );
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

    private function hasActiveFilters(): bool
    {
        return trim($this->search) !== ''
            || trim($this->statusFilter) !== ''
            || trim($this->workflowFilter) !== ''
            || trim($this->dateFrom) !== ''
            || trim($this->dateTo) !== ''
            || $this->perPage !== 12;
    }
}
