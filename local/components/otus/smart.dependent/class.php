<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\ElementTable;

class SmartDependentComponent extends CBitrixComponent implements Controllerable
{
    public function configureActions(): array
    {
        return [
            'getElements' => ['prefilters' => []],
        ];
    }

    public function getElementsAction(string $iblockApi): array
    {
        $iblockApi = trim($iblockApi);
        if ($iblockApi === '') {
            $this->addError(new Error('Empty iblockApi'));
            return [];
        }

        if (!Loader::includeModule('iblock')) {
            $this->addError(new Error('Module iblock not available'));
            return [];
        }

        // Белый список — обязательно
        $allowed = ['Doctors' => true, 'Procedure' => true];
        if (!isset($allowed[$iblockApi])) {
            $this->addError(new Error('Iblock not allowed'));
            return [];
        }

        $iblockRow = IblockTable::getRow([
            'select' => ['ID'],
            'filter' => ['=API_CODE' => $iblockApi],
        ]);
        if (!$iblockRow) {
            $this->addError(new Error('Iblock not found'));
            return [];
        }

        $iblockId = (int)$iblockRow['ID'];

        $items = [];
        $res = ElementTable::getList([
            'select' => ['ID','NAME'],
            'filter' => ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y'],
            'order' => ['NAME' => 'ASC'],
            'limit' => 500,
        ]);

        while ($row = $res->fetch()) {
            $items[] = ['id' => (int)$row['ID'], 'name' => $row['NAME']];
        }

        return ['items' => $items];
    }

    public function executeComponent() {}
}