<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\BV\\Crmcustomtab\\Controllers',
            'namespaces' => [
                '\\BV\\Crmcustomtab\\Controllers' => 'propusk', // можно указать свой неймспейс без указания имени класса
            ],
        ],
    ],
];
