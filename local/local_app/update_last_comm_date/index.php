<?php
require_once(__DIR__ . '/crestcurrent.php');

$data = $_REQUEST['data'];

if ($data['FIELDS']['ENTITY_TYPE_ID'] === 'CONTACT') {

    $contactId = $data['FIELDS']['ENTITY_ID'];

    CRestCurrent::call('crm.contact.update', [
            'id' => $contactId,
            'fields' => [
                    'UF_CRM_LAST_COMM_DATE' => date('d.m.Y H:i:s')
            ]
    ]);
}
?>