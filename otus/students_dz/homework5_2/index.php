<?php
global $APPLICATION;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

use Bitrix\Main\Page\Asset;


$APPLICATION->SetTitle('ДЗ #5_2: Компонент списка таблицы БД (доп. Grid - пропуска)');

//Asset::getInstance()->addString('<script src="https://cdn.tailwindcss.com"></script>');


$APPLICATION->IncludeComponent(
    'otus:grid.propusk',
    "",
    [
        'SHOW_CHECKBOXES' => 'Y',
        'GRID_ID' => 'PROPUSK_GRID',
        'PREFIX' => 'otus'
    ]
);



require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>