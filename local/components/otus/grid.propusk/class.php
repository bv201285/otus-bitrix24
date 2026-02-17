<?php

use App\Models\Lists\BuildingsPropertyValuesTable;
use App\Models\Lists\DoctorsPropertyValuesTable;
use App\Models\Orm\PropuskTable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\Main\Error;

class PropuskGrid extends CBitrixComponent implements Controllerable, Errorable {

    use ErrorableImplementation;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->errorCollection = new ErrorCollection();
    }

    protected const GRID_ID = 'PROPUSK_GRID';

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
            'addPropusk' => []
        ];
    }

    public function listKeysSignedParameters(): array
    {
        return [
            'GRID_ID',
            'PREFIX'
        ];
    }

    public function addPropuskAction(array $data)
    {
        try {
            $propuskTitle = $this->arParams['PREFIX'] . ' ' . (string)$data['TITLE'];
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

    public function deleteItemsAction(array $ids): array
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

    public function onPrepareComponentParams($arParams): mixed
    {
        // тут пишем логику обработки параметров, дополнение параметрами по умолчанию
        // и прочие нужные вещи
        return $arParams;
    }

    public function executeComponent(): void
    {
        $gridOptions = new \Bitrix\Main\Grid\Options(self::GRID_ID);

        // Названия колонок грида
        $this->arResult['COLUMNS'] = $this->prepareColumns();

        // Вывод в EXCEL
        if ($this->request->get('EXPORT_MODE') == 'Y') {
            $this->setTemplateName('excel');
        }

        // Выгрузка в EXCEL только по тем колонкам, которые выбраны в данный момент
        $this->arResult['USED_COLUMNS'] = $gridOptions->getUsedColumns($this->arResult['COLUMNS']);

        // Параметр компонента "показывать checkbox"
        if($this->arParams['SHOW_CHECKBOXES'] == 'Y'){
            $this->arResult['SHOW_ROW_CHECKBOXES'] = true;
        }else{
            $this->arResult['SHOW_ROW_CHECKBOXES'] = false;
        }

        // Идентификатор для самого грида и фильтра
        $this->arResult['GRID_ID'] = self::GRID_ID;

        // Дополнительные кнопки управления в Toolbar
        $this->arResult['BUTTONS'] = $this->getButtons();

        // Поля фильтра
        $this->arResult['FILTER_FIELDS'] = $this->prepareFilterFields();

        // Параметры сортировки грида
        $sort = $gridOptions->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
        $this->arResult['SORT'] = $sort['sort'];

        // Работа с пагинацией
        $nav = new \Bitrix\Main\UI\PageNavigation(self::GRID_ID);
        $nav->allowAllRecords(true)
            ->setPageSize($gridOptions->GetNavParams()['nPageSize'])
            ->initFromUri();

        // Выборка данных
        $filterOptions = new \Bitrix\Main\UI\Filter\Options(self::GRID_ID);
        $gridFilter = $filterOptions->getFilter();
        $ormFilter = $this->prepareOrmFilter($gridFilter);

        // Выборка данных через getList
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

        // Выборка данных через query
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

        // Формирование строк (Rows) для Grid
        $this->arResult['ROWS'] = $this->prepareRowsGrid($resultQuery);

        // Кнопки групповых дейтсвий
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
        $this->arResult['ACTION_PANEL']  =[
            'GROUPS' => [
                'TYPE' => [
                    'ITEMS' => [
                        [
                            'ID' => 'DELETE_ITEM',
                            'TYPE' => 'BUTTON',
                            'TEXT' => 'Удалить',
                            'CLASS' => 'ui-btn ui-btn-danger-light',
                            //'ONCHANGE' => $arResult['ACTION_DELETE'],
                            'ONCHANGE' => $actionDelete -> toArray(),
                        ],
                    ],
                ]
            ],
        ];
        $this->includeComponentTemplate();

    }

    protected function getButtons(): array
    {
        $btnDropdown = new Button([
            'text' => 'Действия',
            'color' => Color::SUCCESS,
            'dropdown' => true,
            'menu' => [
                'items' => [
                    [
                        'text' => 'Добавить пропуск (ajax.php)',
                        'onclick' => new JsCode('PropuskGridHandler.addPropusk(true)'),
                    ],
                    [
                        'text' => 'Добавить пропуск 2 (ajax.php)',
                        'onclick' => new JsCode('PropuskGridHandler.addPropusk2(true)'),
                    ],
                    [
                        'text' => 'Добавить пропуск (class.php)',
                        'onclick' => new JsCode('PropuskGridHandler.addPropusk(false)'),
                    ],
                    [
                        'text' => 'Редактировать',
                        'disabled' => true
                    ],
                    ['delimiter' => true],
                    [
                        'text' => 'Уничтожить',
                        'onclick' => new JsCode('PropuskGridHandler.addBook()'),
                    ],
                ],
            ]
        ]);

        return [
            [
                'onclick' => new \Bitrix\UI\Buttons\JsCode('PropuskGridHandler.redirectToExcel()'),
                'text' => Loc::getMessage('EXPORT_XLSX_BUTTON_TITLE'),
                'color' => Color::PRIMARY,
            ],
            $btnDropdown,
        ];
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

    private function prepareFilterFields(): array
    {
        return [
            [
                'id'      => 'ID',
                'name'    => 'ID',
                'type'    => 'number',
                'default' => true, // Поле будет отображаться в фильтре сразу по умолчанию
            ],
            [
                'id'      => 'TITLE',
                'name'    => 'Наименование',
                'type'    => 'text',
                'default' => true,
            ],
            [
                'id'      => 'VALIDITY_PERIOD',
                'name'    => 'Действует до',
                'type'    => 'date',
                'default' => true,
            ],
            [
                'id'      => 'DOCTOR_ID',
                'name'    => 'Врач',
                'type'    => 'list',
                'items'   => $this->getDoctorsList(),
                'params'  => ['multiple' => 'Y'],
                'default' => true,
            ],
            [
                'id'      => 'BUILDINGS_ID',
                'name'    => 'Здание',
                'type'    => 'list',
                'items'   => $this->getBuildingsList(),
                'params'  => ['multiple' => 'Y'],
                'default' => true,
            ],
        ];
    }

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

        // Для дальнейшего выбора в выпадающем спсиске модалок
        $this->arResult['DOCTORS'] = $doctors;

        return $doctors;
    }

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

        // Для дальнейшего выбора в выпадающем спсиске модалок
        $this->arResult['BUILDINGS'] = $buildings;

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
            if (str_contains($key, '_from')) {
                $originalField = str_replace('_from', '', $key);
                if (in_array($originalField, $allowedFields)) {
                    $ormFilter['>=' . $originalField] = $value;
                }
                continue;
            }

            if (str_contains($key, '_to')) {
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


}