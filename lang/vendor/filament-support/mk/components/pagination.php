<?php

return [
    'actions' => [
        'first' => [
            'label' => 'Прва',
        ],
        'go_to_page' => [
            'label' => 'Оди на страница :page',
        ],
        'last' => [
            'label' => 'Последна',
        ],
        'next' => [
            'label' => 'Следна',
        ],
        'previous' => [
            'label' => 'Претходна',
        ],
    ],
    'fields' => [
        'records_per_page' => [
            'label' => 'По страница',
            'options' => [
                'all' => 'Сите',
            ],
        ],
    ],
    'label' => 'Навигација по страници',
    'overview' => '{1} Се прикажува 1 резултат|[2,*] Се прикажуваат :first до :last од :total резултати',
];
