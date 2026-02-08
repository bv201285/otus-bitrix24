<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
?>

<div class="my-6">
    <?php if ($arResult['ERROR']): ?>
        <!-- Блок ошибки -->
        <div class="bg-red-50 border-l-4 border-red-400 p-4 max-w-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        <?= $arResult['ERROR'] ?>
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Карточка курса валют -->
        <div class="max-w-sm overflow-hidden bg-white rounded-xl shadow-lg border border-slate-100 transition-all hover:shadow-xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-2">
                        <div class="bg-indigo-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-slate-500 text-sm font-semibold uppercase tracking-wider">Курс валют</h3>
                    </div>
                </div>

                <div class="flex items-baseline space-x-2">
                    <span class="text-4xl font-extrabold text-slate-900">
                        <?= number_format($arResult['RATE'], 2, '.', ' ') ?>
                    </span>
                    <span class="text-xl font-medium text-slate-500">
                        <?= $arResult['BASE_CURRENCY'] ?>
                    </span>
                </div>

                <p class="mt-1 text-slate-400 text-sm">
                    за 1 <?= $arResult['TARGET_CURRENCY'] ?>
                </p>

                <div class="mt-6 pt-4 border-t border-slate-50 flex items-center justify-between">
                    <div class="flex items-center text-xs text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <?= $arResult['DATE'] ?>
                    </div>
                    <div class="text-xs font-bold text-indigo-500 uppercase">
                        <?= $arResult['TARGET_CURRENCY'] ?> / <?= $arResult['BASE_CURRENCY'] ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>