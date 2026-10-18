<?php

return [
    'multiple' => [
        'label' => 'Избриши избрани',
        'modal' => [
            'actions' => [
                'delete' => [
                    'label' => 'Избриши',
                ],
            ],
            'heading' => 'Избриши избрани :label',
        ],
        'notifications' => [
            'deleted' => [
                'title' => 'Избришано',
            ],
            'deleted_none' => [
                'title' => 'Бришењето не успеа',
            ],
            'deleted_partial' => [
                'title' => 'Избришани :count од :total',
            ],
        ],
    ],
    'single' => [
        'label' => 'Избриши',
        'modal' => [
            'actions' => [
                'delete' => [
                    'label' => 'Избриши',
                ],
            ],
            'heading' => 'Избриши :label',
        ],
        'notifications' => [
            'deleted' => [
                'title' => 'Избришано',
            ],
        ],
    ],
];
