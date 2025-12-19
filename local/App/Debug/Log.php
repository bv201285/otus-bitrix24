<?php

namespace App\Debug;
class Log
{

    /**
     * Запись сообщения в лог-файл
     * @param string $message
     * @param string $fileName
     * @return bool|int
     */
    public static function addLog(string $message, string $fileName='log_custom'): bool | int
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . $fileName . '.log';



        $_message = date("d-m-Y H:i:s");
        $_message .= "\n";
        $_message .= print_r($message, true);
        $_message .= "\n";
        $_message .= "-----------------------------------------";
        $_message .= "\n";

        if (file_exists($logFile)) {
            return file_put_contents($logFile, $_message, FILE_APPEND);
        }
        else {
            return false;
        }
    }

    /**
     * Очистка произвольного лог-файла
     * @param string $fileName
     * @return bool|int
     */
    public static function clearLogFile(string $fileName='log_custom'): bool | int
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . $fileName . '.log';

        if (file_exists($logFile)) {
            return file_put_contents($logFile, '');
        }
        else{
            return false;
        }
    }

}