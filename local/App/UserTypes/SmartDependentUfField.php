<?php

namespace App\UserTypes;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Types\StringFormattedType;
use CUserTypeManager;
use Bitrix\Main\Web\Json;

class SmartDependentUfField extends StringFormattedType
{
    public const USER_TYPE_ID = 'smart_dependent_uf_field';
    public const RENDER_COMPONENT = 'otus:main.field.smart_dependent';

    public static function getDescription(): array
    {
        return [
            'DESCRIPTION' => 'Составное поле (справочник/строка/email/url)',
            'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING,
        ];
    }

    public static function getDbColumnType(): string
    {
        return 'text';
    }

    /** Режимы для 1-го select */
    public static function getModes(): array
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

    /** Читаемый вывод (используем в view/list) */
    public static function renderReadable(string $raw): string
    {
        // кеш имен элементов на время одного запроса
        static $nameCache = []; // [int elementId => string name]

        $raw = trim($raw);
        if ($raw === '') return '';

        try {
            $data = Json::decode($raw);
        } catch (\Throwable $e) {
            return htmlspecialcharsbx($raw);
        }

        if (!is_array($data)) {
            return htmlspecialcharsbx($raw);
        }

        $mode  = (string)($data['mode'] ?? '');
        $value = (string)($data['value'] ?? '');

        if ($mode === 'text') {
            return htmlspecialcharsbx($value);
        }
        if ($mode === 'email') {
            $email = htmlspecialcharsbx($value);
            return $email !== '' ? '<a href="mailto:'.$email.'">'.$email.'</a>' : '';
        }
        if ($mode === 'url') {
            $url = htmlspecialcharsbx($value);
            return $url !== '' ? '<a href="'.$url.'" target="_blank" rel="noopener">'.$url.'</a>' : '';
        }

        $title = static::getModes()[$mode] ?? $mode;

        if (strpos($mode, 'iblock:') === 0) {
            $id = (int)$value;
            if ($id <= 0) {
                return htmlspecialcharsbx($title) . ': —';
            }

            if (!isset($nameCache[$id])) {
                $name = null;

                if (Loader::includeModule('iblock')) {
                    $row = ElementTable::getRow([
                        'select' => ['ID', 'NAME'],
                        'filter' => ['=ID' => $id],
                    ]);
                    $name = $row['NAME'] ?? null;
                }

                $nameCache[$id] = $name ?: ('#' . $id);
            }

            return htmlspecialcharsbx($title) . ': ' . htmlspecialcharsbx($nameCache[$id]);
        }

        return htmlspecialcharsbx($title) . ': ' . htmlspecialcharsbx($value);
    }
}