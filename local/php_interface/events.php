<?php

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler(
    '',
    'App\Models\Orm\Propusk::OnBeforeAdd',
    function(Event $event) {
        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->clearByTag('PROPUSK_LIST');

        $result = new EventResult();
        return $result;
    }
);


