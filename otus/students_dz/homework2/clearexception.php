<?php
    use App\Debug\Log as CustomLog;
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

    CustomLog::clearLogFile('exceptions');

    LocalRedirect('/otus/students_dz/homework2/');
