<?php
use App\UserTypes\PrimaryInterestUfField;
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Asset::getInstance()->addCss('/local/assets/css/primary-interest/primary-interest.css');
?>
<div class="pi-view">
    <?php foreach ($arResult['value'] as $value): ?>
        <div class="pi-view__item">
            <?= PrimaryInterestUfField::renderReadable((string)$value); ?>
        </div>
    <?php endforeach; ?>
</div>