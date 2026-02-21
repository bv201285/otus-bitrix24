<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use App\Models\Lists\BookingPropertyValuesTable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;

class BookingProcPopupComponent extends CBitrixComponent implements Controllerable, Errorable
{
    use ErrorableImplementation;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->errorCollection = new ErrorCollection();
    }

    public function configureActions(): array
    {
        return [
            'getForm' => [
                'prefilters' => [],
            ],
            'createBooking' => [
                'prefilters' => [],
            ],
        ];
    }

    /**
     * Ajax action: возвращает HTML формы для BX.PopupWindow
     */
    public function getFormAction(int $doctorId, int $procedureId): array
    {
        if ($doctorId <= 0 || $procedureId <= 0) {
            $this->errorCollection->add([new Error('Некорректные данные')]);
            return [];
        }

        // При желании подтянем имена (без heavy-логики)
        $doctorName = $this->getElementName($doctorId);
        $procedureName = $this->getElementName($procedureId);

        // Передадим в шаблон
        $this->arResult = [
            'DOCTOR_ID' => $doctorId,
            'PROCEDURE_ID' => $procedureId,
            'DOCTOR_NAME' => $doctorName,
            'PROCEDURE_NAME' => $procedureName,
        ];

        ob_start();
        $this->includeComponentTemplate();
        $html = ob_get_clean();

        return [
            'html' => $html,
            'doctorName' => $doctorName,
            'procedureName' => $procedureName,
        ];
    }

    /**
     * Ajax action: создает бронирование процедуры в ИБ Бронирование
     */
    public function createBookingAction(int $doctorId, int $procedureId, string $date, string $clientName): array
    {

        if (!Loader::includeModule('iblock')) {
            $this->errorCollection->add([new Error('Ошибка загрузки модуля битрикс Инф блок.')]);
            return [];
        }

        $clientName = trim($clientName);

        if ($doctorId <= 0 || $procedureId <= 0) {
            $this->errorCollection->add([new Error('Не выбран врач или процедура')]);
            return [];
        }
        if ($clientName === '') {
            $this->errorCollection->add([new Error('Введите имя клиента')]);
            return [];
        }
        if ($date === '') {
            $this->errorCollection->add([new Error('Выберите дату и время')]);
            return [];
        }

        $dateString = $this->normalizeBxCalendarDateToString($date);
        if (!$dateString) {
            $this->errorCollection->add([new Error('Некорректная дата. Ожидается формат ДД.ММ.ГГГГ ЧЧ:ММ:СС')]);
            return [];
        }

        // Проверка занятости
        if ($this->isDoctorSlotBusy($doctorId, $dateString)) {
            $this->errorCollection->add([new Error('Это время уже занято. Выберите другое.')]);
            return [];
        }

        $ok = BookingPropertyValuesTable::add([
            'NAME' => $clientName . ' / ' . $dateString,
            'DOCTOR' => $doctorId,
            'PROCEDURE' => $procedureId,
            'DATE' => $dateString,
            'CLIENT_NAME' => $clientName,
        ]);

        if (!$ok) {
            $this->errorCollection->add([new Error('Не удалось создать бронирование')]);
            return [];
        }

        return ['success' => true];
    }

    private function getElementName(int $id): string
    {
        Loader::includeModule('iblock');

        $row = ElementTable::getRow([
            'select' => ['ID','NAME'],
            'filter' => ['=ID' => $id],
        ]);
        return $row['NAME'] ?? '';
    }

    /**
     * Приводит строку даты из BX.calendar к формату "d.m.Y H:i:s"
     * Возвращает null, если строка не распознана.
     */
    private function normalizeBxCalendarDateToString(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        // основной формат (как у вас): "25.03.2026 15:49:00"
        if (preg_match('~^\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}:\d{2}$~', $date)) {
            return $date;
        }

        // без секунд: "25.03.2026 15:49" -> добавим секунды
        if (preg_match('~^\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}$~', $date)) {
            return $date . ':00';
        }

        // только дата: "25.03.2026" -> добавим время
        if (preg_match('~^\d{2}\.\d{2}\.\d{4}$~', $date)) {
            return $date . ' 00:00:00';
        }

        return null;
    }

    private function isDoctorSlotBusy(int $doctorId, string $dateString): bool
    {
        $bookingIblockId = BookingPropertyValuesTable::getIblockId();

        dd($doctorId, $dateString, $bookingIblockId);

        // dateString у вас в формате d.m.Y H:i:s
        $dt = DateTime::createFromFormat('d.m.Y H:i:s', $dateString);
        if (!$dt) {
            return false;
        }

        // ВНИМАНИЕ: в фильтрах для DateTime лучше использовать формат БД
        $from = $dt->format('Y-m-d H:i:s');
        $to   = $dt->format('Y-m-d H:i:s');

        $res = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $bookingIblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_DOCTOR' => $doctorId,
                '>=PROPERTY_DATE' => $from,
                '<=PROPERTY_DATE' => $to,
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        );

        return (bool)$res->Fetch();
    }
}