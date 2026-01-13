<?php

namespace Platform\Lists\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Lists\Models\ListsBoard;

class Dashboard extends Component
{
    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => 'Platform\Lists\Models\ListsBoard',
            'modelId' => null,
            'subject' => 'Listen Dashboard',
            'description' => 'Übersicht aller Listen-Boards',
            'url' => route('lists.dashboard'),
            'source' => 'lists.dashboard',
            'recipients' => [],
            'meta' => [
                'view_type' => 'dashboard',
            ],
        ]);
    }

    public function createBoard()
    {
        $user = Auth::user();
        
        // Policy-Berechtigung prüfen
        $this->authorize('create', ListsBoard::class);

        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        // Neues Board anlegen
        $board = ListsBoard::create([
            'name' => 'Neues Board',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $this->dispatch('updateSidebar');
        
        // Zur Board-Ansicht weiterleiten
        return $this->redirect(route('lists.boards.show', $board), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        // === BOARDS (nur Team-Boards) ===
        $boards = ListsBoard::where('team_id', $team->id)->orderBy('name')->get();
        $activeBoards = $boards->filter(function($board) {
            return $board->done === null || $board->done === false;
        })->count();
        $totalBoards = $boards->count();

        // === BOARDS-ÜBERSICHT (nur aktive Boards) ===
        $activeBoardsList = $boards->filter(function($board) {
            return $board->done === null || $board->done === false;
        })
        ->map(function ($board) {
            return [
                'id' => $board->id,
                'name' => $board->name,
                'subtitle' => $board->description ? mb_substr($board->description, 0, 50) . '...' : '',
            ];
        })
        ->take(5);

        return view('lists::livewire.dashboard', [
            'activeBoards' => $activeBoards,
            'totalBoards' => $totalBoards,
            'activeBoardsList' => $activeBoardsList,
        ])->layout('platform::layouts.app');
    }
}
