<?php
require_once(__DIR__ . '/crest.php');

$commentId = $_REQUEST['data']['ID'] ?? null;

if ($commentId) {
    $commentInfo = CRest::call('crm.timeline.comment.get', [
        'id' => $commentId
    ]);

    if (isset($commentInfo['result'])) {
        $commentData = $commentInfo['result'];

        $entityId = $commentData['ENTITY_ID'];
        $entityType = $commentData['ENTITY_TYPE'];

        if ($entityType === 'contact') {
            CRest::call('crm.contact.update', [
                'id' => $entityId,
                'fields' => [
                    'UF_CRM_LAST_COMM_DATE' => date('d.m.Y H:i:s')
                ]
            ]);
        }
    }
}
?>