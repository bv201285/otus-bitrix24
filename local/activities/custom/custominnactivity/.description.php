<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    "NAME" => 'Мой кастомный поиск по ИНН',
    "DESCRIPTION" => 'Мой кастомный поиск по ИНН',
    "TYPE" => "activity",
    "CLASS" => "CustomInnActivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => [
        "ID" => "other",
    ],
    "RETURN" => [
        "Text" => [
            "NAME" => 'Название компании',
            "TYPE" => "string",
        ],
    ],
];