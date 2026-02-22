<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;

Asset::getInstance()->addCss('/local/assets/css/deal-dp-table/deal-dp-table.css');

$raw = (string)($arResult['value'][0] ?? ''); // поле у нас НЕ multiple, обычно значение одно
$rows = [];

if ($raw !== '') {
    try {
        $rows = Json::decode($raw);
    } catch (\Throwable $e) {
        $rows = [];
    }
}
if (!is_array($rows)) $rows = [];

if (!count($rows)) {
    echo '<div class="ddpt-view-item" style="color:#a8adb4;">Нет данных</div>';
    return;
}

/** Соберём IDs для batch-запроса */
$doctorIds = [];
$procedureIds = [];

foreach ($rows as $r) {
    if (!is_array($r)) continue;
    $d = (int)($r['doctorId'] ?? 0);
    $p = (int)($r['procedureId'] ?? 0);
    if ($d > 0) $doctorIds[$d] = $d;
    if ($p > 0) $procedureIds[$p] = $p;
}

$names = []; // [id => name]

if (Loader::includeModule('iblock')) {
    $allIds = array_values($doctorIds + $procedureIds);
    if ($allIds) {
        $res = ElementTable::getList([
            'select' => ['ID','NAME'],
            'filter' => ['@ID' => $allIds],
        ]);
        while ($row = $res->fetch()) {
            $names[(int)$row['ID']] = (string)$row['NAME'];
        }
    }
}

$getName = static function(int $id) use ($names): string {
    if ($id <= 0) return '—';
    return $names[$id] ?? ('#'.$id);
};
?>

<div class="ddpt__tablewrap">
    <table class="ddpt__table ddpt__table--view">
        <thead>
        <tr>
            <th style="width:26%;">Врач</th>
            <!--<th style="width:26%;">Процедура</th>-->
            <th style="width:16%;">Дата</th>
            <th>Комментарий</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <?php
            if (!is_array($r)) continue;

            $doctorId = (int)($r['doctorId'] ?? 0);
            $procId   = (int)($r['procedureId'] ?? 0);
            $date     = (string)($r['date'] ?? '');
            $text     = (string)($r['text'] ?? '');
            ?>
            <tr class="ddpt__tr">
                <td><?=htmlspecialcharsbx($getName($doctorId))?></td>
                <!--<td><?php /*=htmlspecialcharsbx($getName($procId))*/?></td>-->
                <td><?=htmlspecialcharsbx($date !== '' ? $date : '—')?></td>
                <td><?=htmlspecialcharsbx($text)?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>