<?php

use Platform\Lists\Livewire\Board;
use Platform\Lists\Livewire\Dashboard;
use Platform\Lists\Livewire\List as ListComponent;
use Platform\Lists\Models\ListsBoard;
use Platform\Lists\Models\ListsList;

Route::get('/', Dashboard::class)->name('lists.dashboard');

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/boards/{listsBoard}', Board::class)
    ->name('lists.boards.show');

// List Routes
Route::get('/lists/{listsList}', ListComponent::class)
    ->name('lists.lists.show');
