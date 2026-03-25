<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use App\UserTypes\DealDoctorsProceduresTableUfField;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Component\BaseUfComponent;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;

class DealDoctorsProceduresTableUfComponent extends BaseUfComponent implements Controllerable, Errorable
{
    use ErrorableImplementation;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->errorCollection = new ErrorCollection();
    }

    protected static function getUserTypeId(): string
    {
        return DealDoctorsProceduresTableUfField::USER_TYPE_ID;
    }

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
            $this->errorCollection->add([new Error('Empty iblockApi')]);
            return [];
        }

        if (!Loader::includeModule('iblock')) {
            $this->errorCollection->add([new Error('Module iblock not available')]);
            return [];
        }

        $allowed = ['Doctors' => true, 'Procedure' => true];
        if (!isset($allowed[$iblockApi])) {
            $this->errorCollection->add([new Error('Iblock not allowed')]);
            return [];
        }

        $iblockRow = IblockTable::getRow([
            'select' => ['ID'],
            'filter' => ['=API_CODE' => $iblockApi],
        ]);
        if (!$iblockRow) {
            $this->errorCollection->add([new Error('Iblock not found')]);
            return [];
        }

        $iblockId = (int)$iblockRow['ID'];

        $items = [];
        $res = ElementTable::getList([
            'select' => ['ID','NAME'],
            'filter' => ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y'],
            'order' => ['NAME' => 'ASC'],
            'limit' => 1000,
        ]);

        while ($row = $res->fetch()) {
            $items[] = ['id' => (int)$row['ID'], 'name' => (string)$row['NAME']];
        }

        return ['items' => $items];
    }
}