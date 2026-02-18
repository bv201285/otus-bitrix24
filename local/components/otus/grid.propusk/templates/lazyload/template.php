<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load([
    'ui.buttons',
    'ui.notification',
    'main.ui.filter',
    'main.ui.grid',
]);

use Bitrix\Main\Web\Json;

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponentTemplate $this
 */

$gridIdJs = \CUtil::JSEscape($arResult['GRID_ID']);
$handlerJS = "window.PropuskGridHandlers['{$gridIdJs}']";

?>

<div class="propusk-crm-tab" style="padding: 12px 0;">

    <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:12px;">

        <!-- Фильтр слева (занимает всё доступное место) -->
        <div style="flex:1; min-width:0;">
            <?php
            $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
                    'FILTER_ID' => $arResult['GRID_ID'],
                    'GRID_ID' => $arResult['GRID_ID'],
                    'FILTER' => $arResult['FILTER_FIELDS'],
                    'ENABLE_LIVE_SEARCH' => true,
                    'ENABLE_LABEL' => true,
            ]);
            ?>
        </div>

        <!-- Кнопка действий справа -->
        <div style="margin-left:auto; white-space:nowrap;">
            <button class="ui-btn ui-btn-success ui-btn-dropdown"
                    onclick="
                            BX.PopupMenu.show(
                            'propusk_actions_<?= $gridIdJs ?>',
                            this,
                            [
                            {text: 'Добавить пропуск', onclick: function(){ <?= $handlerJS ?>.addPropusk2(true); this.popupWindow.close(); }},
                            {delimiter: true},
                            {text: 'Удалить выбранные', onclick: function(){ <?= $handlerJS ?>.removeSelected(); this.popupWindow.close(); }},
                            ],
                            {autoHide: true, closeByEsc: true, offsetTop: 8}
                            );
                            ">
                Действия
            </button>
        </div>

    </div>

    <div style="margin-top: 12px;">
        <?php
        $APPLICATION->IncludeComponent("bitrix:main.ui.grid", "", [
                'GRID_ID' => $arResult['GRID_ID'],
                'COLUMNS' => $arResult['COLUMNS'],
                'ROWS' => $arResult['ROWS'],
                'SORT' => $arResult['SORT'],
                'NAV_OBJECT' => $arResult['NAV_OBJECT'],
                'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
                'AJAX_MODE' => 'Y',
                'AJAX_OPTION_JUMP' => 'N',
                'AJAX_OPTION_HISTORY' => 'N',
                'SHOW_ROW_CHECKBOXES' => $arResult['SHOW_ROW_CHECKBOXES'],
                'SHOW_SELECTED_COUNTER' => true,
                'SHOW_TOTAL_COUNTER' => true,
                'SHOW_ROW_ACTIONS_MENU' => true,
                'SHOW_PAGESIZE' => true,
                'PAGE_SIZES' => [
                        ['NAME' => '1', 'VALUE' => '1'],
                        ['NAME' => '20', 'VALUE' => '20'],
                        ['NAME' => '50', 'VALUE' => '50'],
                        ['NAME' => '100', 'VALUE' => '100'],
                ],
                'ACTION_PANEL' => $arResult['ACTION_PANEL'],
        ]);
        ?>
    </div>

</div>

<?php
// JS-конфиг для класса PropuskGrid
$configPropuskGridHandler = [
    'gridId' => $arResult['GRID_ID'],
    'doctors' => $arResult['DOCTORS'] ?? [],
    'buildings' => $arResult['BUILDINGS'] ?? [],
    'signedParameters' => $this->getComponent()->getSignedParameters(),
];

$configPropuskGridHandler = Json::encode($configPropuskGridHandler, JSON_UNESCAPED_UNICODE);

$ajaxUrl = '/local/components/otus/grid.propusk/lazyload.ajax.php'
        . '?site=' . SITE_ID
        . '&' . bitrix_sessid_get()
        . '&grid_id=' . urlencode($arResult['GRID_ID']);

?>

<script>
    BX.ready(function () {
        window.PropuskGridHandlers = window.PropuskGridHandlers || {};
        window.PropuskGridHandlers['<?= \CUtil::JSEscape($arResult['GRID_ID']) ?>'] = new PropuskGrid(<?= $configPropuskGridHandler ?>);


        const gridObj = BX.Main.gridManager.getById('<?= \CUtil::JSEscape($arResult['GRID_ID']) ?>');
        const grid = gridObj && gridObj.instance;

        if (!grid) {
            console.warn('Grid instance not found: <?= \CUtil::JSEscape($arResult['GRID_ID']) ?>');
            return;
        }

        grid.baseUrl = '<?= \CUtil::JSEscape($ajaxUrl) ?>';
        console.log('Grid ajax url forced to:', '<?= \CUtil::JSEscape($ajaxUrl) ?>');
    });





</script>

