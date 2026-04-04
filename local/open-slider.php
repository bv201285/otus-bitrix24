<?php

use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons\Color;
use Bitrix\Main\UI\Extension;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/**
 * @var CMain $APPLICATION
 */

$APPLICATION->SetTitle('Кнопка, открывающая страницу в слайдере');

// открываем нужную страницу в слайдере
$button = [
    'click' => 'openSlider',
    'text' => 'Открыть страницу в слайдере',
    'color' => Color::SUCCESS,
];

// открываем страницу в слайдере предварительно добавив ее через sliderHelper расширение (BX.SidePanel.Instance.bindAnchors)
$button2 = [
    'link' => './currency_slider.php',
    'text' => 'Открыть страницу в слайдере 2',
    'color' => Color::DANGER,
];

Toolbar::addButton($button);
Toolbar::addButton($button2);
Extension::load('ui.sidepanel');
?>
<script>
    function openSlider() {
        BX.SidePanel.Instance.open('./currency_slider.php', {
            cacheable: false,
        });
    }
</script>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
