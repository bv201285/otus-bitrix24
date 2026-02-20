<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;

class bv_crmcustomtab extends \CModule
{
    public function __construct()
    {
        $this->MODULE_ID = 'bv.crmcustomtab';
        $this->MODULE_NAME = 'Таб с пропусками в сделке';
        $this->MODULE_DESCRIPTION = 'Модуль, выводящий список пропусков в сделке';
        $this->MODULE_VERSION = '1.0.1';
        $this->MODULE_VERSION_DATE = '2026-02-18';
        $this->PARTNER_NAME = 'BV';
        $this->PARTNER_URI = 'https://rudolf.by';
    }

    public function DoInstall()
    {
        $this->InstallEvents();
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUnInstall()
    {
        $this->UnInstallEvents();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\BV\\Crmcustomtab\\Crm\\Handlers',
            'updateTabs'
        );

        $eventManager->registerEventHandler(
            'crm',
            'OnEntityEditorConfiguration',
            $this->MODULE_ID,
            '\\BV\\Crmcustomtab\\Crm\\Handlers',
            'updateMain'
        );
    }

    public function UnInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\BV\\Crmcustomtab\\Crm\\Handlers',
            'updateTabs'
        );

        $eventManager->unRegisterEventHandler(
            'crm',
            'OnEntityEditorConfiguration',
            $this->MODULE_ID,
            '\\BV\\Crmcustomtab\\Crm\\Handlers',
            'updateMain'
        );
    }
}
