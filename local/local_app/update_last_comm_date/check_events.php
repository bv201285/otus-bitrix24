<?php
require_once(__DIR__ . '/crest.php');

echo '<pre>';
print_r(CRest::call('event.get'));
echo '</pre>';