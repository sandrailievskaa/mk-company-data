<?php

return [
    'actions' => [
        'request_password_reset' => [
            'label' => 'Ја заборавивте лозинката?',
        ],
    ],
    'form' => [
        'actions' => [
            'authenticate' => [
                'label' => 'Најави се',
            ],
        ],
        'email' => [
            'label' => 'Е-пошта',
        ],
        'password' => [
            'label' => 'Лозинка',
        ],
        'remember' => [
            'label' => 'Запомни ме',
        ],
    ],
    'heading' => 'Најава',
    'messages' => [
        'failed' => 'Овие податоци не се совпаѓаат со нашите записи.',
    ],
    'notifications' => [
        'throttled' => [
            'body' => 'Обидете се повторно за :seconds секунди.',
            'title' => 'Премногу обиди за најава',
        ],
    ],
    'title' => 'Најава',
];
