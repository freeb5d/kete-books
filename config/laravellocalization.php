<?php

return [
    'supportedLocales' => [
        'en' => [
            'name' => 'English',
            'script' => 'Latn',
            'native' => 'English',
            'regional' => 'en_NZ',
        ],
        'mi' => [
            'name' => 'Maori',
            'script' => 'Latn',
            'native' => 'Te Reo Māori',
            'regional' => 'mi_NZ',
        ],
    ],

    'useAcceptLanguageHeader' => true,

    'hideDefaultLocaleInURL' => false,

    'localesOrder' => ['en', 'mi'],

    'localesMapping' => [],

    'utf8suffix' => '.UTF-8',
];
