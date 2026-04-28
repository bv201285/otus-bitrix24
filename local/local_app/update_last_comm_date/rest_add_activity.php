<?php
require_once(__DIR__ . '/crest.php');

echo '<pre>';

$result = CRest::call('crm.activity.add', [
    'fields' => [
        'OWNER_TYPE_ID' => 3,
        'OWNER_ID' => 1, // ID контакта
        'TYPE_ID' => 1,
        'SUBJECT' => 'Тестовая активность через REST',
        'RESPONSIBLE_ID' => 1,
        'COMPLETED' => 'N',
    ]
]);

print_r($result);

echo '</pre>';