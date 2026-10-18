<?php

return [
    'select' => [
        'actions' => [
            'create_option' => [
                'label' => 'Креирај',
                'modal' => [
                    'actions' => [
                        'create' => [
                            'label' => 'Креирај',
                        ],
                        'create_another' => [
                            'label' => 'Креирај и додај друг',
                        ],
                    ],
                    'heading' => 'Креирај',
                ],
            ],
            'edit_option' => [
                'label' => 'Уреди',
                'modal' => [
                    'actions' => [
                        'save' => [
                            'label' => 'Зачувај',
                        ],
                    ],
                    'heading' => 'Уреди',
                ],
            ],
        ],
        'boolean' => [
            'false' => 'Не',
            'true' => 'Да',
        ],
        'loading_message' => 'Се вчитува…',
        'max_items_message' => 'Може да се избере само :count.',
        'no_search_results_message' => 'Нема опции што одговараат на пребарувањето.',
        'placeholder' => 'Избери опција',
        'search_prompt' => 'Почни да пишуваш за пребарување…',
        'searching_message' => 'Се пребарува…',
    ],
    'toggle_buttons' => [
        'boolean' => [
            'false' => 'Не',
            'true' => 'Да',
        ],
    ],
    'rich_editor' => [
        'tools' => [
            'align_center' => 'Центрирај',
            'align_end' => 'Порамни десно',
            'align_justify' => 'Исправи',
            'align_start' => 'Порамни лево',
            'blockquote' => 'Цитат',
            'bold' => 'Задебелено',
            'bullet_list' => 'Листа со точки',
            'clear_formatting' => 'Отстрани форматирање',
            'code' => 'Код',
            'h1' => 'Наслов',
            'h2' => 'Поднаслов',
            'h3' => 'Помал поднаслов',
            'horizontal_rule' => 'Хоризонтална линија',
            'italic' => 'Закосено',
            'link' => 'Врска',
            'ordered_list' => 'Нумерирана листа',
            'redo' => 'Повтори',
            'strike' => 'Прецртано',
            'underline' => 'Подвлечено',
            'undo' => 'Врати',
        ],
        'actions' => [
            'link' => [
                'label' => 'Врска',
                'modal' => [
                    'heading' => 'Врска',
                    'form' => [
                        'url' => [
                            'label' => 'Адреса (URL)',
                        ],
                        'should_open_in_new_tab' => [
                            'label' => 'Отвори во нов таб',
                        ],
                    ],
                ],
            ],
        ],
        'uploading_file_message' => 'Се прикачува датотеката…',
    ],
];
