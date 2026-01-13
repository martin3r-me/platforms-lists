<?php

namespace Platform\Lists\Policies;

use Platform\Core\Models\User;
use Platform\Lists\Models\ListsBoard;

class BoardPolicy
{
    public function view(User $user, ListsBoard $board): bool
    {
        return $board->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, ListsBoard $board): bool
    {
        return $board->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, ListsBoard $board): bool
    {
        return $board->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function settings(User $user, ListsBoard $board): bool
    {
        return $this->view($user, $board);
    }
}
