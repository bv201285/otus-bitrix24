<?php

namespace App\Models;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

abstract class AbstractIblockPropertyValuesTable extends DataManager
{
    protected static string $apiCode = '';
    protected static ?array $properties = null;

    public static function getIblockId(): int
    {
        static $ids = [];
        $apiCode = static::$apiCode;
        if (empty($apiCode)) {
            throw new \Exception("API_CODE not defined in " . static::class);
        }

        if (!isset($ids[$apiCode])) {
            $entity = IblockTable::compileEntity($apiCode);
            $ids[$apiCode] = (int)$entity->getIblock()->getId();
        }

        return $ids[$apiCode];
    }

    public static function getTableName(): string
    {
        return 'b_iblock_element_prop_s' . static::getIblockId();
    }

    public static function getTableNameMulti(): string
    {
        return 'b_iblock_element_prop_m' . static::getIblockId();
    }

    /**
     * Возвращает имя класса для таблицы множественных значений
     */
    public static function getMultipleValuesTableClass(): string
    {
        $className = (new \ReflectionClass(static::class))->getShortName();
        $namespace = (new \ReflectionClass(static::class))->getNamespaceName();
        $multiClassName = str_replace('Table', 'MultipleTable', $className);

        return $namespace . '\\' . $multiClassName;
    }

    /**
     * Создает класс для таблицы _m на лету, если он не существует
     */
    protected static function initMultipleValuesTableClass(): void
    {
        $fullClassName = static::getMultipleValuesTableClass();
        if (class_exists($fullClassName)) {
            return;
        }

        // Получаем информацию о ТЕКУЩЕМ классе (который существует)
        $reflection = new \ReflectionClass(static::class);
        $namespace = $reflection->getNamespaceName();

        // Вычисляем имя будущего класса из полного имени (берем всё, что после последнего слэша)
        $className = substr($fullClassName, strrpos($fullClassName, '\\') + 1);

        $tableName = static::getTableNameMulti();
        $parentClass = '\\' . static::class;

        /** @lang PHP */
        $code = <<<PHP
namespace {$namespace};

class {$className} extends \Bitrix\Main\ORM\Data\DataManager 
{
    public static function getTableName() 
    { 
        return '{$tableName}'; 
    }

    public static function getMap() 
    {
        return [
            'ID' => new \Bitrix\Main\ORM\Fields\IntegerField('ID', [
                'primary' => true, 
                'autocomplete' => true
            ]),
            'IBLOCK_ELEMENT_ID' => new \Bitrix\Main\ORM\Fields\IntegerField('IBLOCK_ELEMENT_ID'),
            'IBLOCK_PROPERTY_ID' => new \Bitrix\Main\ORM\Fields\IntegerField('IBLOCK_PROPERTY_ID'),
            'VALUE' => new \Bitrix\Main\ORM\Fields\IntegerField('VALUE'),
            
            // Обратная связь с родителем (Доктором)
            'PARENT' => new \Bitrix\Main\ORM\Fields\Relations\Reference(
                'PARENT',
                '{$parentClass}',
                \Bitrix\Main\ORM\Query\Join::on('this.IBLOCK_ELEMENT_ID', 'ref.IBLOCK_ELEMENT_ID')
            ),
            
            // Прямая связь с таблицей элементов (для получения NAME и т.д.)
            'ELEMENT' => new \Bitrix\Main\ORM\Fields\Relations\Reference(
                'ELEMENT',
                \Bitrix\Iblock\ElementTable::class,
                \Bitrix\Main\ORM\Query\Join::on('this.VALUE', 'ref.ID')
            ),
        ];
    }
}
PHP;

        eval($code);
    }

    public static function getMap(): array
    {
        $iblockId = static::getIblockId();
        $cache = Cache::createInstance();
        $cacheDir = 'iblock_property_map/' . $iblockId;

        // Инициализируем класс для множественных значений
        static::initMultipleValuesTableClass();
        $multiClass = static::getMultipleValuesTableClass();

        if ($cache->initCache(3600, md5($cacheDir . $multiClass), $cacheDir)) {
            $map = $cache->getVars();
        } else {
            $cache->startDataCache();

            $map['IBLOCK_ELEMENT_ID'] = new IntegerField('IBLOCK_ELEMENT_ID', ['primary' => true]);

            // Связь с базовой таблицей элементов
            $map['ELEMENT'] = new Reference(
                'ELEMENT',
                ElementTable::class,
                Join::on('this.IBLOCK_ELEMENT_ID', 'ref.ID')
            );

            foreach (static::getProperties() as $property) {
                $code = $property['CODE'];
                $propId = (int)$property['ID'];

                if ($property['MULTIPLE'] === 'Y') {
                    // 1. Оставляем обратную совместимость (массив ID через запятую)
                    $map[$code] = new ExpressionField(
                        $code,
                        sprintf('(select group_concat(`VALUE` SEPARATOR "\0") from %s where IBLOCK_ELEMENT_ID = %%s and IBLOCK_PROPERTY_ID = %d)',
                            static::getTableNameMulti(), $propId
                        ),
                        ['IBLOCK_ELEMENT_ID'],
                        ['fetch_data_modification' => [static::class, 'getMultipleFieldValueModifier']]
                    );

                    // 2. ДОБАВЛЯЕМ OneToMany для работы с коллекциями объектов
                    // Имя поля будет CODE_COLLECTION (например, PROCEDURES_COLLECTION)
                    $map[$code . '_COLLECTION'] = new OneToMany(
                        $code . '_COLLECTION',
                        $multiClass,
                        'PARENT'
                    );

                    // Позволяет фильтровать коллекцию только по нужному свойству
                    // В штатном ORM фильтрация внутри OneToMany сложна,
                    // поэтому обычно итерируем и проверяем PropertyId или используем подзапросы.

                    continue;
                }

                // Обычные свойства (не множественные)
                if ($property['PROPERTY_TYPE'] == 'N') {
                    $map[$code] = new IntegerField("PROPERTY_{$propId}");
                } elseif ($property['USER_TYPE'] === 'Date' || $property['USER_TYPE'] === 'DateTime') {
                    $map[$code] = new DatetimeField("PROPERTY_{$propId}");
                } else {
                    $map[$code] = new StringField("PROPERTY_{$propId}");
                }

                // Если это привязка к элементам (одиночная)
                if ($property['PROPERTY_TYPE'] === 'E') {
                    $map[$code . '_ELEMENT'] = new Reference(
                        $code . '_ELEMENT',
                        ElementTable::class,
                        Join::on("this.{$code}", 'ref.ID')
                    );
                }
            }

            if (empty($map)) {
                $cache->abortDataCache();
            } else {
                $cache->endDataCache($map);
            }
        }

        return $map;
    }

    public static function getProperties(): array
    {
        $iblockId = static::getIblockId();
        if (isset(static::$properties[$iblockId])) {
            return static::$properties[$iblockId];
        }

        $dbResult = PropertyTable::query()
            ->setSelect(['ID', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'NAME', 'USER_TYPE'])
            ->where('IBLOCK_ID', $iblockId)
            ->exec();

        while ($row = $dbResult->fetch()) {
            static::$properties[$iblockId][$row['CODE']] = $row;
        }

        return static::$properties[$iblockId] ?? [];
    }

    public static function getMultipleFieldValueModifier(): array
    {
        return [fn ($value) => array_filter(explode("\0", (string)$value))];
    }
}