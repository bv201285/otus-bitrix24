<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UserField\HtmlBuilder;

$htmlBuilder = $this->getComponent()->getHtmlBuilder();
?>
<input <?= $htmlBuilder->buildTagAttributes($arResult['fieldValues']['attrList']) ?>>