<?php

return [
    'actions' => [
        'column_manager' => [
            'label' => 'Колони',
        ],
        'filter' => [
            'label' => 'Филтри',
        ],
        'group' => [
            'label' => 'Групирај',
        ],
        'open_bulk_actions' => [
            'label' => 'Групни акции',
        ],
    ],
    'column_manager' => [
        'actions' => [
            'apply' => [
                'label' => 'Примени колони',
            ],
            'reset' => [
                'label' => 'Ресетирај',
            ],
        ],
        'heading' => 'Колони',
    ],
    'columns' => [
        'actions' => [
            'label' => 'Акција|Акции',
        ],
        'select' => [
            'loading_message' => 'Се вчитува…',
            'no_search_results_message' => 'Нема опции што одговараат на пребарувањето.',
            'placeholder' => 'Избери опција',
            'search_prompt' => 'Почни да пишуваш за пребарување…',
            'searching_message' => 'Се пребарува…',
        ],
        'text' => [
            'actions' => [
                'collapse_list' => 'Прикажи :count помалку',
                'expand_list' => 'Прикажи уште :count',
            ],
            'more_list_items' => 'и уште :count',
        ],
    ],
    'default_model_label' => 'запис',
    'empty' => [
        'description' => 'Креирај :model за да започнете.',
        'heading' => 'Нема :model',
    ],
    'fields' => [
        'bulk_select_group' => [
            'label' => 'Избери/одбери група :title за групни акции.',
        ],
        'bulk_select_page' => [
            'label' => 'Избери/одбери ги сите ставки за групни акции.',
        ],
        'bulk_select_record' => [
            'label' => 'Избери/одбери ставка :key за групни акции.',
        ],
        'search' => [
            'indicator' => 'Пребарување',
            'label' => 'Пребарување',
            'placeholder' => 'Пребарај',
        ],
    ],
    'filters' => [
        'actions' => [
            'apply' => [
                'label' => 'Примени филтри',
            ],
            'remove' => [
                'label' => 'Отстрани филтер',
            ],
            'remove_all' => [
                'label' => 'Отстрани ги сите филтри',
                'tooltip' => 'Отстрани ги сите филтри',
            ],
            'reset' => [
                'label' => 'Ресетирај',
            ],
        ],
        'heading' => 'Филтри',
        'indicator' => 'Активни филтри',
        'multi_select' => [
            'placeholder' => 'Сите',
        ],
        'select' => [
            'placeholder' => 'Сите',
            'relationship' => [
                'empty_option_label' => 'Нема',
            ],
        ],
    ],
    'selection_indicator' => [
        'actions' => [
            'deselect_all' => [
                'label' => 'Одбери ништо',
            ],
            'select_all' => [
                'label' => 'Избери ги сите :count',
            ],
        ],
        'selected_count' => '1 избран запис|:count избрани записи',
    ],
    'sorting' => [
        'fields' => [
            'column' => [
                'label' => 'Подреди по',
            ],
            'direction' => [
                'label' => 'Насока',
                'options' => [
                    'asc' => 'Растечки',
                    'desc' => 'Опаѓачки',
                ],
            ],
        ],
    ],
];
