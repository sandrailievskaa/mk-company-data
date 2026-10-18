<?php

return [

    /*
    | Languages of the admin panel. The source language of every UI text is
    | Macedonian (the text itself is the translation key); lang/en.json holds
    | the English translations. Filament's own texts come from lang/vendor.
    */
    'supported' => [
        'mk' => 'Македонски',
        'en' => 'English',
    ],

    'default' => env('APP_LOCALE', 'mk'),
];
