<?php

namespace App\Models;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use CIBlockElement;

/**
 * Class AbstractIblockPropertyValuesTable
 *
 * Базовый класс для работы с таблицами значений свойств инфоблоков (s-таблицы).
 * Позволяет работать как через IBLOCK_ID, так и через IBLOCK_API_CODE.
 *
 * @package Models
 */
abstract class AbstractIblockPropertyValuesTable extends DataManager
{
    /** @var int|null ID инфоблока */
    const IBLOCK_ID = null;

    /** @var string|null API код инфоблока (из настроек инфоблока) */
    const IBLOCK_API_CODE = null;

    protected static ?array $properties = null;
    protected static ?CIBlockElement $iblockElement = null;

    /** @var array Локальный кеш разрешенных ID инфоблоков для классов-наследников */
    protected static array $resolvedIblockIds = [];

    /**
     * Возвращает ID инфоблока.
     * Сначала проверяет константу IBLOCK_ID, если она пуста - ищет по IBLOCK_API_CODE.
     *
     * @return int
     * @throws SystemException
     */
    public static function getIblockId(): int
    {
        $className = static::class;

        if (!isset(self::$resolvedIblockIds[$className])) {
            if (static::IBLOCK_ID !== null) {
                self::$resolvedIblockIds[$className] = (int)static::IBLOCK_ID;
            } elseif (static::IBLOCK_API_CODE !== null) {
                // Получаем метаданные инфоблока через API_CODE
                $entity = IblockTable::compileEntity(static::IBLOCK_API_CODE);
                $iblock = $entity->getIblock();

                if (!$iblock) {
                    throw new SystemException("Iblock with API_CODE '" . static::IBLOCK_API_CODE . "' not found.");
                }

                self::$resolvedIblockIds[$className] = (int)$iblock->getId();
            } else {
                throw new SystemException("You must define IBLOCK_ID or IBLOCK_API_CODE in " . $className);
            }
        }

        return self::$resolvedIblockIds[$className];
    }

    /**
     * @return string
     * @throws SystemException
     */
    public static function getTableName(): string
    {
        return 'b_iblock_element_prop_s' . static::getIblockId();
    }

    /**
     * @return string
     * @throws SystemException
     */
    public static function getTableNameMulti(): string
    {
        return 'b_iblock_element_prop_m' . static::getIblockId();
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
    public static function getMap(): array
    {
        $iblockId = static::getIblockId();
        $cache = Cache::createInstance();
        $cacheDir = 'iblock_property_map/' . $iblockId;

        $multipleValuesTableClass = static::getMultipleValuesTableClass();
        static::initMultipleValuesTableClass();

        if ($cache->initCache(3600, md5($cacheDir), $cacheDir)) {
            $map = $cache->getVars();
        } else {
            $cache->startDataCache();

            $map['IBLOCK_ELEMENT_ID'] = new IntegerField('IBLOCK_ELEMENT_ID', ['primary' => true]);
            $map['ELEMENT'] = new ReferenceField(
                'ELEMENT',
                ElementTable::class,
                ['=this.IBLOCK_ELEMENT_ID' => 'ref.ID']
            );

            foreach (static::getProperties() as $property) {
                if ($property['MULTIPLE'] === 'Y') {
                    $map[$property['CODE']] = new ExpressionField(
                        $property['CODE'],
                        sprintf('(select group_concat(`VALUE` SEPARATOR "\0") as VALUE from %s as m where m.IBLOCK_ELEMENT_ID = %s and m.IBLOCK_PROPERTY_ID = %d)',
                            static::getTableNameMulti(),
                            '%s',
                            $property['ID']
                        ),
                        ['IBLOCK_ELEMENT_ID'],
                        ['fetch_data_modification' => [static::class, 'getMultipleFieldValueModifier']]
                    );

                    if ($property['USER_TYPE'] === 'EList') {
                        $map[$property['CODE'].'_ELEMENT_NAME'] = new ExpressionField(
                            $property['CODE'].'_ELEMENT_NAME',
                            sprintf('(select group_concat(e.NAME SEPARATOR "\0") as VALUE from %s as m join b_iblock_element as e on m.VALUE = e.ID where m.IBLOCK_ELEMENT_ID = %s and m.IBLOCK_PROPERTY_ID = %d)',
                                static::getTableNameMulti(),
                                '%s',
                                $property['ID']
                            ),
                            ['IBLOCK_ELEMENT_ID'],
                            ['fetch_data_modification' => [static::class, 'getMultipleFieldValueModifier']]
                        );
                    }

                    $map[$property['CODE'].'|SINGLE'] = new ReferenceField(
                        $property['CODE'].'|SINGLE',
                        $multipleValuesTableClass,
                        [
                            '=this.IBLOCK_ELEMENT_ID' => 'ref.IBLOCK_ELEMENT_ID',
                            '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $property['ID'])
                        ]
                    );

                    continue;
                }

                if ($property['PROPERTY_TYPE'] == PropertyTable::TYPE_NUMBER) {
                    $map[$property['CODE']] = new IntegerField("PROPERTY_{$property['ID']}");
                } elseif ($property['USER_TYPE'] === 'Date') {
                    $map[$property['CODE']] = new DatetimeField("PROPERTY_{$property['ID']}");
                } else {
                    $map[$property['CODE']] = new StringField("PROPERTY_{$property['ID']}");
                }

                if ($property['PROPERTY_TYPE'] === 'E' && ($property['USER_TYPE'] === 'EList' || is_null($property['USER_TYPE']))) {
                    $map[$property['CODE'].'_ELEMENT'] = new ReferenceField(
                        $property['CODE'].'_ELEMENT',
                        ElementTable::class,
                        ["=this.{$property['CODE']}" => 'ref.ID']
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

    /**
     * @param array $data
     * @return bool
     * @throws SystemException
     */
    public static function add(array $data): bool
    {
            static::$iblockElement ?? static::$iblockElement = new CIBlockElement();
        $fields = [
            'NAME'            => $data['NAME'],
            'IBLOCK_ID'       => static::getIblockId(),
            'PROPERTY_VALUES' => $data,
        ];

        return (bool)static::$iblockElement->Add($fields);
    }

    /**
     * @param $primary
     * @return DeleteResult
     * @throws NotImplementedException
     */
    public static function delete($primary): DeleteResult
    {
        throw new NotImplementedException('Delete method is not implemented yet.');
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
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

    /**
     * @param string $code
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getPropertyId(string $code): int
    {
        $properties = static::getProperties();
        return (int)($properties[$code]['ID'] ?? 0);
    }

    /**
     * @return array
     */
    public static function getMultipleFieldValueModifier(): array
    {
        return [fn ($value) => array_filter(explode("\0", (string)$value))];
    }

    /**
     * @param int|null $iblockId
     * @throws SystemException
     */
    public static function clearPropertyMapCache(?int $iblockId = null): void
    {
        $iblockId = $iblockId ?: static::getIblockId();
        if (empty($iblockId)) {
            return;
        }

        Cache::clearCache(true, "iblock_property_map/$iblockId");
    }

    /**
     * @param string $propertyCode
     * @param string $byKey
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getEnumPropertyOptions(string $propertyCode, string $byKey = 'ID'): array
    {
        $dbResult = PropertyEnumerationTable::getList([
            'select' => ['ID', 'VALUE', 'XML_ID', 'SORT'],
            'filter' => [
                '=PROPERTY.CODE' => $propertyCode,
                'PROPERTY.IBLOCK_ID' => static::getIblockId()
            ],
        ]);

        $enumPropertyOptions = [];
        while ($row = $dbResult->fetch()) {
            $enumPropertyOptions[$row[$byKey]] = $row;
        }

        return $enumPropertyOptions;
    }

    /**
     * @return string
     */
    private static function getMultipleValuesTableClass(): string
    {
        $parts = explode('\\', static::class);
        $className = end($parts);
        $namespace = implode('\\', array_slice($parts, 0, -1));
        $className = str_replace('Table', 'MultipleTable', $className);

        return $namespace . '\\' . $className;
    }

    /**
     * @return void
     * @throws SystemException
     */
    private static function initMultipleValuesTableClass(): void
    {
        $fullClassName = static::getMultipleValuesTableClass();

        if (class_exists($fullClassName)) {
            return;
        }

        // Логика автоматического создания класса (через eval), если он отсутствует
        // Раскомментируйте, если требуется динамическое создание MultipleTable
        /*
        $parts = explode('\\', $fullClassName);
        $className = end($parts);
        $namespace = implode('\\', array_slice($parts, 0, -1));
        $iblockId = static::getIblockId();

        $php = "namespace $namespace;
                class $className extends \Models\AbstractIblockPropertyMultipleValuesTable
                { const IBLOCK_ID = $iblockId; }";
        eval($php);
        */
    }
}