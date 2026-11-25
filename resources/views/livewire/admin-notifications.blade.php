<div class="headnav-notif-livewire" wire:poll.3s="refreshPayload">
    <div class="headnav-notif-trigger">
        <button
            type="button"
            class="headnav-btn headnav-btn-notif"
            wire:click="togglePanel"
            aria-label="Notifikasi realtime"
            aria-expanded="{{ $openPanel ? 'true' : 'false' }}"
            aria-controls="headnav-notification-panel"
        >
            <span class="headnav-notif-icon" aria-hidden="true">&#128276;</span>
            <span class="headnav-count">{{ $notifCount }}</span>
            <span class="headnav-dot {{ ($notifCount > 0 || $unreadCount > 0) ? 'is-visible' : '' }}" aria-hidden="true"></span>
        </button>
    </div>

    @if ($openPanel)
        <section
            id="headnav-notification-panel"
            class="headnav-notif-panel"
            wire:click.outside="closePanel"
        >
            <header class="headnav-notif-panel-head">
                <strong>Progress Permohonan</strong>
            </header>

            @if ($dbDown)
                <div class="headnav-notif-empty">Koneksi database belum tersedia.</div>
            @else
                @if (count($progressItems) === 0)
                    <div class="headnav-notif-empty">Tidak ada permohonan kunjungan.</div>
                @else
                    <div class="headnav-notif-list">
                        @foreach ($progressItems as $item)
                            <article class="headnav-notif-item">
                                <h4>Status Permohonan: {{ $item['status_label'] ?? '-' }}</h4>
                                <p>{{ $item['asal_instansi'] ?? '-' }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    @endif
</div>
