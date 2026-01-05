<?php

global $APPLICATION;

use Bitrix\Main\Page\Asset;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;

use App\Models\Lists\DoctorsPropertyValuesTable;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("ДЗ #3: Связывание моделей");

Asset::getInstance()->addString('<script src="https://cdn.tailwindcss.com"></script>');



$doctorsCollection = DoctorsPropertyValuesTable::query()
        ->setSelect([
                '*',
                'PROCEDURES_COLLECTION',
                'PROCEDURES_COLLECTION.ELEMENT'
        ])
        ->fetchCollection();

//dd($doctorsCollection);

foreach ($doctorsCollection as $doctor) {
    foreach ($doctor->Get('PROCEDURES_COLLECTION')->getAll() as $item) {
        dump($item->getElement()->getName());
    }

}




// Массив цветовых схем Tailwind
$colorSchemes = [
        [
                'bg' => 'from-blue-500 to-blue-700',
                'text' => 'text-blue-600',
                'hoverText' => 'group-hover:text-blue-600',
                'btnHover' => 'hover:bg-blue-600',
                'shadow' => 'shadow-blue-100'
        ],
        [
                'bg' => 'from-purple-500 to-purple-700',
                'text' => 'text-purple-600',
                'hoverText' => 'group-hover:text-purple-600',
                'btnHover' => 'hover:bg-purple-600',
                'shadow' => 'shadow-purple-100'
        ],
        [
                'bg' => 'from-emerald-500 to-emerald-700',
                'text' => 'text-emerald-600',
                'hoverText' => 'group-hover:text-emerald-600',
                'btnHover' => 'hover:bg-emerald-600',
                'shadow' => 'shadow-emerald-100'
        ],
        [
                'bg' => 'from-orange-400 to-orange-600',
                'text' => 'text-orange-600',
                'hoverText' => 'group-hover:text-orange-600',
                'btnHover' => 'hover:bg-orange-600',
                'shadow' => 'shadow-orange-100'
        ],
        [
                'bg' => 'from-rose-500 to-rose-700',
                'text' => 'text-rose-600',
                'hoverText' => 'group-hover:text-rose-600',
                'btnHover' => 'hover:bg-rose-600',
                'shadow' => 'shadow-rose-100'
        ],
];
?>
<h1 class="text-lg font-semibold"><?php $APPLICATION->ShowTitle() ?></h1>



<div class="bg-gray-50 py-12 px-4 min-h-screen">
    <div class="max-w-7xl mx-auto">

        <!-- Кнопки управления -->
        <div class="flex flex-wrap items-center gap-4 mb-10">
            <button class="flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 active:scale-95">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Добавить врача
            </button>
            <button class="flex items-center px-6 py-3 bg-white text-gray-900 font-bold rounded-2xl border border-gray-200 hover:bg-gray-50 transition-all shadow-sm active:scale-95">
                <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Добавить процедуру
            </button>
        </div>

        <!-- Сетка карточек с PHP циклом -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

            <?php foreach ($doctorsCollection as $i => $doctor):
                // Выбираем схему по остатку от деления
                $scheme = $colorSchemes[$i % count($colorSchemes)];
                ?>
                <div class="group bg-white rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-500 border border-gray-100 overflow-hidden flex flex-col h-full">

                    <!-- Шапка с динамическим градиентом -->
                    <div class="relative h-40 bg-gradient-to-br <?= $scheme['bg'] ?> flex items-center justify-center overflow-hidden">
                        <div class="absolute w-32 h-32 bg-white/10 rounded-full -top-10 -right-10"></div>
                        <svg class="w-20 h-20 text-white/90 relative z-10 group-hover:scale-110 transition-transform duration-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v2m0 0v2m0-2h2m-2 0H10" />
                        </svg>
                    </div>

                    <div class="p-8 flex flex-col flex-grow text-center">
                        <!-- Динамический цвет текста специализации -->
                        <span class="<?= $scheme['text'] ?> text-[10px] font-black uppercase tracking-[0.2em] mb-3">
                            <?= $doctor->getSpecialization()->getValue() ?>
                        </span>

                        <!-- Динамический цвет при наведении на имя -->
                        <h3 class="text-xl font-bold text-gray-900 mb-1 <?= $scheme['hoverText'] ?> transition-colors">
                            <?= $doctor->getName() ?>
                        </h3>

                        <div class="flex items-center justify-center space-x-1 mt-3 mb-6">
                            <span class="text-xs font-medium text-gray-400"><?= $doctor->getCategory()->getValue() ?></span>
                        </div>

                        <!-- Динамический цвет кнопки при наведении -->
                        <div class="mt-auto">
                            <button class="w-full py-3 px-4 bg-gray-900 text-white rounded-2xl font-bold text-sm <?= $scheme['btnHover'] ?> transition-colors shadow-lg <?= $scheme['shadow'] ?>">
                                Записаться
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>


<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
