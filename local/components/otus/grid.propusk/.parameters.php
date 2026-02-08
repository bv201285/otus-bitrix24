<?php
use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => [
            'NAME' => Loc::getMessage('GRID_PARAMETERS'),
            'SORT' => "300"
        ]
    ],
    'PARAMETERS' => [
        'GRID_ID' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('GRID_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'SHOW_CHECKBOXES' =>  [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('SHOW_CHECKBOXES'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N'
        ]
    ]
];




