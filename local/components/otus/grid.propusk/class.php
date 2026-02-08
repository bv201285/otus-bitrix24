<?php

use App\Models\Lists\BuildingsPropertyValuesTable;
use App\Models\Lists\DoctorsPropertyValuesTable;
use App\Models\Orm\PropuskTable;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\SystemException;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class GridPropuskComponent extends CBitrixComponent implements Controllerable {
    private $_request;

    // 1. Описываем конфиг (какие фильтры применять к запросу)
    public function configureActions(): array
    {
        return [
            'deleteItems' => [ // Это название экшена
                'prefilters' => [
                    //new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function deleteItemsAction(array $ids)
    {
        foreach ($ids as $id) {
            // Ваша логика удаления
            PropuskTable::delete((int)$id);
        }

        return [
            'status' => 'success',
            'message' => 'Всё удалено'
        ];
    }


    /**
     * Проверка наличия модулей требуемых для работы компонента
     * @return bool
     * @throws Exception
     */
    private function checkModules(): bool
    {
        /*if (   !Loader::includeModule('iblock')
            || !Loader::includeModule('sale')
        ) {
            throw new \Exception('Не загружены модули необходимые для работы модуля');
        }*/

        return true;
    }

    /**
     * @throws SystemException
     */
    private function checkRequiredParams(): void
    {
        if (!$this->arParams['GRID_ID']) {
            // Генерируем исключение, если параметр пустой
            throw new SystemException("Ошибка: Параметр 'ID таблицы' является обязательным.");
        }
    }

    private function prepareColumns() : array
    {
        $fieldMap = PropuskTable::getMap();
        $columns = [];

        // Список полей, которые мы НЕ хотим видеть в Grid
        $excludedFields = ['DOCTOR_ID', 'BUILDINGS_ID'];

        foreach ($fieldMap as $field) {
            $fieldName = $field->getName();

            // 1. Пропускаем исключенные поля
            if (in_array($fieldName, $excludedFields)) {
                continue;
            }

            // 2. Обрабатываем обычные поля (ID, TITLE, VALIDITY_PERIOD)
            if ($field instanceof ScalarField) {
                $columns[] = [
                    'id'      => $fieldName,
                    'name'    => $field->getTitle(),
                    'sort'    => $fieldName,
                    'default' => true
                ];
            }
            // 3. Обрабатываем поле связи DOCTOR
            elseif ($fieldName === 'DOCTOR') {
                $columns[] = [
                    'id'      => 'DOCTOR_NAME', // Даем алиас для данных из связанной таблицы
                    'name'    => $field->getTitle(),
                    'sort'    => 'DOCTOR_NAME', // Сортировать будем по ID врача в основной таблице
                    'default' => true
                ];
            }
            // 4. Добавляем поле BUILDINGS_NAME значение которого будет получать из query registerRuntimeField
            $columns[] = [
                'id'      => 'BUILDINGS_NAME', // Даем алиас для данных из связанной таблицы
                'name'    => 'Здание',
                'sort'    => 'BUILDINGS_NAME', // Сортировать будем по ID здания в основной таблице
                'default' => true
            ];
        }
        return $columns;
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    private function prepareFilterFields(): array
    {
        return [
            // Поле типа Число (будет содержать варианты "равно", "больше", "меньше", "диапазон")
            [
                'id'      => 'ID',
                'name'    => 'ID',
                'type'    => 'number',
                'default' => true, // Поле будет отображаться в фильтре сразу по умолчанию
            ],
            // Поле типа Строка
            [
                'id'      => 'TITLE',
                'name'    => 'Наименование',
                'type'    => 'text',
                'default' => true,
            ],
            // Поле типа Дата (автоматически добавит календарь и диапазоны "Вчера", "Сегодня", "Месяц")
            [
                'id'      => 'VALIDITY_PERIOD',
                'name'    => 'Действует до',
                'type'    => 'date',
                'default' => true,
            ],
            // Поле типа Список (Dropdown)
            [
                'id'      => 'DOCTOR_ID',
                'name'    => 'Врач',
                'type'    => 'list',
                'items'   => $this->getDoctorsList(), // Динамически получаем список врачей
                'params'  => ['multiple' => 'Y'], // Разрешаем выбор нескольких врачей
                'default' => true,
            ],
            // Поле типа Список (Dropdown)
            [
                'id'      => 'BUILDINGS_ID',
                'name'    => 'Здание',
                'type'    => 'list',
                'items'   => $this->getBuildingsList(), // Динамически получаем список врачей
                'params'  => ['multiple' => 'Y'], // Разрешаем выбор нескольких врачей
                'default' => true,
            ],
        ];
    }

    /**
     * Вспомогательный метод для получения списка врачей в фильтр
     */
    private function getDoctorsList(): array
    {
        $doctors = [];
        // Здесь формат: ['ID записи' => 'Текст для отображения']

        // Пример получения через ORM:
        $res = DoctorsPropertyValuesTable::getList(['select' => ['IBLOCK_ELEMENT_ID', 'NAME' => 'ELEMENT.NAME']]);
        while ($row = $res->fetch()) {
            //dump($row);
            $doctors[$row['IBLOCK_ELEMENT_ID']] = $row['NAME'];
        }

        return $doctors;
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getBuildingsList(): array
    {
        $buildings = [];
        // Здесь формат: ['ID записи' => 'Текст для отображения']

        // Пример получения через ORM:
        $res = BuildingsPropertyValuesTable::getList(['select' => ['IBLOCK_ELEMENT_ID', 'NAME' => 'ELEMENT.NAME']]);
        while ($row = $res->fetch()) {
            //dump($row);
            $buildings[$row['IBLOCK_ELEMENT_ID']] = $row['NAME'];
        }

        return $buildings;
    }

    private function prepareOrmFilter(array $rawFilter): array
    {
        $ormFilter = [];

        // Список разрешенных полей, чтобы в запрос не попал системный мусор
        $allowedFields = ['ID', 'TITLE', 'VALIDITY_PERIOD', 'DOCTOR_ID', 'BUILDINGS_ID'];

        foreach ($rawFilter as $key => $value) {
            // 1. Обработка глобального текстового поиска (строка "Поиск" в UI)
            if ($key === 'FIND' && !empty($value)) {
                // Используем логику "ИЛИ"
                $ormFilter[] = [
                    'LOGIC' => 'OR',
                    ['%TITLE' => $value],
                    ['ID' => $value],
                    ['%DOCTOR_NAME' => $value],
                    ['%BUILDINGS_NAME' => $value]
                ];
                continue;
            }

            // 2. Игнорируем системные поля UI-фильтра
            if (in_array($key, ['FILTER_ID', 'PRESET_ID', 'FILTER_APPLIED', 'FIND'])) {
                continue;
            }

            // 3. Обработка диапазонов (Числа и Даты)
            // Битрикс добавляет суффиксы _from и _to для полей типа 'number' и 'date'
            if (strpos($key, '_from') !== false) {
                $originalField = str_replace('_from', '', $key);
                if (in_array($originalField, $allowedFields)) {
                    $ormFilter['>=' . $originalField] = $value;
                }
                continue;
            }

            if (strpos($key, '_to') !== false) {
                $originalField = str_replace('_to', '', $key);
                if (in_array($originalField, $allowedFields)) {
                    // Если это дата, для корректного поиска до конца дня добавляем время
                    if ($originalField === 'VALIDITY_PERIOD' && !empty($value)) {
                        $ormFilter['<=' . $originalField] = $value . ' 23:59:59';
                    } else {
                        $ormFilter['<=' . $originalField] = $value;
                    }
                }
                continue;
            }

            // 4. Обработка точных совпадений и списков
            if (in_array($key, $allowedFields)) {
                // Если поле TITLE ищем через UI как строку (без подстроки),
                // можно добавить % для поиска по части слова
                if ($key === 'TITLE') {
                    $ormFilter['%' . $key] = $value;
                } else {
                    // ID, DOCTOR_ID (в т.ч. массивы, если выбор множественный)
                    $ormFilter[$key] = $value;
                }
            }
        }

        return $ormFilter;
    }

    private function prepareRowsGrid($resultQuery): array{
        $arRows = [];
        foreach ($resultQuery->fetchAll() as $row) {
            $arRows[] = [
                'data'    => $row, // Сами данные
                'actions' => [ // Кнопки действий (Меню)
                    [
                        'text'    => 'Удалить',
                        'onclick' => "PropuskGridHandler.removeOne(" . (int)$row['ID'] . ")"
                    ],
                ]
            ];
        }
        return $arRows;
    }

    /**
     * Подготовка параметров компонента
     * @param $arParams
     * @return mixed
     */
    public function onPrepareComponentParams($arParams): mixed
    {
        // тут пишем логику обработки параметров, дополнение параметрами по умолчанию
        // и прочие нужные вещи
        return $arParams;
    }

    /**
     * Точка входа в компонент
     * Должна содержать только последовательность вызовов вспомогательых ф-ий и минимум логики
     * всю логику стараемся разносить по классам и методам
     */
    public function executeComponent(): void
    {

        try {
            //dd($this->_request);

            $this->_request = Application::getInstance()->getContext()->getRequest();

            $this->checkRequiredParams();
            //$this->checkModules();

            $this->arResult['GRID_ID'] = $this->arParams['GRID_ID'];

            if($this->arParams['SHOW_CHECKBOXES'] == 'Y'){
                $this->arResult['SHOW_ROW_CHECKBOXES'] = true;
            }else{
                $this->arResult['SHOW_ROW_CHECKBOXES'] = false;
            }

            // 1. Формируем колонки
            $this->arResult['COLUMNS'] = $this->prepareColumns();

            // 2. Работа с фильтром
            $this->arResult['FILTER_FIELDS'] = $this->prepareFilterFields();
            $filterOptions = new \Bitrix\Main\UI\Filter\Options($this->arResult['GRID_ID']);
            $gridFilter = $filterOptions->getFilter();
            // Преобразуем фильтр из UI-формата в формат для ORM getList
            $ormFilter = $this->prepareOrmFilter($gridFilter);

            // 3. Работа с сортировкой
            $gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID']);
            $sort = $gridOptions->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
            $this->arResult['SORT'] = $sort['sort'];

            // 4. Работа с пагинацией (PageNavigation)
            $nav = new \Bitrix\Main\UI\PageNavigation('nav-params');
            $nav->allowAllRecords(true)
                ->setPageSize($gridOptions->GetNavParams()['nPageSize'])
                ->initFromUri();

            // 5. Выборка данных через ORM
            /*$query = PropuskTable::getList([
                'select'      => ['ID', 'TITLE', 'VALIDITY_PERIOD', 'DOCTOR_NAME' => 'DOCTOR.ELEMENT.NAME'],
                'filter'      => $ormFilter,
                'order'       => $this->arResult['SORT'],
                'offset'      => $nav->getOffset(),
                'limit'       => $nav->getLimit(),
                'count_total' => true,
            ]);
            $nav->setRecordCount($query->getCount());
            $this->arResult['ROWS'] = $this->prepareRowsGrid($query);*/

            $query = PropuskTable::query()
                ->setSelect([
                    'ID',
                    'TITLE',
                    'VALIDITY_PERIOD',
                    'DOCTOR_NAME' => 'DOCTOR.ELEMENT.NAME',
                    'BUILDINGS_NAME' => 'BUILDINGS.ELEMENT.NAME',
                ])
                ->registerRuntimeField(
                    null,
                    new ReferenceField(
                        'BUILDINGS',
                        BuildingsPropertyValuesTable::getEntity(),
                        ['=this.BUILDINGS_ID' => 'ref.IBLOCK_ELEMENT_ID']
                    )
                )
                ->setFilter($ormFilter)
                ->setOrder($this->arResult['SORT'])
                ->setOffset($nav->getOffset())
                ->setLimit($nav->getLimit())
                ->countTotal(true);

            $resultQuery = $query->exec();

            $nav->setRecordCount($resultQuery->getCount());

            $this->arResult['NAV_OBJECT'] = $nav;
            $this->arResult['TOTAL_ROWS_COUNT'] = $resultQuery->getCount();

            // 6. Формирование строк (Rows) для Grid
            $this->arResult['ROWS'] = $this->prepareRowsGrid($resultQuery);

            // 7. Кнопка Action Сообщить


            $actionDelete = new Onchange();
            $actionDelete->addAction(
                [
                    'ACTION' => Actions::CALLBACK,
                    'CONFIRM' => true,
                    'CONFIRM_APPLY_BUTTON'  => 'Подтвердить',
                    'DATA' => [
                        //Будет инициализирован в template.php
                        ['JS' => 'PropuskGridHandler.removeSelected()']
                        // Пример: просто перезагрузить грид
                        //['JS' => "BX.Main.gridManager.getById('".$this->arResult['GRID_ID']."').instance.reload();"]

                    ]
                ]
            );
            $this->arResult['ACTION_DELETE'] =  $actionDelete->toArray();


            //dump($sort);



            $this->includeComponentTemplate();
        }
        catch (SystemException $e) {
            ShowError($e->getMessage());
        }








    }
}