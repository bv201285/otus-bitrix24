<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Currency\CurrencyRateTable;
use Bitrix\Main\Type\Date;

class CurrencyRateComponent extends \CBitrixComponent
{
    public function executeComponent()
    {

        if (!Loader::includeModule('currency')) {
            return;
        }

        // 1. Находим базовую валюту через ORM
        $baseCurrency = CurrencyTable::getList([
            'select' => ['CURRENCY'],
            'filter' => ['=BASE' => 'Y'],
            'limit' => 1
        ])->fetch()['CURRENCY'] ?: 'RUB';

        $targetCurrency = $this->arParams['CURRENCY'];

        // 2. Получаем самый свежий курс через ORM
        // Сортируем по дате убывания и берем 1 запись
        $rateData = CurrencyRateTable::getList([
            'select' => ['RATE', 'RATE_CNT', 'DATE_RATE'],
            'filter' => [
                '=CURRENCY' => $targetCurrency,
                '<=DATE_RATE' => new Date() // Не позже сегодняшнего дня
            ],
            'order' => ['DATE_RATE' => 'DESC'],
            'limit' => 1
        ])->fetch();

        if ($rateData) {
            $finalRate = (float)$rateData['RATE'] / (int)$rateData['RATE_CNT'];

            $this->arResult['RATE'] = $finalRate;
            $this->arResult['BASE_CURRENCY'] = $baseCurrency;
            $this->arResult['TARGET_CURRENCY'] = $targetCurrency;
            $this->arResult['DATE'] = $rateData['DATE_RATE']->toString();
        } else {
            $this->arResult['ERROR'] = "Курс не найден";
        }

        $this->includeComponentTemplate();
    }

}