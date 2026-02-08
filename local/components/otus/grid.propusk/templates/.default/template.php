<script>
    // Создаем экземпляр обработчика, используя ID из arResult
    window.PropuskGridHandler = new PropuskGrid('<?= \CUtil::JSEscape($arResult['GRID_ID']) ?>');
</script>
<?php
/*dump($arParams);
dump($arResult);*/

global $APPLICATION;

$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => $arResult['GRID_ID'],
    'GRID_ID' => $arResult['GRID_ID'],
    'FILTER' => $arResult['FILTER_FIELDS'],
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true
]);

$APPLICATION->includeComponent(
    "bitrix:main.ui.grid",
    "",
    [
        'GRID_ID' => $arResult['GRID_ID'],
        'COLUMNS' => $arResult['COLUMNS'],
        'ROWS' => $arResult['ROWS'],
        'SORT' => $arResult['SORT'],
        'NAV_OBJECT' => $arResult['NAV_OBJECT'],
        'AJAX_MODE' => 'Y',
        'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_HISTORY' => 'N',
        'SHOW_ROW_CHECKBOXES' =>$arResult['SHOW_ROW_CHECKBOXES'],
        'SHOW_SELECTED_COUNTER' => true,
        'SHOW_TOTAL_COUNTER' => true,
        'SHOW_ROW_ACTIONS_MENU' => true,
        'SHOW_PAGESIZE' => true,
        'PAGE_SIZES' =>  [
            ['NAME' => '1', 'VALUE' => '1'],
            ['NAME' => '20', 'VALUE' => '20'],
            ['NAME' => '50', 'VALUE' => '50'],
            ['NAME' => '100', 'VALUE' => '100']
        ],
        'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
        'ACTION_PANEL'  => [
            'GROUPS' => [
                'TYPE' => [
                    'ITEMS' => [
                        [
                            'ID'       => 'DELETE_ITEM',
                            'TYPE'     => 'BUTTON',
                            'TEXT'     => 'Удалить',
                            'CLASS'    => 'ui-btn ui-btn-danger-light',
                            'ONCHANGE' => $arResult['ACTION_DELETE'],
                        ],
                    ],
                ]
            ],
        ],

    ]
);