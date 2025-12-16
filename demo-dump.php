<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/**
 * @var CMain $APPLICATION
 */

 $APPLICATION->setTitle('Demo Dump');

 dump(['e' => '123', '44' => 4]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';

?>
