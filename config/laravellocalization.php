<?php

return [
    'supportedLocales' => [
        'az' => ['name' => 'Azerbaijani', 'script' => 'Latn', 'native' => 'Azərbaycan', 'regional' => 'az_AZ'],
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
        'ru' => ['name' => 'Russian', 'script' => 'Cyrl', 'native' => 'Русский', 'regional' => 'ru_RU'],
    ],

    'useAcceptLanguageHeader' => false,

    'hideDefaultLocaleInURL' => true,

    'localesOrder' => ['az', 'en', 'ru'],

    'localesMapping' => [],

    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),

    'urlsIgnored' => ['/admin', '/admin/*'],

    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
