<?php

namespace App\IBlock\EventHandlers;

use App\Crm\EventHandlers\DealHandler;
use App\Debug\Log;
use App\Models\Lists\OrdersPropertyValuesTable;
use Bitrix\Main\Loader;

class OrderHandler
{
    private static array $before = [];

    public static function onBeforeElementUpdate(array &$arFields): void
    {
        // Анти-цикл: если сейчас идёт синхронизация из сделки -> в Orders,
        // то этот апдейт элемента был инициирован DealHandler-ом. Ничего не делаем.
        if (defined(DealHandler::LOCK_FLAG) && constant(DealHandler::LOCK_FLAG) === 'Y') {
            return;
        }

        if ((int)($arFields['IBLOCK_ID'] ?? 0) !== OrdersPropertyValuesTable::getIblockId()) {
            return;
        }

        $elementId = (int)($arFields['ID'] ?? 0);
        if ($elementId <= 0) {
            return;
        }

        $row = OrdersPropertyValuesTable::getByPrimary($elementId, [
            'select' => ['IBLOCK_ELEMENT_ID', 'Deal', 'Sum', 'Sotrudnik'],
        ])->fetch();

        if (!$row) {
            return;
        }

        self::$before[$elementId] = [
            'Deal'      => (string)($row['Deal'] ?? ''),
            'Sum'       => (string)($row['Sum'] ?? ''),
            'Sotrudnik' => (string)($row['Sotrudnik'] ?? ''),
        ];
    }

    public static function onAfterElementUpdate(array &$arFields): void
    {
        // Анти-цикл: если апдейт элемента был инициирован DealHandler-ом,
        // то не обновляем сделку обратно.
        if (defined(DealHandler::LOCK_FLAG) && constant(DealHandler::LOCK_FLAG) === 'Y') {
            return;
        }

        if ((int)($arFields['IBLOCK_ID'] ?? 0) !== OrdersPropertyValuesTable::getIblockId()) {
            return;
        }

        $elementId = (int)($arFields['ID'] ?? 0);
        if ($elementId <= 0) {
            return;
        }

        $before = self::$before[$elementId] ?? [];

        $after = OrdersPropertyValuesTable::getByPrimary($elementId, [
            'select' => ['IBLOCK_ELEMENT_ID', 'Deal', 'Sum', 'Sotrudnik'],
        ])->fetch();

        if (!$after) {
            return;
        }

        $dealId = self::extractDealId((string)($after['Deal'] ?? ''));
        if ($dealId <= 0) {
            return;
        }

        $dealFields = [];

        // 1) Сумма -> OPPORTUNITY
        $sumOld = $before['Sum'] ?? null;
        $sumNew = (string)($after['Sum'] ?? '');

        if ($sumOld === null || $sumOld !== $sumNew) {
            $dealFields['OPPORTUNITY'] = (float)$sumNew;
        }

        // 2) Ответственный -> ASSIGNED_BY_ID
        $respOld = $before['Sotrudnik'] ?? null;
        $respNew = (string)($after['Sotrudnik'] ?? '');
        $respNewId = self::extractUserId($respNew);

        if (($respOld === null || $respOld !== $respNew) && $respNewId > 0) {
            $dealFields['ASSIGNED_BY_ID'] = $respNewId;
        }

        if (empty($dealFields)) {
            return;
        }

        self::updateDealViaCrm($dealId, $dealFields);
    }

    private static function updateDealViaCrm(int $dealId, array $fields): void
    {
        if (!Loader::includeModule('crm')) {
            Log::addLog('CRM module not loaded', 'log_custom');
            return;
        }

        // Ставим lock на время апдейта сделки, чтобы DealHandler (Deal -> Orders)
        // не попытался тут же обновить Orders обратно.
        if (!defined(DealHandler::LOCK_FLAG)) {
            define(DealHandler::LOCK_FLAG, 'Y');
        }

        $deal = new \CCrmDeal(false);
        $result = $deal->Update($dealId, $fields, true, true);

        if (!$result) {
            $err = $deal->LAST_ERROR ?? 'Unknown CRM error';
            Log::addLog([
                'dealId' => $dealId,
                'fields' => $fields,
                'error'  => $err,
            ], 'log_custom');
        }
    }

    private static function extractDealId(string $dealValue): int
    {
        return preg_match('~(\d+)~', $dealValue, $m) ? (int)$m[1] : 0;
    }

    private static function extractUserId(string $userValue): int
    {
        return preg_match('~(\d+)~', $userValue, $m) ? (int)$m[1] : 0;
    }
}