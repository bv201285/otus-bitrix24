<?php
// Подключаем библиотеку
require_once(__DIR__ . '/crest.php');

// 1. Логируем факт прихода запроса (чтобы убедиться, что Битрикс стучится к вам)
file_put_contents(__DIR__ . '/debug.log', "--- НОВЫЙ ЗАПРОС ---" . PHP_EOL . print_r($_REQUEST, true) . PHP_EOL, FILE_APPEND);

// 2. Устанавливаем авторизацию, если она передана (обязательно!)
/*if (isset($_REQUEST['auth'])) {
    CRest::setAuth($_REQUEST['auth']);
}*/

// 3. Получаем ID активности из структуры, которую вы прислали
$activityId = $_REQUEST['data']['FIELDS']['ID'] ?? null;

if ($activityId) {
    // 4. Получаем полные данные активности
    $activityInfo = CRest::call('crm.activity.get', ['id' => $activityId]);

    if (isset($activityInfo['result'])) {
        $ownerId = $activityInfo['result']['OWNER_ID'];
        $ownerTypeId = $activityInfo['result']['OWNER_TYPE_ID'];

        // 5. Проверяем, что это Контакт (ID типа контакта в Битриксе = 3)
        if ($ownerTypeId == 3) {

            // 6. Обновляем поле
            $update = CRest::call('crm.contact.update', [
                'id' => $ownerId,
                'fields' => [
                    'UF_CRM_LAST_COMM_DATE' => date('Y-m-d H:i:s')
                ]
            ]);

            // Логируем результат обновления
            file_put_contents(__DIR__ . '/debug.log', "Update status: " . json_encode($update) . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug.log', "Активность не для контакта (тип: $ownerTypeId)" . PHP_EOL, FILE_APPEND);
        }
    } else {
        file_put_contents(__DIR__ . '/debug.log', "Ошибка получения активности: " . print_r($activityInfo, true) . PHP_EOL, FILE_APPEND);
    }
} else {
    file_put_contents(__DIR__ . '/debug.log', "ID активности не найден в запросе" . PHP_EOL, FILE_APPEND);
}
?>