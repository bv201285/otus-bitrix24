<?php

namespace App\Debug;

class Log
{
    public static function addLog(mixed $data, string $fileName = 'log_custom'): bool|int
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $logFile = $dir . '/' . $fileName . '.log';

        $message = self::stringify($data);

        // Пишем лог ВСЕГДА в UTF-8
        $message = self::toLogEncoding($message); // -> UTF-8

        $out  = date('d-m-Y H:i:s') . "\n";
        $out .= $message . "\n";
        $out .= "-----------------------------------------\n";

        return file_put_contents($logFile, $out, FILE_APPEND);
    }

    private static function stringify(mixed $data): string
    {
        if ($data instanceof \Throwable) {
            return $data->getMessage() . "\n" . $data->getTraceAsString();
        }

        if (is_string($data)) {
            return $data;
        }

        return print_r($data, true);
    }

    /**
     * Приводим текст к кодировке лог-файла (в этом примере: UTF-8)
     */
    private static function toLogEncoding(string $s): string
    {
        // Если Битрикс в UTF — строки уже в UTF-8, не трогаем
        if (defined('BX_UTF') && BX_UTF) {
            return $s;
        }

        // Иначе проект CP1251 -> конвертируем в UTF-8
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($s, 'UTF-8', 'Windows-1251');
            return $converted !== false ? $converted : $s;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $s);
            return $converted !== false ? $converted : $s;
        }

        return $s;
    }
}