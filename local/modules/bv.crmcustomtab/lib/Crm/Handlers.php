<?php

namespace BV\Crmcustomtab\Crm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Config\Option;

class Handlers
{
    public const MID = 'bv.crmcustomtab';
    public static function updateTabs(Event $event): EventResult
    {
        $tabs = $event->getParameter('tabs');
        $isModuleActive = Option::get(self::MID, 'ACTIVE', 'N');
        $isModuleActive = $isModuleActive === 'Y';
        $availableEntityTypeIds = explode(
            ',',
            Option::get(self::MID, 'TAB_DISPLAY_CRM_ENTITY_TYPE_ID', '2'),
        );

        $entityId = (int)$event->getParameter('entityID');
        $entityTypeId = (int)$event->getParameter('entityTypeID');

        $gridId = 'PROPUSK_GRID_' . $entityTypeId . '_' . $entityId;

        if (
            $isModuleActive &&
            in_array($entityTypeId, $availableEntityTypeIds)
        ) {
            $tabs[] = [
                'id' => 'propusk_tab',
                'name' => 'Пропуска на вход',
                'loader' => [
                    'serviceUrl' => sprintf(
                        '/local/components/otus/grid.propusk/lazyload.ajax.php?site=%s&%s',
                        SITE_ID,
                        \bitrix_sessid_get(),
                    ),
                    'componentData' => [
                        'params' => [
                            'SHOW_CHECKBOXES' => 'Y',
                            'GRID_ID' => $gridId,
                            'PREFIX' => 'otus'
                        ],
                        'template' => 'lazyload',
                    ],
                ],
            ];
        }

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }
}
