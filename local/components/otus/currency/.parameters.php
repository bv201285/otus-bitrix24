<?php
use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('currency')) return;

$currencies = [];

$currencyIterator = CurrencyTable::getList([
    'select' => ['CURRENCY'],
    'order' => ['SORT' => 'ASC']
]);

while ($currency = $currencyIterator->fetch()) {
    $currencies[$currency['CURRENCY']] = $currency['CURRENCY'];
}

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => [
            'NAME' => Loc::getMessage('CURRENCY_PARAMETERS'),
            'SORT' => "300"
        ]
    ],
    'PARAMETERS' => array(
        'CURRENCY' => array(
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('CHOOSE_CURRENCY'),
            'TYPE' => 'LIST',
            'VALUES' => $currencies,
            "DEFAULT" => 'USD',
        )
    ),
];



