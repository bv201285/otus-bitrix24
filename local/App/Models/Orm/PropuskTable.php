<?php

namespace App\Models\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;

use App\Models\Lists\DoctorsPropertyValuesTable;
use App\Models\Lists\BuildingsPropertyValuesTable;

class PropuskTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'otus_propusk';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('TITLE'))
                ->configureRequired()
                ->configureSize(255),

            (new DateField('VALIDITY_PERIOD'))
                ->configureDefaultValue(function() {
                    // Создаем дату: 31 декабря текущего года
                    return new Date(date('Y') . '-12-31', 'Y-m-d');
                }),

            (new IntegerField('DOCTOR_ID'))
                ->configureRequired(),

            (new IntegerField('BUILDINGS_ID'))
                ->configureRequired(),

            (new Reference(
                'DOCTOR',
                DoctorsPropertyValuesTable::class,
                Join::on('this.DOCTOR_ID', 'ref.IBLOCK_ELEMENT_ID')
            ))

            /*(new Reference(
                'BUILDINGS',
                BuildingsPropertyValuesTable::class,
                Join::on('this.BUILDINGS_ID', 'ref.IBLOCK_ELEMENT_ID')
            ))*/
        ];
    }


}
