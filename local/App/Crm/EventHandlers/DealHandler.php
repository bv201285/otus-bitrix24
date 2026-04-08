<?php

namespace App\Crm\EventHandlers;

use App\Models\Lists\OrdersPropertyValuesTable;
use Bitrix\Main\Loader;

class DealHandler
{
    public const LOCK_FLAG = 'APP_SYNC_LOCK';

    private static array $before = [];
    private const LOG_FILE = 'deal_sync';

    public static function onBeforeDealUpdate(array &$arFields): void
    {
        if (!Loader::includeModule('crm')) {
            return;
        }

        $dealId = (int)($arFields['ID'] ?? 0);
        if ($dealId <= 0) {
            return;
        }

        $deal = \CCrmDeal::GetByID($dealId, false);
        if (!is_array($deal)) {
            return;
        }

        self::$before[$dealId] = [
            'OPPORTUNITY'    => (string)($deal['OPPORTUNITY'] ?? ''),
            'ASSIGNED_BY_ID' => (string)($deal['ASSIGNED_BY_ID'] ?? ''),
        ];
    }

    public static function onAfterDealUpdate(array &$arFields): void
    {
        if (!Loader::includeModule('crm')) {
            return;
        }

        $dealId = (int)($arFields['ID'] ?? 0);
        if ($dealId <= 0) {
            return;
        }

        // анти-цикл: если сделку обновили из OrderHandler — не трогаем Orders обратно
        if (defined(self::LOCK_FLAG) && constant(self::LOCK_FLAG) === 'Y') {
            return;
        }

        $before = self::$before[$dealId] ?? null;

        $deal = \CCrmDeal::GetByID($dealId, false);
        if (!is_array($deal)) {
            return;
        }

        $oppNew  = (string)($deal['OPPORTUNITY'] ?? '');
        $respNew = (string)($deal['ASSIGNED_BY_ID'] ?? '');

        $oppOld  = $before['OPPORTUNITY'] ?? null;
        $respOld = $before['ASSIGNED_BY_ID'] ?? null;

        $needOpp  = $oppOld === null || $oppOld !== $oppNew;
        $needResp = $respOld === null || $respOld !== $respNew;

        if (!$needOpp && !$needResp) {
            return;
        }

        $orderIds = self::findOrderIdsByDealId($dealId);
        if (empty($orderIds)) {
            return;
        }

        $propsToSet = [];

        if ($needOpp) {
            $propsToSet['Sum'] = (float)$oppNew;
        }

        if ($needResp) {
            $propsToSet['Sotrudnik'] = (int)$respNew;
        }

        if (empty($propsToSet)) {
            return;
        }

        self::setOrdersProperties($orderIds, $propsToSet);
    }

    private static function findOrderIdsByDealId(int $dealId): array
    {
        $rows = OrdersPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID'],
            'filter' => ['=Deal' => (string)$dealId], // Deal: PROPERTY_79 (StringField)
        ])->fetchAll();

        return array_values(array_unique(array_map(
            static fn(array $r) => (int)$r['IBLOCK_ELEMENT_ID'],
            $rows
        )));
    }

    private static function setOrdersProperties(array $elementIds, array $propsByCode): void
    {
        if (!Loader::includeModule('iblock')) {
            return;
        }

        // lock, чтобы при обновлении Orders не запускать синхронизацию Orders -> Deal
        if (!defined(self::LOCK_FLAG)) {
            define(self::LOCK_FLAG, 'Y');
        }

        $iblockId = OrdersPropertyValuesTable::getIblockId();

        foreach ($elementIds as $elementId) {
            \CIBlockElement::SetPropertyValuesEx((int)$elementId, $iblockId, $propsByCode);
        }
    }
}