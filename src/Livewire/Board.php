<?php

namespace Platform\Lists\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Lists\Models\ListsBoard;
use Livewire\Attributes\On;

class Board extends Component
{
    public ListsBoard $board;

    public function mount(ListsBoard $listsBoard)
    {
        $this->board = $listsBoard->fresh()->load('lists');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->board);
    }

    #[On('updateBoard')] 
    public function updateBoard()
    {
        $this->board->refresh();
        $this->board->load('lists');
    }

    public function updateListOrder($items)
    {
        $this->authorize('update', $this->board);
        
        foreach ($items as $item) {
            $list = $this->board->lists()->find($item['value']);
            if ($list) {
                $list->update(['order' => $item['order']]);
            }
        }
        
        $this->board->refresh();
        $this->board->load('lists');
    }

    public function createList()
    {
        $this->authorize('update', $this->board);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $list = \Platform\Lists\Models\ListsList::create([
            'name' => 'Neue Liste',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'board_id' => $this->board->id,
        ]);

        $this->board->refresh();
        
        return $this->redirect(route('lists.lists.show', $list), navigate: true);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->board),
            'modelId' => $this->board->id,
            'subject' => $this->board->name,
            'description' => $this->board->description ?? '',
            'url' => route('lists.boards.show', $this->board),
            'source' => 'lists.board.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->board->created_at,
            ],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        
        // Listen für dieses Board laden
        $lists = $this->board->lists;

        return view('lists::livewire.board', [
            'user' => $user,
            'lists' => $lists,
        ])->layout('platform::layouts.app');
    }
}
