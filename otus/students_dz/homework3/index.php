<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use App\Models\Lists\DoctorsPropertyValuesTable;
use App\Models\Lists\ProceduresPropertyValuesTable;
use Bitrix\Main\Page\Asset;

Asset::getInstance()->addString('<script src="https://cdn.tailwindcss.com"></script>');

$colorSchemes = [
        ['grad' => 'from-blue-500 to-blue-600', 'text' => 'text-blue-600', 'soft' => 'bg-blue-50', 'btn' => 'bg-blue-600', 'hover' => 'hover:bg-blue-700'],
        ['grad' => 'from-purple-500 to-purple-600', 'text' => 'text-purple-600', 'soft' => 'bg-purple-50', 'btn' => 'bg-purple-600', 'hover' => 'hover:bg-purple-700'],
        ['grad' => 'from-emerald-500 to-emerald-600', 'text' => 'text-emerald-600', 'soft' => 'bg-emerald-50', 'btn' => 'bg-emerald-600', 'hover' => 'hover:bg-emerald-700'],
        ['grad' => 'from-orange-400 to-orange-500', 'text' => 'text-orange-600', 'soft' => 'bg-orange-50', 'btn' => 'bg-orange-600', 'hover' => 'hover:bg-orange-700'],
];

$currentDoctorId = (int)$_GET['ID'];
$isEdit = $_GET['EDIT'] === 'Y';
$addMode = $_GET['ADD'];

// --- ОБРАБОТКА СОХРАНЕНИЯ (UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update' && $currentDoctorId > 0) {
    $el = new CIBlockElement();
    $el->Update($currentDoctorId, ['NAME' => $_POST['name']]);
    CIBlockElement::SetPropertyValuesEx(
            $currentDoctorId,
            DoctorsPropertyValuesTable::getIblockId(),
            [
                    'specialization' => $_POST['specialization'],
                    'category' => $_POST['category'],
                    'procedures' => $_POST['procedures']
            ]
    );
    DoctorsPropertyValuesTable::clearPropertyMapCache();
    LocalRedirect("?ID=" . $currentDoctorId);
}

// --- ОБРАБОТКА ДОБАВЛЕНИЯ (CREATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
    if ($_POST['type'] === 'doctor') {
        DoctorsPropertyValuesTable::add([
                'NAME' => $_POST['name'],
                'specialization' => $_POST['specialization'],
                'category' => $_POST['category'],
                'procedures' => $_POST['procedures']
        ]);
        DoctorsPropertyValuesTable::clearPropertyMapCache();
    } elseif ($_POST['type'] === 'procedure') {
        ProceduresPropertyValuesTable::add(['NAME' => $_POST['name']]);
        ProceduresPropertyValuesTable::clearPropertyMapCache();
    }
    LocalRedirect("?");
}

// 1. ФОРМА ДОБАВЛЕНИЯ ВРАЧА
if ($addMode === 'doctor'):
    // Для формы добавления нам тоже нужен список всех процедур
    $allProcedures = ProceduresPropertyValuesTable::query()
            ->setSelect(['ID' => 'IBLOCK_ELEMENT_ID', 'NAME' => 'ELEMENT.NAME'])
            ->fetchAll();
    ?>
    <div class="max-w-2xl mx-auto py-10 px-4">
        <form method="POST" class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="type" value="doctor">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center mr-3 text-sm font-bold">+</span>
                    Новый врач
                </h2>
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ФИО Врача</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Специализация</label>
                        <input type="text" name="specialization" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
                        <input type="text" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 font-bold text-blue-600 uppercase text-xs tracking-widest">Выбор процедур (Ctrl + клик)</label>
                        <select name="procedures[]" multiple class="w-full px-4 py-2 border border-gray-300 rounded-xl h-48 focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php foreach ($allProcedures as $proc): ?>
                                <option value="<?=$proc['ID']?>"><?=$proc['NAME']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-8 py-4 flex items-center justify-between">
                <a href="?" class="text-gray-500 hover:text-gray-700 font-medium transition-colors">Отмена</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white hover:text-white px-8 py-2 rounded-xl font-bold shadow-lg transition-all transform hover:-translate-y-0.5">
                    Создать врача
                </button>
            </div>
        </form>
    </div>

<?php
// 2. ФОРМА ДОБАВЛЕНИЯ ПРОЦЕДУРЫ
elseif ($addMode === 'procedure'): ?>
    <div class="max-w-md mx-auto py-10 px-4">
        <form method="POST" class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="type" value="procedure">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <span class="w-8 h-8 bg-emerald-600 text-white rounded-full flex items-center justify-center mr-3 text-sm font-bold">+</span>
                    Новая процедура
                </h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Название процедуры</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                </div>
            </div>
            <div class="bg-gray-50 px-8 py-4 flex items-center justify-between">
                <a href="?" class="text-gray-500 hover:text-gray-700 font-medium transition-colors">Отмена</a>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white hover:text-white px-8 py-2 rounded-xl font-bold shadow-lg transition-all transform hover:-translate-y-0.5">
                    Создать процедуру
                </button>
            </div>
        </form>
    </div>

<?php
// 3. РЕДАКТИРОВАНИЕ ВРАЧА
elseif ($currentDoctorId > 0 && $isEdit):
    $doctor = DoctorsPropertyValuesTable::query()
            ->setSelect(['*', 'NAME' => 'ELEMENT.NAME', 'IBLOCK_ELEMENT_ID', 'PROCEDURES'])
            ->where('IBLOCK_ELEMENT_ID', $currentDoctorId)
            ->fetch();
    $allProcedures = ProceduresPropertyValuesTable::query()->setSelect(['ID' => 'IBLOCK_ELEMENT_ID', 'NAME' => 'ELEMENT.NAME'])->fetchAll();
    ?>
    <div class="max-w-2xl mx-auto py-10 px-4">
        <form method="POST" class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
            <input type="hidden" name="action" value="update">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Редактирование профиля</h2>
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ФИО Врача</label>
                        <input type="text" name="name" value="<?=htmlspecialcharsbx($doctor['NAME'])?>" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Специализация</label>
                        <input type="text" name="specialization" value="<?=htmlspecialcharsbx($doctor['specialization'])?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
                        <input type="text" name="category" value="<?=htmlspecialcharsbx($doctor['category'])?>" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 font-bold text-blue-600 uppercase text-xs tracking-widest">Процедуры (Ctrl + клик)</label>
                        <select name="procedures[]" multiple class="w-full px-4 py-2 border border-gray-300 rounded-xl h-48 focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php foreach ($allProcedures as $proc): ?>
                                <option value="<?=$proc['ID']?>" <?=(in_array($proc['ID'], $doctor['procedures'] ?: []) ? 'selected' : '')?>><?=$proc['NAME']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-8 py-4 flex items-center justify-between">
                <a href="?ID=<?=$currentDoctorId?>" class="text-gray-500 hover:text-gray-700 font-medium transition-colors">Отмена</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white hover:text-white px-8 py-2 rounded-xl font-bold shadow-lg transition-all transform hover:-translate-y-0.5">
                    Сохранить
                </button>
            </div>
        </form>
    </div>

<?php
// 4. ДЕТАЛЬНАЯ КАРТОЧКА
elseif ($currentDoctorId > 0):
    $doctor = DoctorsPropertyValuesTable::query()
            ->setSelect(['*', 'NAME' => 'ELEMENT.NAME', 'IBLOCK_ELEMENT_ID', 'PROCEDURES'])
            ->where('IBLOCK_ELEMENT_ID', $currentDoctorId)
            ->fetch();
    if ($doctor):
        $procedures = !empty($doctor['procedures']) ? ProceduresPropertyValuesTable::query()->setSelect(['NAME' => 'ELEMENT.NAME'])->whereIn('IBLOCK_ELEMENT_ID', $doctor['procedures'])->fetchAll() : [];
        ?>
        <div class="max-w-4xl mx-auto py-10 px-4">
            <div class="flex justify-between items-center mb-6">
                <a href="?" class="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors group">
                    <svg class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Назад к списку
                </a>
                <a href="?ID=<?=$currentDoctorId?>&EDIT=Y" class="bg-gray-100 hover:bg-gray-200 text-gray-700 hover:text-gray-900 px-5 py-2 rounded-xl font-bold transition-all flex items-center shadow-sm border border-gray-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    Редактировать
                </a>
            </div>
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                <div class="h-32 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
                <div class="px-8 pb-8">
                    <div class="relative flex justify-between items-end -mt-12 mb-6">
                        <div class="w-32 h-32 bg-gray-200 rounded-2xl border-4 border-white shadow-lg flex items-center justify-center text-4xl font-bold text-gray-400 overflow-hidden uppercase font-bold"><?= mb_substr($doctor['NAME'], 0, 1) ?></div>
                        <span class="px-4 py-2 rounded-full bg-emerald-50 text-emerald-700 text-sm font-bold mb-2"><?= $doctor['category'] ?: 'Специалист' ?></span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= $doctor['NAME'] ?></h1>
                    <p class="text-indigo-600 font-medium text-lg mb-6"><?= $doctor['specialization'] ?></p>
                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 italic">Выполняемые процедуры</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($procedures as $proc): ?>
                                <div class="flex items-center p-3 bg-gray-50 rounded-xl border border-gray-100 transition-colors hover:bg-gray-100">
                                    <div class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></div>
                                    <span class="text-gray-700 font-medium"><?= $proc['NAME'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif;

// 5. ОБЩИЙ СПИСОК
else:
    $doctors = DoctorsPropertyValuesTable::query()->setSelect(['IBLOCK_ELEMENT_ID', 'NAME' => 'ELEMENT.NAME', 'SPECIALIZATION', 'CATEGORY'])->fetchAll();
    ?>
    <div class="bg-gray-50 min-h-screen py-12 px-4">
        <div class="max-w-7xl mx-auto">
            <header class="mb-12">
                <div class="text-center mb-10">
                    <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-4 uppercase">Наши специалисты</h2>
                    <div class="flex flex-wrap justify-center gap-4 mt-8">
                        <a href="?ADD=doctor" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white hover:text-white px-8 py-4 rounded-2xl font-bold shadow-lg shadow-blue-200 transition-all transform hover:-translate-y-1">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Добавить врача
                        </a>
                        <a href="?ADD=procedure" class="flex items-center bg-emerald-600 hover:bg-emerald-700 text-white hover:text-white px-8 py-4 rounded-2xl font-bold shadow-lg shadow-emerald-200 transition-all transform hover:-translate-y-1">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Добавить процедуру
                        </a>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($doctors as $index => $doctor):
                    $scheme = $colorSchemes[$index % count($colorSchemes)];
                    ?>
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col hover:-translate-y-1">
                        <div class="h-2 w-full bg-gradient-to-r <?= $scheme['grad'] ?>"></div>
                        <div class="p-6 flex-grow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 <?= $scheme['soft'] ?> <?= $scheme['text'] ?> rounded-xl flex items-center justify-center font-bold text-xl uppercase"><?= mb_substr($doctor['NAME'], 0, 1) ?></div>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 bg-gray-50 px-2 py-1 rounded"><?= $doctor['category'] ?: 'Врач' ?></span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors mb-1"><?= $doctor['NAME'] ?></h3>
                            <p class="text-gray-500 text-sm mb-4 italic"><?= $doctor['specialization'] ?></p>
                        </div>
                        <div class="px-6 pb-6">
                            <a href="?ID=<?= $doctor['IBLOCK_ELEMENT_ID'] ?>"
                               class="block w-full text-center py-3 rounded-xl font-bold text-white hover:text-white shadow-md transition-all duration-200 transform hover:-translate-y-0.5 <?= $scheme['btn'] ?> <?= $scheme['hover'] ?> hover:shadow-lg">
                                Профиль врача
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>