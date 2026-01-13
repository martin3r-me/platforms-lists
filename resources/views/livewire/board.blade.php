<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$board->name" icon="heroicon-o-list-bullet">
            <x-slot name="actions">
                <a href="{{ route('lists.dashboard') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurück zum Dashboard</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $board->name }}</h1>
                @if($board->description)
                    <p class="text-[var(--ui-secondary)]">{{ $board->description }}</p>
                @endif
            </div>
        </div>

        {{-- Listen Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)] mb-1">Listen</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Alle Listen in diesem Board</p>
                    </div>
                    @can('update', $board)
                        <x-ui-button 
                            variant="primary" 
                            size="sm" 
                            wire:click="createList"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus','w-4 h-4')
                                <span>Liste hinzufügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
                
                {{-- Listen Grid --}}
                @if($lists->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($lists as $list)
                            <a href="{{ route('lists.lists.show', $list) }}" class="group relative overflow-hidden rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-all duration-300 bg-white">
                                <div class="p-6">
                                    <div class="flex items-start justify-between gap-3 mb-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-bold text-[var(--ui-secondary)] mb-1 truncate">{{ $list->name }}</h3>
                                            @if($list->description)
                                                <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $list->description }}</p>
                                            @endif
                                        </div>
                                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[var(--ui-primary)]/10 to-[var(--ui-primary)]/5 flex items-center justify-center flex-shrink-0">
                                            @svg('heroicon-o-list-bullet', 'w-5 h-5 text-[var(--ui-primary)]')
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                        <span>Erstellt {{ $list->created_at->format('d.m.Y') }}</span>
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-[var(--ui-primary)]/0 to-[var(--ui-primary)]/0 group-hover:from-[var(--ui-primary)]/5 group-hover:to-transparent transition-all duration-300"></div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted)] mb-4">
                            @svg('heroicon-o-list-bullet', 'w-8 h-8 text-[var(--ui-muted)]')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Listen hinzugefügt</p>
                        <p class="text-xs text-[var(--ui-muted)]">Erstelle deine erste Liste</p>
                    </div>
                @endif
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Listen</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $lists->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $board->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
