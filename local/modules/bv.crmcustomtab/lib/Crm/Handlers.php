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

        \Bitrix\Main\Diag\Debug::writeToFile(
            $tabs,
            'log data',
            '/local/logs/crm_tabs.log'
        );


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

    public static function updateMain(Event $event): EventResult
    {
        $config = $event->getParameter('configuration'); // массив конфигурации editor'а
        \Bitrix\Main\Diag\Debug::writeToFile(
            $config,
            'log data',
            '/local/logs/crm_tabs.log'
        );

        $entityTypeId = (int)$event->getParameter('entityTypeID');
        if ($entityTypeId === \CCrmOwnerType::Deal) {
            dd($config);
        }

        $config = $event->getParameter('configuration'); // массив конфигурации editor'а

        // Дальше нужно добавить section / element в нужное место.
        // Внутри обычно элементы вида: ['name' => '...', 'type' => 'section', 'elements' => [...]]
        // Для кастомного HTML обычно используют type='custom' и JS-рендер.

        return new EventResult(\Bitrix\Main\EventResult::SUCCESS, [
            'configuration' => $config
        ]);
    }
}
