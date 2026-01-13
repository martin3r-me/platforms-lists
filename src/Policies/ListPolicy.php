<?php

namespace Platform\Lists\Policies;

use Platform\Core\Models\User;
use Platform\Lists\Models\ListsList;

class ListPolicy
{
    public function view(User $user, ListsList $list): bool
    {
        return $list->team_id === $user->currentTeam?->id;
    }

    public function update(User $user, ListsList $list): bool
    {
        return $list->team_id === $user->currentTeam?->id;
    }

    public function delete(User $user, ListsList $list): bool
    {
        return $list->team_id === $user->currentTeam?->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }
}
