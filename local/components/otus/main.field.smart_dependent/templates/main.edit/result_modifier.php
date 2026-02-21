<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use App\UserTypes\SmartDependentUfField;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;


CJSCore::init(['uf']); // важно для UF/CRM

Asset::getInstance()->addJs('/local/js/smart-dependent/smart-dependent.js');
Asset::getInstance()->addCss('/local/assets/css/smart-dependent/smart-dependent.css');

$values = (
is_array($arResult['userField']['VALUE'])
    ? (count($arResult['userField']['VALUE']) ? $arResult['userField']['VALUE'] : [0 => ''])
    : [$arResult['userField']['VALUE']]
);

$arResult['fieldValues'] = [];

$modesMap = SmartDependentUfField::getModes();

foreach ($values as $key => $rawValue)
{
    $rawValue = (string)$rawValue;

    $name = str_replace('[]', '['.$key.']', $arResult['fieldName']);
    $uid = 'sdc_' . md5($name);

    $stored = [];
    if ($rawValue !== '') {
        try { $stored = Json::decode($rawValue); } catch (\Throwable $e) { $stored = []; }
        if (!is_array($stored)) $stored = [];
    }

    $mode = (string)($stored['mode'] ?? '');
    $iblockApi = (string)($stored['iblock'] ?? '');
    $val = (string)($stored['value'] ?? '');

    $options = '';
    foreach ($modesMap as $k => $label) {
        $sel = ($k !== '' && $k === $mode) ? ' selected' : '';
        $options .= '<option value="'.htmlspecialcharsbx($k).'"'.$sel.'>'.htmlspecialcharsbx($label).'</option>';
    }

    $html = '
      <div class="smart-dep" id="'.$uid.'">
        <input type="hidden" name="'.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx($rawValue).'" data-role="store">

        <div class="smart-dep__row">
          <div class="smart-dep__label">Тип</div>
          <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
            <div class="ui-ctl-after ui-ctl-icon-angle"></div>
            <select class="ui-ctl-element" data-role="mode">'.$options.'</select>
          </div>
        </div>

        <div class="smart-dep__row">
          <div class="smart-dep__label">Значение</div>
          <div data-role="valueContainer"></div>
        </div>

        <script>
          window.SDC_InitQueue = window.SDC_InitQueue || [];
          window.SDC_InitQueue.push({
            rootId: "'.\CUtil::JSEscape($uid).'",
            stored: '.Json::encode([
            'mode' => $mode,
            'iblock' => $iblockApi,
            'value' => $val,
        ]).'
          });
          if (window.SDC_InitAll) { window.SDC_InitAll(); }
        </script>
      </div>
    ';

    $arResult['fieldValues'][$key] = ['html' => $html];
}