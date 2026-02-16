<div>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Listen
    </div>

    {{-- Abschnitt: Allgemein --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('lists.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Neues Board --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createBoard">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Neues Board</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('lists.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createBoard" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Boards mit Listen --}}
    <div class="mt-2" x-show="!collapsed">
        @if($boards->isNotEmpty())
            @foreach($boards as $board)
                <x-ui-sidebar-list :label="$board->name">
                    {{-- Board-Link --}}
                    <x-ui-sidebar-item :href="route('lists.boards.show', ['listsBoard' => $board])">
                        @svg('heroicon-o-rectangle-stack', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                        <div class="flex-1 min-w-0 ml-2">
                            <span class="truncate text-sm font-medium">Übersicht</span>
                        </div>
                    </x-ui-sidebar-item>

                    {{-- Listen innerhalb des Boards --}}
                    @foreach($board->lists as $list)
                        <x-ui-sidebar-item :href="route('lists.lists.show', ['listsList' => $list])">
                            @svg('heroicon-o-list-bullet', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <span class="truncate text-sm font-medium">{{ $list->name }}</span>
                            </div>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @endforeach
        @else
            <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                Keine Boards vorhanden
            </div>
        @endif
    </div>
</div>
