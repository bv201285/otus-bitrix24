<?php
require_once(__DIR__ . '/crest.php');

echo '<pre>';

$result = CRest::call('crm.activity.add', [
    'fields' => [
        'OWNER_TYPE_ID' => 3,
        'OWNER_ID' => 1,
        'TYPE_ID' => 1,
        'PROVIDER_ID' => 'CRM_TASK',
        'PROVIDER_TYPE_ID' => 'TASK',
        'SUBJECT' => 'Тестовая активность через REST',
        'RESPONSIBLE_ID' => 1,
        'COMPLETED' => 'N',
        'PRIORITY' => 2,
        'DESCRIPTION' => 'Тест',
        'DESCRIPTION_TYPE' => 1,
        'COMMUNICATIONS' => [
            [
                'ENTITY_TYPE_ID' => 3,
                'ENTITY_ID' => 1,
            ]
        ],
    ]
]);

print_r($result);

echo '</pre>';