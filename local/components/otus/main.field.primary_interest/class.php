<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use App\Models\Lists\MarketingActivitiesPropertyValuesTable;
use App\UserTypes\PrimaryInterestUfField;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Component\BaseUfComponent;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;

class PrimaryInterestUfComponent extends BaseUfComponent implements Controllerable, Errorable
{
    use ErrorableImplementation;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->errorCollection = new ErrorCollection();
    }

    protected static function getUserTypeId(): string
    {
        return PrimaryInterestUfField::USER_TYPE_ID;
    }

    public function configureActions(): array
    {
        return [
            'getMarketingActivities' => ['prefilters' => []],

            // autocomplete search
            'searchCompanies'        => ['prefilters' => []],
            'searchUsers'            => ['prefilters' => []],

            // resolve title/fio by id (for edit init)
            'getCompanyTitle'        => ['prefilters' => []],
            'getUserFio'             => ['prefilters' => []],
        ];
    }

    /**
     * Канал "Выставка" -> элементы инфоблока marketingActivities.
     */
    public function getMarketingActivitiesAction(): array
    {
        if (!Loader::includeModule('iblock')) {
            $this->errorCollection->add([new Error('Ошибка загрузки модуля "Инфоблоки".')]);
            return [];
        }

        try {
            $iblockId = MarketingActivitiesPropertyValuesTable::getIblockId();
        } catch (\Throwable $e) {
            $this->errorCollection->add([new Error('Инфоблок marketingActivities недоступен: ' . $e->getMessage())]);
            return [];
        }

        $items = [];
        try {
            $res = ElementTable::getList([
                'select' => ['ID', 'NAME'],
                'filter' => ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y'],
                'order'  => ['NAME' => 'ASC'],
                'limit'  => 500,
            ]);

            while ($row = $res->fetch()) {
                $items[] = [
                    'id' => (int)$row['ID'],
                    'name' => (string)$row['NAME'],
                ];
            }
        } catch (\Throwable $e) {
            $this->errorCollection->add([new Error('Ошибка получения списка маркетинговых мероприятий: ' . $e->getMessage())]);
            return [];
        }

        return ['items' => $items];
    }

    /**
     * Канал "Рекомендация" -> компании CRM (поиск по названию).
     * Возвращаем: items: [{id, name}]
     */
    public function searchCompaniesAction(string $q = ''): array
    {
        $q = trim($q);

        if (!Loader::includeModule('crm')) {
            $this->errorCollection->add([new Error('Модуль CRM недоступен.')]);
            return [];
        }

        $filter = [];
        if ($q !== '') {
            $filter['%TITLE'] = $q;
        }

        $items = [];
        try {
            $res = \Bitrix\Crm\CompanyTable::getList([
                'select' => ['ID', 'TITLE'],
                'filter' => $filter,
                'order'  => ['TITLE' => 'ASC'],
                'limit'  => 50,
            ]);

            while ($row = $res->fetch()) {
                $items[] = [
                    'id' => (int)$row['ID'],
                    'name' => (string)$row['TITLE'],
                ];
            }
        } catch (\Throwable $e) {
            $this->errorCollection->add([new Error('Ошибка поиска компаний CRM: ' . $e->getMessage())]);
            return [];
        }

        return ['items' => $items];
    }

    /**
     * Канал "Сотрудник" -> пользователи (поиск).
     * Возвращаем: items: [{id, name}]
     */
    public function searchUsersAction(string $q = ''): array
    {
        $q = trim($q);

        if (!Loader::includeModule('main')) {
            $this->errorCollection->add([new Error('Модуль "main" недоступен.')]);
            return [];
        }

        $items = [];
        $seenIds = [];

        try {
            $filter = [
                'LOGIC' => 'AND',
                ['=ACTIVE' => 'Y'],
            ];

            if ($q !== '') {
                $filter[] = [
                    'LOGIC' => 'OR',
                    ['%NAME' => $q],
                    ['%LAST_NAME' => $q],
                    ['%SECOND_NAME' => $q],
                    ['%LOGIN' => $q],
                    ['%EMAIL' => $q],
                ];
            }

            $res = \Bitrix\Main\UserTable::getList([
                'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
                'filter' => $filter,
                'order'  => ['LAST_NAME' => 'ASC', 'NAME' => 'ASC'],
                'limit'  => 50,
            ]);

            while ($row = $res->fetch()) {
                $id = (int)($row['ID'] ?? 0);
                if ($id <= 0) continue;

                if (isset($seenIds[$id])) continue;
                $seenIds[$id] = true;

                $fio = trim(implode(' ', array_filter([
                    (string)($row['LAST_NAME'] ?? ''),
                    (string)($row['NAME'] ?? ''),
                    (string)($row['SECOND_NAME'] ?? ''),
                ])));

                if ($fio === '') {
                    $fio = (string)($row['LOGIN'] ?? '');
                }

                $items[] = ['id' => $id, 'name' => $fio];
            }
        } catch (\Throwable $e) {
            $this->errorCollection->add([new Error('Ошибка поиска сотрудников: ' . $e->getMessage())]);
            return [];
        }

        return ['items' => $items];
    }

    /**
     * Получить TITLE компании по ID (для восстановления отображаемого значения в input при загрузке edit-формы).
     * Возвращаем: {title: "..."}
     */
    public function getCompanyTitleAction($id): array
    {
        $id = (int)$id;
        if ($id <= 0) {
            $this->errorCollection->add([new Error('Некорректный идентификатор компании.')]);
            return [];
        }

        if (!Loader::includeModule('crm')) {
            $this->errorCollection->add([new Error('Модуль CRM недоступен.')]);
            return [];
        }

        try {
            $row = \Bitrix\Crm\CompanyTable::getRow([
                'select' => ['ID', 'TITLE'],
                'filter' => ['=ID' => $id],
            ]);
        } catch (\Throwable $e) {
            $this->errorCollection->add([new Error('Ошибка получения названия компании: ' . $e->getMessage())]);
            return [];
        }

        if (!$row) {
            $this->errorCollection->add([new Error('Компания не найдена.')]);
            return [];
        }

        return ['title' => (string)$row['TITLE']];
    }

    /**
     * Получить ФИО пользователя по ID (для восстановления отображаемого значения в input при загрузке edit-формы).
     * Возвращаем: {title: "..."} (ключ title используем для унификации на фронте)
     */
    public function getUserFioAction($id): array
    {
        $id = (int)$id;
        if ($id <= 0) {
            $this->errorCollection->add([new Error('Некорректный идентификатор сотрудника.')]);
            return [];
        }

        if (!Loader::includeModule('main')) {
            $this->errorCollection->add([new Error('Модуль "main" недоступен.')]);
            return [];
        }

        try {
            $row = \Bitrix\Main\UserTable::getRow([
                'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
                'filter' => ['=ID' => $id],
            ]);
        } catch (\Throwable $e) {
            $this->errorCollection->add([new Error('Ошибка получения данных сотрудника: ' . $e->getMessage())]);
            return [];
        }

        if (!$row) {
            $this->errorCollection->add([new Error('Сотрудник не найден.')]);
            return [];
        }

        $fio = trim(implode(' ', array_filter([
            (string)($row['LAST_NAME'] ?? ''),
            (string)($row['NAME'] ?? ''),
            (string)($row['SECOND_NAME'] ?? ''),
        ])));

        if ($fio === '') {
            $fio = (string)($row['LOGIN'] ?? '');
        }

        return ['title' => $fio];
    }
}