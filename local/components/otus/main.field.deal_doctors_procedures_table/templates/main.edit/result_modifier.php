<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

CJSCore::init(['uf']);

Asset::getInstance()->addJs('/local/js/deal-dp-table/deal-dp-table.js');
Asset::getInstance()->addCss('/local/assets/css/deal-dp-table/deal-dp-table.css');

$rawValue = (string)($arResult['userField']['VALUE'] ?? '');
$rows = [];

if ($rawValue !== '') {
    try {
        $rows = Json::decode($rawValue);
    } catch (\Throwable $e) {
        $rows = [];
    }
}
if (!is_array($rows)) $rows = [];

// если пусто — стартовая одна строка
if (!count($rows)) {
    $rows = [
        ['doctorId' => '', 'procedureId' => '', 'date' => '', 'text' => '']
    ];
}

$arResult['ddpt'] = [
    'name' => $arResult['fieldName'], // тут без MULTIPLE, поле одно, но внутри таблица
    'uid'  => 'ddpt_' . md5($arResult['fieldName']),
    'rows' => $rows,
];