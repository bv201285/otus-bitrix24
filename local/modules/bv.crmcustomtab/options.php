<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use BV\Crmcustomtab\OptionsHelper;

Loc::loadMessages(__FILE__);
/**
 * @global CMain $APPLICATION
 */

$request = HttpApplication::getInstance()->getContext()->getRequest();

Loader::includeModule('main');

$moduleId = htmlspecialcharsbx('' != $request['mid'] ? $request['mid'] : $request['id']);

Loader::includeModule($moduleId);

$aTabsStatic = [
    [
        'DIV' => 'crm_tab_settings',
        'TAB' => 'Настройки таба с пропусками',
        'ICON' => '',
        'TITLE' => 'Настройки таба с пропусками',
        'OPTIONS' => [
            [
                'ACTIVE',
                'Модуль активен?',
                'Y',
                [
                    'checkbox',
                    '',
                ],
            ],
            [
                'TAB_DISPLAY_CRM_ENTITY_TYPE_ID',
                'В какой сущности CRM отображать Таб?',
                '2',
                [
                    'multiselectbox',
                    OptionsHelper::getCrmEntityTypeIds(),
                ],
            ],
        ],
    ],
];

/**
 * @todo динамические опции
 */

$aTabs = $aTabsStatic;

if ($request->isPost() && check_bitrix_sessid()) {

    foreach ($aTabs as $aTab) {

        foreach ($aTab['OPTIONS'] as $arOption) {

            if (!is_array($arOption)) {
                continue;
            }

            $optionName = $arOption[0];
            if ($request['apply']) {

                $optionValue = $request->getPost($optionName);
                if (in_array($optionName, ['ACTIVE'], true)) {
                    if ('' == $optionValue) {
                        $optionValue = 'N';
                    } else {
                        $optionValue = 'Y';
                    }
                }

                if (!empty($optionValue) || 0 === $optionValue) {
                    Option::set($moduleId, $optionName, is_array($optionValue) ? implode(',', $optionValue) : $optionValue);
                } else {
                    Option::delete($moduleId, ['name' => $optionName]);
                }
            } elseif ($request['default']) {
                Option::set($moduleId, $optionName, $arOption[2]);
            }
        }
    }

    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . $moduleId . '&lang=' . LANG);
} ?>

<?php
$tabControl = new CAdminTabControl(
    'tabControl',
    $aTabs,
    false
);

$tabControl->Begin();
?>
<form action='<?php echo ($APPLICATION->GetCurPage()); ?>?mid=<?= $moduleId ?>&lang=<?= LANG ?>'
    method='post'>

    <?php

    foreach ($aTabs as $aTab) {

        $tabControl->BeginNextTab();
        if ($aTab['OPTIONS']) {
            __AdmSettingsDrawList($moduleId, $aTab['OPTIONS']);
        }
    }
    $tabControl->Buttons();
    ?>

    <input type='submit' name='apply' value='<?= Loc::getMessage('MAIN_SAVE') ?>' class='adm-btn-save' />

    <?= bitrix_sessid_post() ?>

</form>
<?php $tabControl->End(); ?>