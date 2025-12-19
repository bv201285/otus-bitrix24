<?php
    global $APPLICATION;
    use App\Debug\Log as CustomLog;

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

    $APPLICATION->SetTitle("Добавление в лог");
?>
    <ul class="list-group">
        <li class="list-group-item">
            <a href="/local/logs/log_custom.log?version=<?=time()?>">Файл лога</a>,
<?php
    $result = CustomLog::addLog('Открыта страница writelog.php');
    echo $result ? "в лог добавленно 'Открыта страница writelog.php'" : 'Ошибка при добавлении записи в лог!';
?>
        </li>
    </ul>
<?php
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");