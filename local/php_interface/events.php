<?php

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\EventManager;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

$eventManager = EventManager::getInstance();

// OnBeforeProlog
$eventManager->addEventHandler('main', 'OnBeforeProlog', static function () {
    // Этот js выводит в консоль все custom-event которые срабатывают
    //Asset::getInstance()->addJs('/local/js/find-on-custom-event/find-on-custom-event.js');

    // Подключить slider-helper для открытия определнных страниц сразу в слайдере
    Extension::load(['otus.sliderHelper',]);
});

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
$eventManager->AddEventHandler('main','OnUserTypeBuildList',
    ['App\UserTypes\DealDoctorsProceduresTableUfField', 'GetUserTypeDescription']
);
$eventManager->AddEventHandler('main','OnUserTypeBuildList',
    ['App\UserTypes\PrimaryInterestUfField', 'GetUserTypeDescription']
);



