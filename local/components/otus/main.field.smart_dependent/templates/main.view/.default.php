<?php
use App\UserTypes\SmartDependentUfField;
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Asset::getInstance()->addCss('/local/assets/css/smart-dependent/smart-dependent.css');
?>
<div class="sdc-view">
    <?php foreach ($arResult['value'] as $value): ?>
        <div class="sdc-view__item">
            <?= SmartDependentUfField::renderReadable((string)$value); ?>
        </div>
    <?php endforeach; ?>
</div>