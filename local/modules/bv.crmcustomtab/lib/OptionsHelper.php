<?php

namespace BV\Crmcustomtab;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\Loader;

class OptionsHelper
{
    public static function getCrmEntityTypeIds(): array
    {
        if (!Loader::includeModule('crm')) {
            return [];
        }

        return [
            \CCrmOwnerType::Lead => 'Лид',
            \CCrmOwnerType::Deal => 'Сделка',
            \CCrmOwnerType::Contact => 'Контакт',
            \CCrmOwnerType::Company => 'Компания',
        ] + self::getDynamicsEntityTypeId();
    }

    public static function getDynamicsEntityTypeId(): array
    {
        $rawDynamics = TypeTable::getList([
            'select' => [
                'ENTITY_TYPE_ID',
                'TITLE',
            ],
        ])->fetchCollection();

        $dynamics = [];
        foreach ($rawDynamics as $dynamic) {
            $dynamics[$dynamic->getEntityTypeId()] = $dynamic->getTitle();
        }

        return $dynamics;
    }
}
