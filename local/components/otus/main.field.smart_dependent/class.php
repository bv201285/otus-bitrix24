<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use App\UserTypes\SmartDependentUfField;
use Bitrix\Main\Component\BaseUfComponent;

class SmartDependentCompoundUfComponent extends BaseUfComponent
{
    protected static function getUserTypeId(): string
    {
        return SmartDependentUfField::USER_TYPE_ID;
    }
}