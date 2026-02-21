<?php

namespace App\UserTypes;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

class BookingProcedure
{
    private static array $nameCache = []; // [procedureId => name]

    public static function GetUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'E',
            'USER_TYPE' => 'booking_procedure',
            'DESCRIPTION' => 'Процедуры (бронирование)',
            'GetPublicViewHTML' => [static::class, 'GetPublicViewHTML'],
        ];
    }


    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName): string
    {
        Asset::getInstance()->addJs('/local/js/booking-procedure.js');
        Asset::getInstance()->addCss('/local/assets/css/booking-procedure.css');


        $doctorId = (int)($arProperty['ELEMENT_ID'] ?? 0);
        $procedureId = (int)($value['VALUE'] ?? 0);

        if ($doctorId <= 0 || $procedureId <= 0) {
            return '';
        }

        $title = static::getProcedureName($procedureId);
        $title = htmlspecialcharsbx($title !== '' ? $title : ('Процедура #' . $procedureId));

        return sprintf(
            '<a href="javascript:void(0)"
                class="booking-procedure-link"
                data-doctor-id="%d"
                data-procedure-id="%d">%s</a>',
            $doctorId,
            $procedureId,
            $title
        );
    }

    private static function getProcedureName(int $procedureId): string
    {
        if (isset(static::$nameCache[$procedureId])) {
            return static::$nameCache[$procedureId];
        }

        Loader::includeModule('iblock');

        $row = ElementTable::getRow([
            'select' => ['ID','NAME'],
            'filter' => ['=ID' => $procedureId],
        ]);

        static::$nameCache[$procedureId] = $row['NAME'] ?? '';
        return static::$nameCache[$procedureId];
    }
}