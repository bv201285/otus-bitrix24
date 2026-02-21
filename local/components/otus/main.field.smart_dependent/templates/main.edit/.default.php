<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var SmartDependentCompoundUfComponent $component */
$component = $this->getComponent();
?>
<span class="field-wrap smart-dependent-wrap">
  <?php foreach($arResult['fieldValues'] as $value): ?>
      <span class="field-item">
      <?= $value['html'] ?>
    </span>
  <?php endforeach; ?>

    <?php
    if (
            $arResult['userField']['MULTIPLE'] === 'Y'
            && $arResult['additionalParameters']['SHOW_BUTTON'] !== 'N'
    )
    {
        print $component->getHtmlBuilder()->getCloneButton($arResult['fieldName']);
    }
    ?>
</span>