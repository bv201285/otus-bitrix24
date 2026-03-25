<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arResult['fieldValues'] = [
    'attrList' => [
        'name'  => $arResult['additionalParameters']['NAME'],
        'value' => (string)$arResult['additionalParameters']['VALUE'],
        'type'  => 'text',
    ]
];