<?php
require_once(__DIR__ . '/crest.php');

$handlerUrl = 'https://cz768396.tw1.ru/local/local_app/update_last_comm_date/handler_test.php';

$events = [
    'ONCRMACTIVITYADD',
    'ONCRMACTIVITYUPDATE',
    'ONCRMACTIVITYDELETE',
    'ONCRMCONTACTADD',
    'ONCRMCONTACTUPDATE',
    'ONCRMCONTACTDELETE',
    'ONCRMDEALADD',
    'ONCRMDEALUPDATE',
    'ONCRMDEALDELETE',
    'ONCRMLEADADD',
    'ONCRMLEADUPDATE',
    'ONCRMLEADDELETE',
];

echo '<pre>';

foreach ($events as $event) {
    echo "BIND {$event}\n";
    print_r(CRest::call('event.bind', [
        'event' => $event,
        'handler' => $handlerUrl,
    ]));
    echo "\n";
}

echo "EVENT.GET\n";
print_r(CRest::call('event.get'));

echo '</pre>';