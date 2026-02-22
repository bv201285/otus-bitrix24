<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use App\UserTypes\DealDoctorsProceduresTableUfField;

$val = (string)($arResult['additionalParameters']['VALUE'] ?? '');
print ($val !== '' ? DealDoctorsProceduresTableUfField::renderReadable($val) : '&nbsp;');