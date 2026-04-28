<?php
require_once (__DIR__.'/crest.php');

$result = CRest::installApp();

if($result['rest_only'] === false): ?>
    <head>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <?php if($result['install'] == true): ?>
            <script>
                BX24.init(function(){
                    BX24.installFinish();
                });
            </script>
        <?php endif; ?>
    </head>
    <body>
    <?php if($result['install'] == true):

        $handlerUrl = 'https://cz768396.tw1.ru/local/local_app/update_last_comm_date/handler.php';

        $eventResult = CRest::call('event.bind', [
            'event' => 'OnCrmActivityAdd',
            'handler' => $handlerUrl,
        ]);
        ?>

        <h1>Установка завершена</h1>
        <p>
            <?php if(isset($eventResult['result']) && $eventResult['result'] === true): ?>
                Событие OnCrmActivityAdd успешно зарегистрировано.
            <?php else: ?>
                Ошибка регистрации события: <?php echo $eventResult['error_description'] ?? 'неизвестно'; ?>
            <?php endif; ?>
        </p>

    <?php else: ?>
        installation error
    <?php endif; ?>
    </body>
<?php endif;