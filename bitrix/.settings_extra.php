<?php
use App\Debug\ExceptionLog as ExceptionLog;

return [
    'exception_handling' =>
        array (
            'value' =>
                array (
                    'debug' => true,
                    'handled_errors_types' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_STRICT,
                    'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_STRICT,
                    'ignore_silence' => false,
                    'assertion_throws_exception' => true,
                    'assertion_error_type' => 256,
                    'log' => [
                        'class_name' => ExceptionLog::class,
                        'settings' => [
                            'file' => 'local/logs/exceptions.log',
                            'log_size' => 1000000,
                        ]
                    ],
                ),
            'readonly' => false,
        )
];