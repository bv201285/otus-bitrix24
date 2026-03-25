<?php

use App\UserTypes\PrimaryInterestUfField;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$val = (string)($arResult['additionalParameters']['VALUE'] ?? '');
print ($val !== '' ? PrimaryInterestUfField::renderReadable($val) : '&nbsp;');