<?php

use App\UserTypes\SmartDependentUfField;


if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$val = (string)($arResult['additionalParameters']['VALUE'] ?? '');
print ($val !== '' ? SmartDependentUfField::renderReadable($val) : '&nbsp;');