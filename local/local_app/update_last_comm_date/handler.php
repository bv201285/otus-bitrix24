<?php
require_once(__DIR__ . '/crest.php');

file_put_contents(__DIR__ . '/../../logs/debug_local_app.log', "--- НОВЫЙ ЗАПРОС ---" . PHP_EOL . print_r($_REQUEST, true) . PHP_EOL, FILE_APPEND);

$activityId = $_REQUEST['data']['FIELDS']['ID'] ?? null;

if ($activityId) {
    $activityInfo = CRest::call('crm.activity.get', ['id' => $activityId]);

    if (isset($activityInfo['result'])) {
        $ownerId = $activityInfo['result']['OWNER_ID'];
        $ownerTypeId = $activityInfo['result']['OWNER_TYPE_ID'];

        if ($ownerTypeId == 3) {
            $update = CRest::call('crm.contact.update', [
                'id' => $ownerId,
                'fields' => [
                    'UF_CRM_LAST_COMM_DATE' => date('Y-m-d H:i:s')
                ]
            ]);
            file_put_contents(__DIR__ . '/../../logs/debug_local_app.log', "Update status: " . json_encode($update) . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/../../logs/debug_local_app.log', "Активность не для контакта (тип: $ownerTypeId)" . PHP_EOL, FILE_APPEND);
        }
    } else {
        file_put_contents(__DIR__ . '/../../logs/debug_local_app.log', "Ошибка получения активности: " . print_r($activityInfo, true) . PHP_EOL, FILE_APPEND);
    }
} else {
    file_put_contents(__DIR__ . '/../../logs/debug_local_app.log', "ID активности не найден в запросе" . PHP_EOL, FILE_APPEND);
}
