<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-list-bullet" />
    </x-slot>

    <x-ui-page-container>
        {{-- Main Stats Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-ui-dashboard-tile
                title="Aktive Boards"
                :count="$activeBoards"
                subtitle="von {{ $totalBoards }}"
                icon="list-bullet"
                variant="secondary"
                size="lg"
            />
        </div>

        <x-ui-panel title="Meine aktiven Boards" subtitle="Top 5 Boards">
            <div class="grid grid-cols-1 gap-3">
                @forelse($activeBoardsList as $board)
                    @php
                        $href = route('lists.boards.show', ['listsBoard' => $board['id'] ?? null]);
                    @endphp
                    <a href="{{ $href }}" class="flex items-center gap-3 p-3 rounded-md border border-[var(--ui-border)] bg-white hover:bg-[var(--ui-muted-5)] transition">
                        <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded flex items-center justify-center">
                            @svg('heroicon-o-list-bullet', 'w-5 h-5')
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ $board['name'] ?? 'Board' }}</div>
                            <div class="text-xs text-[var(--ui-muted)] truncate">
                                {{ $board['subtitle'] ?? '' }}
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-3 text-sm text-[var(--ui-muted)] bg-white rounded-md border border-[var(--ui-border)]">Keine Boards gefunden.</div>
                @endforelse
            </div>
        </x-ui-panel>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary-outline" size="sm" wire:click="createBoard" class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Neues Board</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Schnellstatistiken</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Aktive Boards</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $activeBoards ?? 0 }} Boards</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
