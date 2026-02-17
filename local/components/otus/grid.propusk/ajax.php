<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Error;
use Bitrix\Main\Engine\Controller;
use App\Models\Orm\PropuskTable;

class PropuskGridAjaxController extends Controller
{
    public function configureActions(): array
    {
        return [
            'deleteElement' => [
                'prefilters' => [],
            ],
            'addPropusk' => [
                'prefilters' => [],
                'postfilters' => [],
            ],
        ];
    }

    public function addPropuskAction(array $data): array
    {
        try {
            $propuskTitle = (string)$data['TITLE'];
            $doctorID = (int)$data['DOCTOR_ID'];
            $buildingsID = (int)$data['BUILDINGS_ID'];

            if ($propuskTitle === '') {
                $this->errorCollection->add([new Error('Не передано наименование')]);
                return [];
            }

            if ($doctorID <= 0) {
                $this->errorCollection->add([new Error('Не передан доктор')]);
                return [];
            }

            if ($buildingsID <= 0) {
                $this->errorCollection->add([new Error('Не передано здание')]);
                return [];
            }

            $addResult = PropuskTable::add([
                'TITLE' => $propuskTitle,
                'DOCTOR_ID' => $doctorID,
                'BUILDINGS_ID' => $buildingsID,
            ]);

            if (!$addResult->isSuccess()) {
                $errors = [];
                foreach ($addResult->getErrorMessages() as $msg) {
                    $errors[] = new Error($msg);
                }
                $this->errorCollection->add($errors);
                return [];
            }

            return ['PROPUSK_ID' => $addResult->getId()];
        }
        catch (\Throwable $e) {
            $this->errorCollection->add([new Error($e->getMessage())]);
            return [];
        }
    }

}
