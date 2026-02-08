<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

$arComponentDescription = [
    "NAME" => Loc::getMessage("NAME_COMPONENT"),
    "DESCRIPTION" => Loc::getMessage("DESCRIPTION_COMPONENT"),
    "COMPLEX" => "N",
    "CACHE_PATH" => "Y",
    "PATH" => [
        "ID" => 'currency_component',
        "NAME" => Loc::getMessage("PATH_NAME"),
    ],
];

