// /local/js/booking/booking-procedure.js
// Самоинициализация + защита от повторной инициализации (guard-флаг)

(function () {
    BX.ready(function () {
        // guard: если файл подключили/выполнился несколько раз (из разных ячеек) — не дублируем обработчики
        if (window.__bookingProcedureInited) return;
        window.__bookingProcedureInited = true;

        // делегирование клика на весь документ — работает даже если грид перерисовался ajax-ом
        document.addEventListener('click', onProcedureClick);
    });

    function onProcedureClick(e) {
        const link = e.target.closest('.booking-procedure-link');
        if (!link) return;

        const doctorId = parseInt(link.dataset.doctorId, 10);
        const procedureId = parseInt(link.dataset.procedureId, 10);

        if (!doctorId || !procedureId) {
            console.error('booking: no doctorId/procedureId', { doctorId, procedureId, link });
            return;
        }
        openBookingPopup({ doctorId, procedureId });
    }

    function openBookingPopup({ doctorId, procedureId }) {
        // Грузим HTML формы из компонентного action
        BX.ajax.runComponentAction('otus:booking.procedure', 'getForm', {
            mode: 'class',
            data: { doctorId, procedureId }
        }).then(function (response) {
            const html = response?.data?.html || '';
            if (!html) throw new Error('Empty form html');

            const popupId = `booking_popup_${doctorId}_${procedureId}`;

            // если попап с таким ID уже есть — уничтожим, чтобы не копились старые обработчики
            const existing = BX.PopupWindowManager.getPopupById(popupId);
            if (existing) existing.destroy();

            const popup = new BX.PopupWindow(popupId, null, {
                content: html,
                closeIcon: true,
                autoHide: true,
                overlay: true,
                width: 480
            });

            popup.show();

            const container = popup.contentContainer;
            const submitBtn = container.querySelector('[data-role="submitBooking"]');

            // закрытие по "Отмена"
            const cancelBtn = container.querySelector('[data-role="cancelBooking"]');
            if (cancelBtn) cancelBtn.addEventListener('click', () => popup.close());

            if (submitBtn) {
                submitBtn.addEventListener('click', function () {
                    submitBooking(container, popup);
                    //alert('123');
                });
            }



            initCalendar(container);

        }).catch(function (err) {
            console.error(err);
            notify('Не удалось открыть форму записи');
        });
    }

    function submitBooking(container, popup) {

        const doctorId = parseInt(container.querySelector('input[name="doctorId"]')?.value, 10);
        const procedureId = parseInt(container.querySelector('input[name="procedureId"]')?.value, 10);
        const date = container.querySelector('input[name="date"]')?.value || '';
        const clientName = (container.querySelector('input[name="clientName"]')?.value || '').trim();
        const form = container.querySelector('form');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (!date) {
            notify('Выберите дату и время');
            return;
        }
        if (!clientName) {
            notify('Введите имя клиента');
            return;
        }

        // Блокируем кнопку на время запроса (если есть)
        const submitBtn = container.querySelector('[data-role="submitBooking"]');
        if (submitBtn) submitBtn.classList.add('ui-btn-wait');

        BX.ajax.runComponentAction('otus:booking.procedure', 'createBooking', {
            mode: 'class',
            data: { doctorId, procedureId, date, clientName }
        }).then(function () {
            popup.close();
            notify('Бронирование создано');

            if (submitBtn) submitBtn.classList.remove('ui-btn-wait');

            setTimeout(() => {
                window.location.href = '/services/lists/20/view/0/';
            }, 300);

        }).catch(function (err) {
            console.error(err);
            const msg = err?.errors?.[0]?.message || 'Ошибка';
            notify(msg);

            if (submitBtn) submitBtn.classList.remove('ui-btn-wait');
        });
    }

    function notify(text) {
        if (BX.UI?.Notification?.Center) {
            BX.UI.Notification.Center.notify({ content: text });
        } else {
            alert(text);
        }
    }

    function initCalendar(container) {
        const dateField = container.querySelector('[data-role="bookingDateField"]');
        const openBtn = container.querySelector('[data-role="openCalendar"]');

        if (!dateField) return;

        dateField.addEventListener('keydown', (e) => e.preventDefault());

        const open = () => {
            // BX.calendar читает/пишет значение в field в "формате сайта"
            // и может показывать выбор времени, если bTime:true
            BX.calendar({
                node: dateField,
                field: dateField,
                bTime: true,
                bHideTime: false,
                // callback_after: function(date){ console.log(date); } // если нужно
            });
        };

        dateField.addEventListener('click', open);
        if (openBtn) openBtn.addEventListener('click', open);
    }
})();