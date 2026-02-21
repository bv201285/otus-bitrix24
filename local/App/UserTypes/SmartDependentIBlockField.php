<?php

namespace App\UserTypes;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

class SmartDependentIBlockField
{
    public static function GetUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'smart_dependent',
            'DESCRIPTION' => 'Зависимое поле (справочник/строка/email/url)',
            'GetPropertyFieldHtml' => [static::class, 'GetPropertyFieldHtml'],
            'GetPublicEditHTML' => [static::class, 'GetPropertyFieldHtml'],
            'GetAdminListViewHTML' => [static::class, 'GetPublicViewHTML'],
            'GetPublicViewHTML'    => [static::class, 'GetPublicViewHTML'],
            'ConvertToDB' => [static::class, 'ConvertToDB'],
            'ConvertFromDB' => [static::class, 'ConvertFromDB'],
        ];
    }

    private static function getModes(): array
    {
        return [
            '' => 'Выберите тип',
            'iblock:Procedure' => 'Процедура (из справочника)',
            'iblock:Doctors'   => 'Врач (из справочника)',
            'text'  => 'Произвольная строка',
            'email' => 'Email',
            'url'   => 'URL',
        ];
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName): string
    {
        Asset::getInstance()->addJs('/local/js/smart-dependent/smart-dependent.js');
        Asset::getInstance()->addCss('/local/css/smart-dependent/smart-dependent.css');

        $stored = static::decode((string)($value['VALUE'] ?? ''));
        $mode = (string)($stored['mode'] ?? '');
        $iblockApi = (string)($stored['iblock'] ?? '');
        $val = (string)($stored['value'] ?? '');

        $inputName = htmlspecialcharsbx($strHTMLControlName['VALUE']);
        $uid = 'smart_dep_' . md5($inputName);

        $options = '';
        foreach (static::getModes() as $k => $label) {
            $sel = ($k !== '' && $k === $mode) ? ' selected' : '';
            $options .= '<option value="'.htmlspecialcharsbx($k).'"'.$sel.'>'.htmlspecialcharsbx($label).'</option>';
        }

        return '
        <div class="smart-dep" id="'.$uid.'">
          <input type="hidden" name="'.$inputName.'" value="'.htmlspecialcharsbx((string)($value['VALUE'] ?? '')).'" data-role="store">

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
            window.SmartDepInitQueue = window.SmartDepInitQueue || [];
            window.SmartDepInitQueue.push({
              rootId: "'.\CUtil::JSEscape($uid).'",
              stored: '.\Bitrix\Main\Web\Json::encode([
                'mode' => $mode,
                'iblock' => $iblockApi,
                'value' => $val,
            ]).'
            });
            if (window.SmartDepInitAll) window.SmartDepInitAll();
          </script>
        </div>';
    }

    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName): string
    {
        // локальный кеш имён элементов (на время одного HTTP-запроса)
        static $elementNameCache = []; // [int elementId => string name]

        $raw = (string)($value['VALUE'] ?? '');
        $raw = trim($raw);

        if ($raw === '') {
            return '';
        }

        // Пытаемся декодировать JSON (то, что вы храните в строковом свойстве)
        $data = [];
        try {
            $data = Json::decode($raw);
        } catch (\Throwable $e) {
            // не JSON — выводим как есть
            return htmlspecialcharsbx($raw);
        }

        if (!is_array($data)) {
            return htmlspecialcharsbx($raw);
        }

        $mode = (string)($data['mode'] ?? '');
        $val  = (string)($data['value'] ?? '');

        // --- режимы input ---
        if ($mode === 'text') {
            return htmlspecialcharsbx($val);
        }

        if ($mode === 'email') {
            $email = htmlspecialcharsbx($val);
            return $email !== '' ? '<a href="mailto:'.$email.'">'.$email.'</a>' : '';
        }

        if ($mode === 'url') {
            $url = htmlspecialcharsbx($val);
            return $url !== '' ? '<a href="'.$url.'" target="_blank" rel="noopener">'.$url.'</a>' : '';
        }

        // Человеческое название режима из вашего getModes()
        $modesMap = [];
        if (method_exists(static::class, 'getModes')) {
            $modesMap = (array)static::getModes(); // ['iblock:Procedure' => 'Процедура', ...]
        }
        $modeTitle = (string)($modesMap[$mode] ?? $mode);

        // --- режим "выбор элемента инфоблока" ---
        if (str_starts_with($mode, 'iblock:')) {
            $iblockApi = (string)($data['iblock'] ?? '');
            $elementId = (int)$val;

            if ($elementId <= 0) {
                return htmlspecialcharsbx($modeTitle) . ': —';
            }

            // имя элемента с кешем
            if (!isset($elementNameCache[$elementId])) {
                $name = null;

                if (Loader::includeModule('iblock')) {
                    $row = ElementTable::getRow([
                        'select' => ['ID', 'NAME'],
                        'filter' => ['=ID' => $elementId],
                    ]);
                    $name = $row['NAME'] ?? null;
                }

                $elementNameCache[$elementId] = $name ?: ('#' . $elementId);
            }

            $elementName = $elementNameCache[$elementId];

            // Можно вывести так: "Процедура (Procedure): Название"
            // либо без iblockApi:
            $left = $modeTitle;
            // если хотите показывать ещё и API_CODE справочника:
            // $left = $modeTitle . ($iblockApi !== '' ? " ({$iblockApi})" : '');

            return htmlspecialcharsbx($left) . ': ' . htmlspecialcharsbx($elementName);
        }

        // fallback на случай неизвестного mode
        return htmlspecialcharsbx($modeTitle) . ': ' . htmlspecialcharsbx($val);
    }

    public static function ConvertToDB($arProperty, $value): array { return $value; }
    public static function ConvertFromDB($arProperty, $value): array { return $value; }

    private static function decode(string $json): array
    {
        $json = trim($json);
        if ($json === '') return [];
        try {
            $data = Json::decode($json);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}