<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use App\UserTypes\DealDoctorsProceduresTableUfField;
use Bitrix\Main\Component\BaseUfComponent;

class DealDoctorsProceduresTableUfComponent extends BaseUfComponent
{
    protected static function getUserTypeId(): string
    {
        return DealDoctorsProceduresTableUfField::USER_TYPE_ID;
    }
}