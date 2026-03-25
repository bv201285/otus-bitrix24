<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var PrimaryInterestUfComponent $component */
$component = $this->getComponent();
?>
<span class="field-wrap primary-interest-wrap">
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