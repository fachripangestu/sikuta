<div class="table-livewire-shell">
    <div class="table-filter-toggle-wrap">
        <button
            type="button"
            class="table-filter-toggle {{ $showFilters ? 'is-open' : '' }}"
            wire:click="toggleFilters"
            aria-expanded="{{ $showFilters ? 'true' : 'false' }}"
            aria-controls="dashboardFilterPanel"
        >
            {{ $showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter' }}
        </button>
        @if ($hasActiveFilters && !$showFilters)
            <button type="button" class="table-filter-reset" wire:click="resetFilters">
                Reset Filter
            </button>
        @endif
    </div>

    <div
        id="dashboardFilterPanel"
        class="table-filter-collapse {{ $showFilters ? 'is-open' : '' }}"
        aria-hidden="{{ $showFilters ? 'false' : 'true' }}"
    >
        <section class="table-filter-wrap" aria-label="Filter data kunjungan">
            <div class="table-filter-grid">
                <label class="table-filter-field table-filter-search">
                    <span>Pencarian</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama, instansi, tujuan, perihal, WA, nomor surat"
                        autocomplete="off"
                    >
                </label>

                <label class="table-filter-field">
                    <span>Status</span>
                    <select wire:model.live="statusFilter">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="table-filter-field">
                    <span>Alur Proses</span>
                    <select wire:model.live="workflowFilter">
                        <option value="">Semua Alur</option>
                        @foreach ($workflowOptions as $workflowValue => $workflowLabel)
                            <option value="{{ $workflowValue }}">{{ $workflowLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="table-filter-field">
                    <span>Dari Tanggal</span>
                    <input type="date" wire:model.live="dateFrom">
                </label>

                <label class="table-filter-field">
                    <span>Sampai Tanggal</span>
                    <input type="date" wire:model.live="dateTo">
                </label>

                <label class="table-filter-field">
                    <span>Tampil</span>
                    <select wire:model.live="perPage">
                        <option value="12">12 Data</option>
                        <option value="25">25 Data</option>
                        <option value="50">50 Data</option>
                    </select>
                </label>
            </div>

            <div class="table-filter-foot">
                <button type="button" class="table-filter-reset" wire:click="resetFilters">
                    Reset Filter
                </button>
                <span class="table-filter-loading" wire:loading wire:target="search,statusFilter,workflowFilter,dateFrom,dateTo,perPage">
                    Memuat data...
                </span>
            </div>
        </section>
    </div>

    @if ($dbDown)
        <div class="empty">Koneksi database belum aktif. Jalankan MySQL agar filter data kunjungan bisa digunakan.</div>
    @elseif ($rows->isEmpty())
        <div class="empty">{{ $emptyMessage }}</div>
    @else
        <div class="table-wrap" wire:loading.class="is-loading">
            <table class="req-table {{ $showBalasanColumn ? 'req-table-subbag' : '' }}">
                <thead>
                    <tr>
                        <th class="table-cell-center">No</th>
                        <th class="table-cell-left">Nama Pemohon</th>
                        <th class="table-cell-left">Instansi</th>
                        <th class="table-cell-left">Perihal Kunjungan</th>
                        <th class="table-cell-left">Provinsi</th>
                        <th class="table-cell-center">No WA</th>
                        <th class="table-cell-center">Lampiran</th>
                        <th class="table-cell-center table-cell-date-head">Tanggal Kunjungan</th>
                        @if ($showBalasanColumn)
                            <th class="table-cell-center table-cell-balasan-head">Surat Balasan<br>Permohonan Kunjungan</th>
                        @else
                            <th class="table-cell-center table-cell-action-head">Tindak Lanjut<br>Verifikasi</th>
                        @endif
                        <th class="table-cell-center table-cell-status-head">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        @php
                            $statusKey = strtolower(trim((string) ($row->status ?? 'menunggu')));
                            $statusClass = match ($statusKey) {
                                'terkonfirmasi', 'selesai' => 'done',
                                'diterima' => 'approved',
                                'diproses' => 'process',
                                'ditunda' => 'reschedule',
                                'ditolak', 'rejected' => 'rejected',
                                default => 'pending',
                            };
                            $statusLabel = match ($statusKey) {
                                'menunggu' => 'Menunggu Verifikasi',
                                'diproses' => 'Diproses',
                                'diterima' => 'Disetujui',
                                'ditunda' => 'Reschedule',
                                'terkonfirmasi' => 'Terkonfirmasi',
                                'selesai' => 'Selesai',
                                'ditolak', 'rejected' => 'Ditolak',
                                default => ucfirst($statusKey),
                            };
                            $isQueueRow = $row->workflow_step === $adminRole && $row->workflow_step !== 'selesai';
                            $isAssignedToCurrentOperator = true;
                            if ($adminRole === 'bidang_tujuan') {
                                $isAssignedToCurrentOperator = !is_null($row->assigned_operator_id)
                                    && (int) $row->assigned_operator_id === (int) $adminUserId;
                                $isQueueRow = $isQueueRow && $isAssignedToCurrentOperator;
                            }
                            $hasBalasan = (bool) ($row->balasan_uploaded_at || $row->balasan_path || $row->balasan_data);
                            $rescheduleNote = trim((string) ($row->verification_note ?? ''));
                            $rescheduleNotePreview = mb_strlen($rescheduleNote) > 110
                                ? mb_substr($rescheduleNote, 0, 110) . '...'
                                : $rescheduleNote;
                            $canUploadBalasan = $showBalasanColumn && in_array($statusKey, ['terkonfirmasi', 'ditunda'], true) && !$hasBalasan;
                            $canReuploadBalasan = $showBalasanColumn && $hasBalasan;
                            $justVerifiedActionLabel = match (true) {
                                $adminRole === 'data_entry' => 'Telah Diverifikasi',
                                $statusKey === 'ditunda' => 'Telah Ditunda',
                                default => 'Telah Disetujui',
                            };
                            $justVerifiedActionClass = $statusKey === 'ditunda' ? ' is-reschedule' : '';
                            $rowNumber = ((int) ($rows->firstItem() ?? 1) - 1) + $index + 1;
                        @endphp
                        <tr wire:key="kunjungan-row-{{ (int) $row->id }}">
                            <td class="table-cell-center">{{ $rowNumber }}</td>
                            <td class="table-cell-left">{{ $row->nama ?: '-' }}</td>
                            <td class="table-cell-left">
                                <div class="instansi">{{ $row->instansi ?: '-' }}</div>
                                <div class="tujuan">{{ $row->tujuan ?: '-' }}</div>
                            </td>
                            <td class="perihal table-cell-left">{{ trim((string) ($row->keperluan ?? '')) !== '' ? $row->keperluan : '-' }}</td>
                            <td class="table-cell-left">{{ $row->instansi_provinsi ?: '-' }}</td>
                            <td class="table-cell-center">{{ $row->telepon ?: '-' }}</td>
                            <td class="table-cell-center">
                                @if ($row->lampiran_path || $row->lampiran_data)
                                    <a
                                        class="lampiran-link lampiran-link-icon"
                                        href="{{ route('admin.kunjungan.lampiran', ['tamu' => $row, 'v' => file_exists(public_path('favicon.ico')) ? filemtime(public_path('favicon.ico')) : time()]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Lihat dokumen lampiran"
                                        title="Lihat dokumen"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-6Z" fill="currentColor" opacity=".35"/>
                                            <path d="M14 2v6h5" fill="currentColor"/>
                                            <path d="M9 13h6M9 16h6M9 10h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                        </svg>
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="table-cell-center">{{ $row->tanggal_usulan?->timezone('Asia/Jakarta')?->locale('id')->translatedFormat('d M Y') ?? '-' }}</td>
                            @if ($showBalasanColumn)
                                <td class="table-action-cell">
                                    @if ($canUploadBalasan)
                                        <div class="balasan-upload-stack">
                                            @if ($statusKey === 'ditunda' && $rescheduleNote !== '')
                                                <span class="table-upload-note" title="{{ $rescheduleNote }}">Ditunda: {{ $rescheduleNotePreview }}</span>
                                            @endif
                                            <div class="balasan-upload-actions balasan-upload-actions-inline is-pending-upload">
                                                <form class="table-upload-form table-upload-icon-form table-upload-inline" method="post" action="{{ route('admin.kunjungan.upload_balasan', $row) }}" enctype="multipart/form-data">
                                                    @csrf
                                                    <input
                                                        id="balasan-upload-{{ (int) $row->id }}"
                                                        class="table-upload-input"
                                                        type="file"
                                                        name="balasan_dokumen"
                                                        accept=".pdf,.jpg,.jpeg,.png"
                                                        required
                                                        aria-label="Unggah surat balasan"
                                                    >
                                                    <label
                                                        class="lampiran-link lampiran-link-icon table-upload-trigger"
                                                        for="balasan-upload-{{ (int) $row->id }}"
                                                        aria-label="Unggah dokumen balasan"
                                                        title="Unggah dokumen balasan"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                            <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-6Z" fill="currentColor" opacity=".35"/>
                                                            <path d="M14 2v6h5" fill="currentColor"/>
                                                            <path d="M9 13h6M9 16h6M9 10h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                        </svg>
                                                    </label>
                                                </form>
                                                <details class="table-upload-choice-menu">
                                                    <summary
                                                        class="lampiran-link lampiran-link-icon table-upload-choice-trigger"
                                                        aria-label="Pilih aksi dokumen balasan"
                                                        title="Pilih aksi dokumen balasan"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                            <circle cx="12" cy="5.2" r="1.7" fill="currentColor"/>
                                                            <circle cx="12" cy="12" r="1.7" fill="currentColor"/>
                                                            <circle cx="12" cy="18.8" r="1.7" fill="currentColor"/>
                                                        </svg>
                                                    </summary>
                                                    <div class="table-upload-choice-popover">
                                                        <button
                                                            class="table-upload-choice-item"
                                                            type="button"
                                                            data-choice-action="reupload"
                                                            data-upload-input-id="balasan-upload-{{ (int) $row->id }}"
                                                        >
                                                            Unggah Ulang
                                                        </button>
                                                        <button
                                                            class="table-upload-choice-item is-disabled"
                                                            type="button"
                                                            disabled
                                                            title="Dokumen balasan belum tersedia untuk dihapus."
                                                        >
                                                            Hapus Unggahan
                                                        </button>
                                                    </div>
                                                </details>
                                            </div>
                                        </div>
                                    @elseif ($hasBalasan)
                                        <div class="balasan-upload-stack balasan-upload-stack-inline">
                                            <div class="balasan-upload-actions balasan-upload-actions-inline is-uploaded">
                                                <a
                                                    class="lampiran-link lampiran-link-icon"
                                                    href="{{ route('admin.kunjungan.balasan', ['tamu' => $row, 'v' => file_exists(public_path('favicon.ico')) ? filemtime(public_path('favicon.ico')) : time()]) }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    aria-label="Lihat dokumen balasan"
                                                    title="Lihat dokumen balasan"
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                        <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-6Z" fill="currentColor" opacity=".35"/>
                                                        <path d="M14 2v6h5" fill="currentColor"/>
                                                        <path d="M9 13h6M9 16h6M9 10h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                    </svg>
                                                </a>
                                                @if ($canReuploadBalasan)
                                                    <details class="table-upload-choice-menu">
                                                        <summary
                                                            class="lampiran-link lampiran-link-icon table-upload-choice-trigger"
                                                            aria-label="Pilih aksi dokumen balasan"
                                                            title="Pilih aksi dokumen balasan"
                                                        >
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                <circle cx="12" cy="5.2" r="1.7" fill="currentColor"/>
                                                                <circle cx="12" cy="12" r="1.7" fill="currentColor"/>
                                                                <circle cx="12" cy="18.8" r="1.7" fill="currentColor"/>
                                                            </svg>
                                                        </summary>
                                                        <div class="table-upload-choice-popover">
                                                            <button
                                                                class="table-upload-choice-item"
                                                                type="button"
                                                                data-choice-action="reupload"
                                                                data-upload-input-id="balasan-reupload-{{ (int) $row->id }}"
                                                            >
                                                                Unggah Ulang
                                                            </button>
                                                            <button
                                                                class="table-upload-choice-item is-danger"
                                                                type="button"
                                                                data-choice-action="delete"
                                                                data-delete-form-id="balasan-delete-form-{{ (int) $row->id }}"
                                                            >
                                                                Hapus Unggahan
                                                            </button>
                                                        </div>
                                                    </details>
                                                    <form class="table-upload-form table-upload-inline table-upload-hidden-action" method="post" action="{{ route('admin.kunjungan.upload_balasan', $row) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        <input
                                                            id="balasan-reupload-{{ (int) $row->id }}"
                                                            class="table-upload-input"
                                                            type="file"
                                                            name="balasan_dokumen"
                                                            accept=".pdf,.jpg,.jpeg,.png"
                                                            required
                                                            aria-label="Unggah ulang dokumen balasan"
                                                        >
                                                    </form>
                                                    <form
                                                        id="balasan-delete-form-{{ (int) $row->id }}"
                                                        class="table-upload-form table-upload-inline table-upload-hidden-action"
                                                        method="post"
                                                        action="{{ route('admin.kunjungan.delete_balasan', $row) }}"
                                                    >
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="table-action-muted">-</span>
                                    @endif
                                </td>
                            @elseif ($isQueueRow)
                                <td class="table-action-cell">
                                    <form class="table-verify-form {{ $adminRole === 'sekretaris_pimpinan' ? 'with-operator' : '' }}" method="post" action="{{ route('admin.kunjungan.verify', $row) }}">
                                        @csrf
                                        @if ($adminRole === 'data_entry')
                                            <button class="action-btn approve" type="submit" name="action" value="initial_review">Verifikasi</button>
                                        @elseif ($adminRole === 'sekretaris_pimpinan')
                                            <div class="table-verify-action-grid table-verify-action-toggle-grid">
                                                @if (!empty($operatorUsers))
                                                    <button class="action-btn approve" type="button" data-action-toggle="approve" aria-pressed="false">Disetujui</button>
                                                @else
                                                    <button class="action-btn approve" type="button" disabled>Disetujui</button>
                                                @endif
                                                <button class="action-btn reschedule" type="button" data-action-toggle="reschedule" aria-pressed="false">Ditunda</button>
                                            </div>
                                            <div class="table-verify-action-panels">
                                                <div class="table-verify-action-panel" data-action-panel="approve" hidden>
                                                    <select
                                                        name="operator_user_id"
                                                        class="action-operator-select"
                                                        aria-label="Pilih operator bidang/wilayah untuk disetujui"
                                                        data-panel-input="approve"
                                                        disabled
                                                    >
                                                        <option value="">Pilih Operator (untuk Disetujui)</option>
                                                        @foreach ($operatorUsers as $operatorUser)
                                                            <option value="{{ (int) ($operatorUser['id'] ?? 0) }}">
                                                                {{ (string) ($operatorUser['name'] ?? '-') }} ({{ (string) ($operatorUser['username'] ?? '-') }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button class="action-btn approve" type="submit" name="action" value="approve" @if (empty($operatorUsers)) disabled @endif>Disetujui</button>
                                                </div>
                                                <div class="table-verify-action-panel" data-action-panel="reschedule" hidden>
                                                    <textarea
                                                        name="note"
                                                        class="action-reschedule-input"
                                                        aria-label="Keterangan penundaan"
                                                        data-panel-input="reschedule"
                                                        rows="2"
                                                        maxlength="1000"
                                                        placeholder="Isi keterangan penundaan"
                                                        disabled
                                                    ></textarea>
                                                    <button class="action-btn reschedule" type="submit" name="action" value="reschedule">Ditunda</button>
                                                </div>
                                            </div>
                                            @if (empty($operatorUsers))
                                                <span class="table-action-muted">Operator aktif belum tersedia. Anda tetap bisa memilih Ditunda.</span>
                                            @endif
                                        @elseif ($adminRole === 'bidang_tujuan')
                                            <button class="action-btn accepted" type="submit" name="action" value="confirm_visit">Diterima</button>
                                        @endif
                                    </form>
                                </td>
                            @else
                                <td class="table-action-cell">
                                    @if ($justVerifiedId === (int) $row->id)
                                        <span class="table-action-success{{ $justVerifiedActionClass }}">{{ $justVerifiedActionLabel }}</span>
                                    @else
                                        <span class="table-action-muted">-</span>
                                    @endif
                                </td>
                            @endif
                            <td class="table-cell-center"><span class="status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (!$rows->isEmpty())
        <div class="table-pagination-wrap">
            <p class="table-pagination-meta">
                Halaman {{ $rows->currentPage() }} dari {{ $rows->lastPage() }}
            </p>
            @if ($rows->hasPages())
                @php
                    $currentPage = (int) $rows->currentPage();
                    $lastPage = (int) $rows->lastPage();
                    $pageItems = [];

                    if ($lastPage <= 7) {
                        $pageItems = range(1, $lastPage);
                    } elseif ($currentPage <= 4) {
                        $pageItems = [1, 2, 3, 4, 5, 'ellipsis', $lastPage];
                    } elseif ($currentPage >= ($lastPage - 3)) {
                        $pageItems = [1, 'ellipsis', $lastPage - 4, $lastPage - 3, $lastPage - 2, $lastPage - 1, $lastPage];
                    } else {
                        $pageItems = [1, 'ellipsis', $currentPage - 1, $currentPage, $currentPage + 1, 'ellipsis', $lastPage];
                    }
                @endphp
                <div class="table-pagination-controls">
                    <button
                        type="button"
                        class="table-page-btn table-page-btn-nav"
                        wire:click="goToPage(1)"
                        @if ($rows->onFirstPage()) disabled @endif
                        aria-label="Halaman pertama"
                    >
                        &laquo;
                    </button>
                    <button
                        type="button"
                        class="table-page-btn table-page-btn-nav"
                        wire:click="goToPreviousPage"
                        @if ($rows->onFirstPage()) disabled @endif
                        aria-label="Halaman sebelumnya"
                    >
                        &lsaquo;
                    </button>

                    @foreach ($pageItems as $pageItem)
                        @if ($pageItem === 'ellipsis')
                            <span class="table-page-ellipsis" aria-hidden="true">...</span>
                        @else
                            <button
                                type="button"
                                class="table-page-btn table-page-btn-number{{ (int) $pageItem === $currentPage ? ' is-active' : '' }}"
                                wire:click="goToPage({{ (int) $pageItem }})"
                                @if ((int) $pageItem === $currentPage) aria-current="page" @endif
                                aria-label="Halaman {{ (int) $pageItem }}"
                            >
                                {{ (int) $pageItem }}
                            </button>
                        @endif
                    @endforeach

                    <button
                        type="button"
                        class="table-page-btn table-page-btn-nav"
                        wire:click="goToNextPage"
                        @if (!$rows->hasMorePages()) disabled @endif
                        aria-label="Halaman berikutnya"
                    >
                        &rsaquo;
                    </button>
                    <button
                        type="button"
                        class="table-page-btn table-page-btn-nav"
                        wire:click="goToPage({{ (int) $rows->lastPage() }})"
                        @if (!$rows->hasMorePages()) disabled @endif
                        aria-label="Halaman terakhir"
                    >
                        &raquo;
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
