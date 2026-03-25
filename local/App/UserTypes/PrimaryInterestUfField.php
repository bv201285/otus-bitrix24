<?php

namespace App\UserTypes;

use App\Models\Lists\MarketingActivitiesPropertyValuesTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Types\StringFormattedType;
use Bitrix\Main\Web\Json;
use CUserTypeManager;

class PrimaryInterestUfField extends StringFormattedType
{
    public const USER_TYPE_ID = 'primary_interest_uf_field';
    public const RENDER_COMPONENT = 'otus:main.field.primary_interest';

    public static function getDescription(): array
    {
        return [
            'DESCRIPTION' => 'Первичный интерес (канал + зависимое значение)',
            'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING,
        ];
    }

    public static function getDbColumnType(): string
    {
        return 'text';
    }

    /** Каналы (1-й select) */
    public static function getChannels(): array
    {
        return [
            '' => 'Выберите канал',
            'exhibition'     => 'Выставка',
            'recommendation' => 'Рекомендация',
            'site'           => 'Сайт предприятия',
            'employee'       => 'Сотрудник',
            'ebase'          => 'Электронная база',
        ];
    }

    /**
     * Читаемый вывод (list/view).
     */
    public static function renderReadable(string $raw): string
    {
        static $cache = [
            'marketing' => [], // [id => name]
            'company'   => [], // [id => title]
            'user'      => [], // [id => fio]
        ];

        $raw = trim($raw);
        if ($raw === '') return '';

        try {
            $data = Json::decode($raw);
        } catch (\Throwable $e) {
            return htmlspecialcharsbx($raw);
        }
        if (!is_array($data)) return htmlspecialcharsbx($raw);

        $channel = (string)($data['channel'] ?? '');
        $type    = (string)($data['type'] ?? '');
        $value   = (string)($data['value'] ?? '');

        $channelLabel = static::getChannels()[$channel] ?? $channel;
        if ($channelLabel === '') return '';

        // Каналы без второго значения
        if (in_array($channel, ['site', 'ebase'], true)) {
            return htmlspecialcharsbx($channelLabel);
        }

        $id = (int)$value;
        if ($id <= 0) {
            return htmlspecialcharsbx($channelLabel) . ': —';
        }

        $name = null;

        // Выставка -> marketingActivities element NAME
        if ($channel === 'exhibition') {
            if (!isset($cache['marketing'][$id])) {
                $cache['marketing'][$id] = null;

                if (Loader::includeModule('iblock')) {
                    try {
                        $iblockId = MarketingActivitiesPropertyValuesTable::getIblockId();
                        $row = ElementTable::getRow([
                            'select' => ['ID', 'NAME'],
                            'filter' => ['=ID' => $id, '=IBLOCK_ID' => $iblockId],
                        ]);
                        $cache['marketing'][$id] = $row['NAME'] ?? null;
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                if (!$cache['marketing'][$id]) {
                    $cache['marketing'][$id] = '#' . $id;
                }
            }
            $name = $cache['marketing'][$id];
        }

        // Рекомендация -> CRM company TITLE
        elseif ($channel === 'recommendation') {
            if (!isset($cache['company'][$id])) {
                $cache['company'][$id] = null;

                if (Loader::includeModule('crm')) {
                    $row = \Bitrix\Crm\CompanyTable::getRow([
                        'select' => ['ID', 'TITLE'],
                        'filter' => ['=ID' => $id],
                    ]);
                    $cache['company'][$id] = $row['TITLE'] ?? null;
                }

                if (!$cache['company'][$id]) {
                    $cache['company'][$id] = '#' . $id;
                }
            }
            $name = $cache['company'][$id];
        }

        // Сотрудник -> user FIO
        elseif ($channel === 'employee') {
            if (!isset($cache['user'][$id])) {
                $cache['user'][$id] = null;

                if (Loader::includeModule('main')) {
                    $row = \Bitrix\Main\UserTable::getRow([
                        'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
                        'filter' => ['=ID' => $id],
                    ]);

                    if ($row) {
                        $fio = trim(implode(' ', array_filter([
                            (string)($row['LAST_NAME'] ?? ''),
                            (string)($row['NAME'] ?? ''),
                            (string)($row['SECOND_NAME'] ?? ''),
                        ])));
                        if ($fio === '') $fio = (string)($row['LOGIN'] ?? '');
                        $cache['user'][$id] = $fio ?: null;
                    }
                }

                if (!$cache['user'][$id]) {
                    $cache['user'][$id] = '#' . $id;
                }
            }
            $name = $cache['user'][$id];
        }

        if ($name === null) {
            $name = '#' . $id;
        }

        return htmlspecialcharsbx($channelLabel) . ': ' . htmlspecialcharsbx($name);
    }

    /** Безопасный decode */
    public static function decodeValue(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return ['channel' => '', 'type' => '', 'value' => ''];

        try {
            $data = Json::decode($raw);
        } catch (\Throwable $e) {
            return ['channel' => '', 'type' => '', 'value' => ''];
        }

        if (!is_array($data)) return ['channel' => '', 'type' => '', 'value' => ''];

        return [
            'channel' => (string)($data['channel'] ?? ''),
            'type'    => (string)($data['type'] ?? ''),
            'value'   => (string)($data['value'] ?? ''),
        ];
    }
}