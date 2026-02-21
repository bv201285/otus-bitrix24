<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arResult */

CJSCore::Init(['date']); // важно для BX.calendar
$uid = 'booking_' . (int)$arResult['DOCTOR_ID'] . '_' . (int)$arResult['PROCEDURE_ID'];
$formId = 'booking_modal_form_' . $uid;
?>
<div class="booking-modal" id="<?=htmlspecialcharsbx($uid)?>">
    <div class="booking-modal__header">
        <div class="booking-modal__title">Запись на приём</div>
        <div class="booking-modal__subtitle">
            <div><span class="booking-modal__label">Врач:</span> <?=htmlspecialcharsbx($arResult['DOCTOR_NAME'])?></div>
            <div><span class="booking-modal__label">Процедура:</span> <?=htmlspecialcharsbx($arResult['PROCEDURE_NAME'])?></div>
        </div>
    </div>

    <input type="hidden" name="doctorId" value="<?= (int)$arResult['DOCTOR_ID'] ?>">
    <input type="hidden" name="procedureId" value="<?= (int)$arResult['PROCEDURE_ID'] ?>">

    <form id="<?=htmlspecialcharsbx($formId)?>" >
        <div class="booking-modal__body">
            <div class="booking-field">
                <div class="booking-field__label">Дата и время</div>

                <div class="booking-date">
                    <input type="text"
                           name="date"
                           class="ui-ctl-element booking-date__input"
                           placeholder="Выберите дату и время"
                           autocomplete="off"
                           required
                           data-role="bookingDateField">

                    <button type="button"
                            class="ui-btn ui-btn-light-border booking-date__btn"
                            data-role="openCalendar">
                        Выбрать
                    </button>
                </div>

                <div class="booking-field__hint">Кликните «Выбрать» или в поле, чтобы открыть календарь.</div>
            </div>

            <div class="booking-field">
                <div class="booking-field__label">Имя клиента</div>
                <div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
                    <input type="text"
                           class="ui-ctl-element"
                           name="clientName"
                           value=""
                           placeholder="Например, Иван Иванов"
                           data-role="clientName"
                           required>

                </div>
            </div>
        </div>

        <div class="booking-modal__footer">
            <button type="button" class="ui-btn ui-btn-primary" data-role="submitBooking">
                Записаться
            </button>
            <button type="button" class="ui-btn ui-btn-light-border" data-role="cancelBooking">
                Отмена
            </button>
        </div>
    </form>
</div>