<?php

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\EventManager;
use Bitrix\Main\Page\Asset;

$eventManager = EventManager::getInstance();

// Пользовательские типы для свойства инфоблока
$eventManager->AddEventHandler('iblock','OnIBlockPropertyBuildList',
    ['App\UserTypes\BookingProcedure', 'GetUserTypeDescription']
);
$eventManager->AddEventHandler('iblock','OnIBlockPropertyBuildList',
    ['App\UserTypes\SmartDependentIBlockField', 'GetUserTypeDescription']
);

// Пользовательские типы для UF полей
$eventManager->AddEventHandler('main','OnUserTypeBuildList',
    ['App\UserTypes\SmartDependentUfField', 'GetUserTypeDescription']
);

