<?php
file_put_contents(
    __DIR__ . '/debug.log',
    "\n==== " . date('Y-m-d H:i:s') . " ====\n" .
    "EVENT: " . ($_REQUEST['event'] ?? 'NO_EVENT') . "\n" .
    "REQUEST:\n" . print_r($_REQUEST, true) . "\n",
    FILE_APPEND
);

echo 'OK';