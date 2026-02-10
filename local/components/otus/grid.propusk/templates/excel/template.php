<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arResult
 */

// 1. Очистка буфера
while (ob_get_level()) {
    ob_end_clean();
}

/**
 * ПОДГОТОВКА КОЛОНОК
 * $arResult['USED_COLUMNS'] содержит только ID: ["ID", "TITLE", ...]
 * Нам нужно найти их названия в $arResult['COLUMNS']
 */
$exportColumns = [];
foreach ($arResult['USED_COLUMNS'] as $columnId) {
    foreach ($arResult['COLUMNS'] as $fullColumn) {
        if ($fullColumn['id'] === $columnId) {
            $exportColumns[$columnId] = $fullColumn['name'];
            break;
        }
    }
}

// Если вдруг список выбранных колонок пуст, берем все доступные
if (empty($exportColumns)) {
    foreach ($arResult['COLUMNS'] as $fullColumn) {
        $exportColumns[$fullColumn['id']] = $fullColumn['name'];
    }
}

$spreadSheet = new Spreadsheet();
$activeSheet = $spreadSheet->getActiveSheet();

// Стили
$headersStyleArray = [
    'font' => ['bold' => true],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
];

$rowStyleArray = [
    'font' => ['italic' => true, 'color' => ['rgb' => '0000ff']],
];

// --- ШАГ 1: Заголовки (Используем красивые названия) ---
$colIndex = 1;
foreach ($exportColumns as $id => $humanName) {
    $cell = Coordinate::stringFromColumnIndex($colIndex) . '1';
    $activeSheet->setCellValue($cell, $humanName);
    $colIndex++;
}

$lastHeaderCol = $colIndex - 1;
if ($lastHeaderCol >= 1) {
    $range = 'A1:' . Coordinate::stringFromColumnIndex($lastHeaderCol) . '1';
    $activeSheet->getStyle($range)->applyFromArray($headersStyleArray);
}

// --- ШАГ 2: Данные (Строго по выбранным колонкам) ---
$rowIndex = 2;
foreach ($arResult['ROWS'] as $item) {
    $colIndex = 1;
    $rowData = $item['data'];

    // Итерируем только по ТЕМ колонкам, которые выбраны в гриде
    foreach ($exportColumns as $fieldId => $humanName) {
        $cell = Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
        $value = $rowData[$fieldId] ?? '';

        // Обработка объектов Даты
        if ($value instanceof \Bitrix\Main\Type\Date) {
            $value = $value->toString();
        }

        // На случай массивов
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        $activeSheet->setCellValue($cell, (string)$value);
        $colIndex++;
    }

    // Стиль для четных строк
    if ($rowIndex % 2 === 0) {
        $range = 'A' . $rowIndex . ':' . Coordinate::stringFromColumnIndex($lastHeaderCol) . $rowIndex;
        $activeSheet->getStyle($range)->applyFromArray($rowStyleArray);
    }

    $rowIndex++;
}

// --- ШАГ 3: Автоширина ---
for ($i = 1; $i <= $lastHeaderCol; $i++) {
    $colLetter = Coordinate::stringFromColumnIndex($i);
    $activeSheet->getColumnDimension($colLetter)->setAutoSize(true);
}

$activeSheet->setTitle('Пропуски');

// --- ВЫВОД ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="propusk_export_' . date('d_m_Y') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadSheet);
$writer->save('php://output');

die();