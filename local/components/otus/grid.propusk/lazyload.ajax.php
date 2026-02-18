<?php
use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);

$siteId = isset($_REQUEST['site']) ? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if ($siteId !== '') { define('SITE_ID', $siteId); }

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!check_bitrix_sessid()) { die('Bad sessid'); }

global $APPLICATION;

$APPLICATION->RestartBuffer();
header('Content-Type: text/html; charset=' . LANG_CHARSET);

// ассеты (если надо именно в ajax-ответах)
Asset::getInstance()->addCss('/local/assets/css/tailwind-bx.css');
Asset::getInstance()->addJs('/local/components/otus/grid.propusk/script.js');

$APPLICATION->ShowAjaxHead();

$request = Application::getInstance()->getContext()->getRequest();
$session = Application::getInstance()->getSession();

$gridId = (string)$request->get('grid_id');
if ($gridId === '') {
    // fallback: при первой загрузке может не быть grid_id, тогда попробуем взять из PARAMS
    $gridId = '';
}

$componentData = $request->get('PARAMS');

// Ключ для хранения params компонента в сессии
$sessionKey = 'PROPUSK_TAB_COMPONENT_DATA_' . $gridId;

// 1) Первая загрузка таба: PARAMS пришли -> сохраняем в сессию
if (is_array($componentData)) {
    $componentParams = (isset($componentData['params']) && is_array($componentData['params']))
        ? $componentData['params']
        : [];

    $template = (string)($componentData['template'] ?? 'lazyload');

    // если gridId ещё не определили — берём из params
    if ($gridId === '' && !empty($componentParams['GRID_ID'])) {
        $gridId = (string)$componentParams['GRID_ID'];
        $sessionKey = 'PROPUSK_TAB_COMPONENT_DATA_' . $gridId;
    }

    // сохраняем НА ВСЯКИЙ СЛУЧАЙ всё componentData (params + template)
    $session->set($sessionKey, $componentData);
}
// 2) Внутренний ajax грида/фильтра: PARAMS не пришли -> берём из сессии
else {
    $saved = $session->get($sessionKey);

    if (is_array($saved)) {
        $componentData = $saved;
        $componentParams = (isset($componentData['params']) && is_array($componentData['params']))
            ? $componentData['params']
            : [];
        $template = (string)($componentData['template'] ?? 'lazyload');
    } else {
        // если вообще нечего взять — делаем минимальный fallback, но фильтр может не работать
        $componentParams = [
            'SHOW_CHECKBOXES' => 'Y',
            'GRID_ID' => $gridId ?: 'PROPUSK_GRID',
            'PREFIX' => 'otus',
        ];
        $template = 'lazyload';
    }
}

$APPLICATION->IncludeComponent(
    'otus:grid.propusk',
    $template,
    $componentParams,
    false
);

\CMain::FinalActions();
die();