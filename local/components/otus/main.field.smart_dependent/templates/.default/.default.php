<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Фолбек-шаблон. BaseUfComponent может сюда проваливаться.
 * Просто выведем значение(я).
 */
$isFirst = true;
foreach ($arResult['value'] as $value)
{
    if (!$isFirst) { echo '<br>'; }
    $isFirst = false;
    echo htmlspecialcharsbx((string)$value);
}