<?php

namespace App\IBlock\EventHandlers;

class IBlockHandler
{
    public static function OnBeforeIBlockUpdate(&$arFields): bool
    {
        \App\Debug\Log::addLog($arFields);
        global $APPLICATION;
        $APPLICATION->throwException("!!!");
        return false;
    }
}