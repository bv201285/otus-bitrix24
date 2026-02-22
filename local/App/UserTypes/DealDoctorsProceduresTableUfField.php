<?php

namespace App\UserTypes;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Types\StringFormattedType;
use Bitrix\Main\Web\Json;
use CUserTypeManager;

class DealDoctorsProceduresTableUfField extends StringFormattedType
{
    public const USER_TYPE_ID = 'deal_doctors_procedures_table';
    public const RENDER_COMPONENT = 'otus:main.field.deal_doctors_procedures_table';

    public static function getDescription(): array
    {
        return [
            'DESCRIPTION' => 'Таблица: Врач/Процедура/Дата/Комментарий',
            'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING,
        ];
    }

    public static function getDbColumnType(): string
    {
        return 'text';
    }

    /**
     * Читаемый вывод (view/list) — одной строкой/несколькими строками.
     * Важно: тут можно оптимизировать, делая batch-запросы. Пока простой вариант.
     */
    public static function renderReadable(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        try {
            $rows = Json::decode($raw);
        } catch (\Throwable $e) {
            return htmlspecialcharsbx($raw);
        }

        if (!is_array($rows)) return htmlspecialcharsbx($raw);

        $out = [];

        foreach ($rows as $r) {
            if (!is_array($r)) continue;

            $doctorId = (int)($r['doctorId'] ?? 0);
            $procId   = (int)($r['procedureId'] ?? 0);
            $date     = (string)($r['date'] ?? '');
            $text     = (string)($r['text'] ?? '');

            $doctorName = $doctorId ? static::getElementNameCached($doctorId) : '—';
            $procName   = $procId   ? static::getElementNameCached($procId)   : '—';

            $line = sprintf(
                'Врач: %s; Процедура: %s; Дата: %s; %s',
                $doctorName,
                $procName,
                $date !== '' ? $date : '—',
                $text !== '' ? ('Комментарий: '.$text) : ''
            );

            $out[] = htmlspecialcharsbx($line);
        }

        return implode('<br>', $out);
    }

    protected static function getElementNameCached(int $id): string
    {
        static $cache = [];

        if ($id <= 0) return '—';
        if (isset($cache[$id])) return $cache[$id];

        $name = '#'.$id;
        if (Loader::includeModule('iblock')) {
            $row = ElementTable::getRow([
                'select' => ['ID', 'NAME'],
                'filter' => ['=ID' => $id],
            ]);
            if ($row && $row['NAME']) {
                $name = (string)$row['NAME'];
            }
        }

        $cache[$id] = $name;
        return $name;
    }
}