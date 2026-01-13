<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$list->name" icon="heroicon-o-list-bullet">
            <x-slot name="actions">
                <a href="{{ route('lists.boards.show', $list->board) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurück zum Board</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $list->name }}</h1>
                @if($list->description)
                    <p class="text-[var(--ui-secondary)]">{{ $list->description }}</p>
                @endif
            </div>
        </div>

        {{-- Liste Content --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[var(--ui-primary)]/10 to-[var(--ui-primary)]/5 flex items-center justify-center">
                        @svg('heroicon-o-document-text', 'w-5 h-5 text-[var(--ui-primary)]')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Details</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Liste bearbeiten</p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <x-ui-input-text
                            name="list.name"
                            label="Name"
                            wire:model="list.name"
                            placeholder="Listen-Name eingeben..."
                            required
                            :errorKey="'list.name'"
                        />
                    </div>

                    <div>
                        <x-ui-input-textarea
                            name="list.description"
                            label="Beschreibung"
                            wire:model="list.description"
                            placeholder="Optionale Beschreibung..."
                            rows="4"
                            :errorKey="'list.description'"
                        />
                    </div>
                </div>
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Liste-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $list)
                            @if($this->isDirty())
                                <x-ui-button variant="primary" size="sm" wire:click="save" class="w-full">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-check','w-4 h-4')
                                        <span>Speichern</span>
                                    </span>
                                </x-ui-button>
                            @endif
                        @endcan
                    </div>
                </div>

                {{-- Liste-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Board</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $list->board->name }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $list->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
