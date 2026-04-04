<?php
global $APPLICATION;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

use Bitrix\Main\Page\Asset;


$APPLICATION->SetTitle('ДЗ #5: Компонент списка таблицы БД (курс валюты)');

Asset::getInstance()->addString('<script src="https://cdn.tailwindcss.com"></script>');


// Для того чтобы подгружать эту страницу php в слайдере (BX.SidePanel) нужно компонент оборачивать следующим образом
$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper', '', [
    'POPUP_COMPONENT_NAME' => 'otus:currency',
    'POPUP_COMPONENT_TEMPLATE_NAME' => '',
    'POPUP_COMPONENT_PARAMS' => [
        'CURRENCY' => 'USD',
    ],
]);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
