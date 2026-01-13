<?php

return [
    'routing' => [
        'mode' => env('LISTS_MODE', 'path'),
        'prefix' => 'lists',
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'lists.dashboard',
        'icon'  => 'heroicon-o-list-bullet',
        'order' => 40,
    ],

    'sidebar' => [
        [
            'group' => 'Listen',
            'dynamic' => [
                'model'     => \Platform\Lists\Models\ListsBoard::class,
                'team_based' => true,
                'order_by'  => 'name',
                'route'     => 'lists.boards.show',
                'icon'      => 'heroicon-o-list-bullet',
                'label_key' => 'name',
            ],
        ],
    ],
    'billables' => []
];
