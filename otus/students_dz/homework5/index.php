<?php
global $APPLICATION;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

use Bitrix\Main\Page\Asset;


$APPLICATION->SetTitle('ДЗ #5: Компонент списка таблицы БД (курс валюты)');

Asset::getInstance()->addString('<script src="https://cdn.tailwindcss.com"></script>');


$APPLICATION->IncludeComponent(
    'otus:currency',
    "",
    [
        'CURRENCY' => 'USD',
    ]
);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
