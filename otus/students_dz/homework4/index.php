<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use App\Models\Orm\PropuskTable;
use App\Models\Lists\BuildingsPropertyValuesTable;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Entity\ReferenceField;

Asset::getInstance()->addString('<script src="https://cdn.tailwindcss.com"></script>');

$application = Application::getInstance();
$request = $application->getContext()->getRequest();

// --- ЛОГИКА ОЧИСТКИ КЕША ---
if (isset($_GET['clear_cache']) && $_GET['clear_cache'] === 'y') {
    $taggedCache = $application->getTaggedCache();
    $taggedCache->clearByTag('PROPUSK_LIST');

    LocalRedirect($request->getRequestedPage());
}
// ---------------------------

/*$propuskList = PropuskTable::getList([
        'select' => [
            'ID',
            'TITLE',
            'VALIDITY_PERIOD',
            'DOCTOR_NAME' => 'DOCTOR.ELEMENT.NAME',
            'DOCTOR.*'
        ]
])->fetchCollection();*/

$cache = Cache::createInstance();
$taggedCache = Application::getInstance()->getTaggedCache();

$cacheTime = 300;
$cacheId = 'propusk_tag_cache_' . CurrentUser::get()->getId();
$cacheDir = 'propusk';
$cacheTag = 'PROPUSK_LIST';

if ($cache->initCache($cacheTime, $cacheId, $cacheDir)) {
    $fromCache = true;
    $propuskList = $cache->getVars();
} else {
    $fromCache = false;
    $cache->startDataCache();
    $taggedCache->startTagCache($cacheDir);

    $taggedCache->registerTag($cacheTag);

    $propuskList = PropuskTable::query()
            ->setSelect([
                    'ID',
                    'TITLE',
                    'VALIDITY_PERIOD',
                    'DOCTOR_NAME' => 'DOCTOR.ELEMENT.NAME',
                    'DOCTOR_SPECIALIZATION' => 'DOCTOR.SPECIALIZATION',
                    'DOCTOR_CATEGORY' => 'DOCTOR.CATEGORY',
                    'BUILDINGS_NAME' => 'BUILDINGS.ELEMENT.NAME',
            ])
            ->registerRuntimeField(
                    null,
                    new ReferenceField(
                            'BUILDINGS',
                            BuildingsPropertyValuesTable::getEntity(),
                            ['=this.BUILDINGS_ID' => 'ref.IBLOCK_ELEMENT_ID']
                    )
            )->fetchAll();

    $taggedCache->endTagCache();
    $cache->endDataCache($propuskList);
}



/*foreach ($propuskList as $propusk) {
    dump($propusk);
}*/

//dump($propuskList);

?>
<div class="bg-gray-50 min-h-screen py-10 px-4">
    <div class="max-w-6xl mx-auto">

        <!-- КНОПКА ОЧИСТКИ КЕША -->
        <div class="flex justify-end mb-6">
            <a href="?clear_cache=y"
               class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-2 px-4 rounded-lg shadow-sm transition-colors duration-200 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Очистить тегированный кеш
            </a>
        </div>

        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Реестр пропусков <?= $fromCache ? '(из кэша)' : '(из базы)'?></h1>

        <?php
        if (empty($propuskList)): ?>
            <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
                Пропусков пока нет.
            </div>
        <?php else: ?>
            <!-- Сетка: 1 колонка на мобилках, 2 на планшетах, 3 на десктопе -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php foreach ($propuskList as $propusk):
                    // Форматируем дату, если она пришла как объект Bitrix\Main\Type\Date
                    $dateEnd = ($propusk['VALIDITY_PERIOD'] instanceof \Bitrix\Main\Type\Date)
                        ? $propusk['VALIDITY_PERIOD']->format('d.m.Y')
                        : $propusk['VALIDITY_PERIOD'];
                    ?>

                    <!-- Карточка -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-300">
                        <!-- Заголовок и Срок действия -->
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-xl font-bold text-blue-700 leading-tight">
                                <?= htmlspecialcharsbx($propusk['TITLE']) ?>
                            </h2>
                            <span class="bg-yellow-50 text-gray-600 px-2 py-1 rounded font-medium">
                                <?= htmlspecialcharsbx($propusk['BUILDINGS_NAME'])?>
                            </span>
                        </div>

                        <div class="space-y-3">
                            <!-- Срок действия -->
                            <div class="flex items-center text-sm text-gray-600">
                                <span class="font-semibold w-32">Срок действия:</span>
                                <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded font-medium">
                                    <?= $dateEnd ?>
                                </span>
                            </div>

                            <!-- Врач -->
                            <div class="flex flex-col border-t border-gray-50 pt-3">
                                <span class="text-xs text-gray-400 uppercase tracking-wider mb-1">Лечащий врач</span>
                                <span class="text-gray-800 font-medium">
                                    <?= htmlspecialcharsbx($propusk['DOCTOR_NAME']) ?>
                                </span>
                            </div>

                            <!-- Специальность -->
                            <div class="flex items-center text-sm">
                                <span class="text-gray-500 w-32">Специальность:</span>
                                <span class="text-gray-700"><?= htmlspecialcharsbx($propusk['DOCTOR_SPECIALIZATION']) ?></span>
                            </div>

                            <!-- Категория -->
                            <div class="flex items-center text-sm">
                                <span class="text-gray-500 w-32">Категория:</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <?= htmlspecialcharsbx($propusk['DOCTOR_CATEGORY']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Кнопка-заглушка -->
                        <div class="mt-6">
                            <button class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-2 rounded-lg text-sm font-semibold transition-colors">
                                Подробнее
                            </button>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>
    </div>
</div>


<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");?>