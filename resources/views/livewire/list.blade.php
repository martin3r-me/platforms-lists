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

        {{-- Listen-Elemente Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)] mb-1">Elemente</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Sortierbare Liste</p>
                    </div>
                    @can('update', $list)
                        <x-ui-button 
                            variant="primary" 
                            size="sm" 
                            wire:click="createItem"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus','w-4 h-4')
                                <span>Element hinzufügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
                
                {{-- Sortierbare Liste --}}
                @if($list->items->count() > 0)
                    <div 
                        wire:sortable="updateItemOrder" 
                        wire:sortable.options="{ animation: 150 }"
                        class="space-y-2"
                    >
                        @foreach($list->items->sortBy('order') as $item)
                            <div 
                                wire:sortable.item="{{ $item->id }}" 
                                wire:key="item-{{ $item->id }}"
                                class="group flex items-center gap-3 p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60 rounded-lg hover:border-[var(--ui-border)] hover:bg-[var(--ui-muted)] transition-all"
                            >
                                {{-- Drag Handle --}}
                                @can('update', $list)
                                    <div wire:sortable.handle class="cursor-move p-1 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] flex-shrink-0" title="Zum Verschieben ziehen">
                                        @svg('heroicon-o-bars-3', 'w-5 h-5')
                                    </div>
                                @endcan
                                
                                {{-- Checkbox --}}
                                @can('update', $list)
                                    <input 
                                        type="checkbox" 
                                        wire:click="toggleItemDone({{ $item->id }})"
                                        @if($item->done) checked @endif
                                        class="w-5 h-5 rounded border-[var(--ui-border)] text-[var(--ui-primary)] focus:ring-[var(--ui-primary)] cursor-pointer flex-shrink-0"
                                    >
                                @else
                                    <div class="w-5 h-5 rounded border border-[var(--ui-border)] flex items-center justify-center flex-shrink-0">
                                        @if($item->done)
                                            @svg('heroicon-o-check', 'w-4 h-4 text-[var(--ui-success)]')
                                        @endif
                                    </div>
                                @endcan
                                
                                {{-- Titel --}}
                                <div class="flex-1 min-w-0">
                                    @can('update', $list)
                                        <input 
                                            type="text"
                                            value="{{ $item->title }}"
                                            wire:blur="updateItemTitle({{ $item->id }}, $event.target.value)"
                                            class="w-full bg-transparent border-none p-0 text-[var(--ui-secondary)] font-medium focus:outline-none focus:ring-0 @if($item->done) line-through text-[var(--ui-muted)] @endif"
                                            placeholder="Element-Titel..."
                                        >
                                    @else
                                        <div class="text-[var(--ui-secondary)] font-medium @if($item->done) line-through text-[var(--ui-muted)] @endif">
                                            {{ $item->title }}
                                        </div>
                                    @endcan
                                    @if($item->description)
                                        <div class="text-sm text-[var(--ui-muted)] mt-1">{{ $item->description }}</div>
                                    @endif
                                </div>
                                
                                {{-- Delete Button --}}
                                @can('update', $list)
                                    <button 
                                        wire:click="deleteItem({{ $item->id }})"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-[var(--ui-danger)] hover:text-[var(--ui-danger-80)] flex-shrink-0"
                                        title="Löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </button>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted)] mb-4">
                            @svg('heroicon-o-list-bullet', 'w-8 h-8 text-[var(--ui-muted)]')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Elemente hinzugefügt</p>
                        <p class="text-xs text-[var(--ui-muted)]">Erstelle dein erstes Element</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Liste Details (bearbeitbar) --}}
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
                            <span class="text-sm text-[var(--ui-muted)]">Elemente</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $list->items->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erledigt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $list->items->where('done', true)->count() }} / {{ $list->items->count() }}
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
