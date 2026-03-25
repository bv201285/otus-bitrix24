<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use App\UserTypes\PrimaryInterestUfField;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

CJSCore::init(['uf']);

Asset::getInstance()->addJs('/local/js/primary-interest/primary-interest.js');
Asset::getInstance()->addCss('/local/assets/css/primary-interest/primary-interest.css');

$values = (
is_array($arResult['userField']['VALUE'])
    ? (count($arResult['userField']['VALUE']) ? $arResult['userField']['VALUE'] : [0 => ''])
    : [$arResult['userField']['VALUE']]
);

$arResult['fieldValues'] = [];
$channelsMap = PrimaryInterestUfField::getChannels();

foreach ($values as $key => $rawValue)
{
    $rawValue = (string)$rawValue;

    $name = str_replace('[]', '['.$key.']', $arResult['fieldName']);
    $uid = 'pi_' . md5($name);

    $stored = [];
    if ($rawValue !== '') {
        try { $stored = Json::decode($rawValue); } catch (\Throwable $e) { $stored = []; }
        if (!is_array($stored)) $stored = [];
    }

    $channel = (string)($stored['channel'] ?? '');
    $type    = (string)($stored['type'] ?? '');
    $val     = (string)($stored['value'] ?? '');

    $options = '';
    foreach ($channelsMap as $k => $label) {
        $sel = ($k !== '' && $k === $channel) ? ' selected' : '';
        $options .= '<option value="'.htmlspecialcharsbx($k).'"'.$sel.'>'.htmlspecialcharsbx($label).'</option>';
    }

    $html = '
      <div class="primary-interest" id="'.$uid.'">
        <input type="hidden" name="'.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx($rawValue).'" data-role="store">

        <div class="primary-interest__row">
          <div class="primary-interest__label">Канал</div>
          <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
            <div class="ui-ctl-after ui-ctl-icon-angle"></div>
            <select class="ui-ctl-element" data-role="channel">'.$options.'</select>
          </div>
        </div>

        <div class="primary-interest__row">
          <div class="primary-interest__label">Значение</div>
          <div data-role="valueContainer"></div>
        </div>

        <script>
          window.PI_InitQueue = window.PI_InitQueue || [];
          window.PI_InitQueue.push({
            rootId: "'.\CUtil::JSEscape($uid).'",
            stored: '.Json::encode([
            'channel' => $channel,
            'type' => $type,
            'value' => $val,
        ]).'
          });
          if (window.PI_InitAll) { window.PI_InitAll(); }
        </script>
      </div>
    ';

    $arResult['fieldValues'][$key] = ['html' => $html];
}