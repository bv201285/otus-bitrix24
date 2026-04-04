<?php

use Bitrix\Main\UI\Extension;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/**
 * @var CMain $APPLICATION
 */

$APPLICATION->SetTitle('Форма с кастомизированными инпутами');
Extension::load('aclips.ui-selector');
$arResult = [
    'SERVICES' => [
        'it_services' => [
            '1c' => '1С',
            'bitrix' => 'Битрикс CMS',
            'bitrix24' => 'Битрикс 24',
        ],
        'law_services' => [
            'advocate' => 'Решение споров на IT-проектах',
            'maintenance' => 'Сопровождение на IT-проектах',
        ],
    ],
    'TABS' => [
        [
            'id' => 'it_services',
            'title' => 'IT Услуги',
        ],
        [
            'id' => 'law_services',
            'title' => 'Услуги юристов',
        ],
    ],
    'selectedServiceId' => [
        'tabId' => 'it_services',
        'value' => 'bitrix24',
    ],
];

?>
<form>
    <select name="service[]" multiple id="service[]">
        <?php foreach ($arResult['SERVICES'] as $tabId => $services) {
            foreach ($services as $serviceId => $serviceTitle) {
        ?>
                <option data-tab="<?= $tabId ?>" <?= $arResult['selectedServiceId']['value'] === $serviceId && $arResult['selectedServiceId']['tabId'] === $tabId ? 'selected' : '' ?>
                    value="<?= $serviceId ?>">
                    <?= $serviceTitle ?>
                </option>
            <?php } ?>
        <?php } ?>
    </select>
</form>
<script>
    BX(() => {
        BX.Plugin.UiSelector.createTagSelector('service[]', {
            tabs: <?= json_encode($arResult['TABS']) ?>
        });
    });
</script>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
