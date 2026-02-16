<?php

namespace Platform\Lists\Livewire;

use Livewire\Component;
use Platform\Lists\Models\ListsBoard;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    #[On('updateSidebar')]
    public function updateSidebar()
    {
    }

    public function createBoard()
    {
        $user = auth()->user();
        $teamId = $user->currentTeam->id;

        $board = new ListsBoard();
        $board->name = 'Neues Board';
        $board->user_id = $user->id;
        $board->team_id = $teamId;
        $board->save();

        return redirect()->route('lists.boards.show', ['listsBoard' => $board->id]);
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('lists::livewire.sidebar', [
                'boards' => collect(),
            ]);
        }

        $boards = ListsBoard::query()
            ->where('team_id', $teamId)
            ->with('lists')
            ->orderBy('name')
            ->get();

        return view('lists::livewire.sidebar', [
            'boards' => $boards,
        ]);
    }
}
