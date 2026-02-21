<?php

use App\UserTypes\SmartDependentUfField;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<span class="fields smart-dependent field-wrap">
  <?php foreach($arResult['value'] as $value): ?>
      <span class="fields smart-dependent field-item">
      <?= SmartDependentUfField::renderReadable((string)$value); ?>
    </span>
  <?php endforeach; ?>
</span>