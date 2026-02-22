<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Web\Json;

$cfg = $arResult['ddpt'];
$uid = $cfg['uid'];
$name = $cfg['name'];
$rows = $cfg['rows'];
?>
<div class="ddpt" id="<?=htmlspecialcharsbx($uid)?>">
    <input type="hidden"
           name="<?=htmlspecialcharsbx($name)?>"
           value="<?=htmlspecialcharsbx(Json::encode($rows))?>"
           data-role="store">

    <div class="ddpt__tablewrap">
        <table class="ddpt__table">
            <thead>
            <tr>
                <th style="width: 26%;">Врач</th>
                <!--<th style="width: 26%;">Процедура</th>-->
                <th style="width: 16%;">Дата</th>
                <th>Комментарий</th>
                <th style="width:34px;"></th>
            </tr>
            </thead>
            <tbody data-role="tbody">
            <!-- строки рисуются JS-ом -->
            </tbody>
        </table>
    </div>

    <div class="ddpt__actions">
        <button type="button" class="ui-btn ui-btn-primary" data-role="addRow">Добавить строку</button>
    </div>

    <script>
        window.DDPT_InitQueue = window.DDPT_InitQueue || [];
        window.DDPT_InitQueue.push({
            rootId: "<?=\CUtil::JSEscape($uid)?>",
            rows: <?=Json::encode($rows)?>
        });
        if (window.DDPT_InitAll) { window.DDPT_InitAll(); }
    </script>
</div>