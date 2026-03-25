BX(() => {
    BX.addCustomEvent('onTimeManDataRecieved', function (event) {

        let isDayClosed = event.STATE;
        let canResumeWorkDay = event.CAN_OPEN;

        if (isDayClosed === 'CLOSED' && (canResumeWorkDay === 'OPEN' || canResumeWorkDay === 'REOPEN')) {

            const timemanContainer = document.querySelector('.timeman-instant-container');

            if (timemanContainer) {

                timemanContainer.innerHTML = '';

                let panelButtonText = (canResumeWorkDay === 'REOPEN')
                    ? 'Возобновить рабочий день'
                    : 'Начать рабочий день';

                const customButton = BX.create('button', {
                    attrs: {
                        className: 'ui-btn ui-btn-success ui-btn-sm',
                        id: 'my_custom_timeman_start_btn'
                    },
                    style: {
                        width: '100%',
                        margin: '10px 0'
                    },
                    text: panelButtonText,
                    events: {
                        click: function(e) {
                            e.preventDefault();
                            openCustomTimeManModal(event);
                        }
                    }
                });

                timemanContainer.appendChild(customButton);
            }
        }
    });

    function openCustomTimeManModal(timeManData) {
        const popupId = 'custom_timeman_modal_window';

        let existingPopup = BX.PopupWindowManager.getPopupById(popupId);
        if (existingPopup) {
            existingPopup.destroy();
        }

        let isReopen = (timeManData.CAN_OPEN === 'REOPEN');

        let actionWord = isReopen ? 'Возобновить' : 'Начать';
        let titleText  = isReopen ? 'Возобновление рабочего дня' : 'Начало рабочего дня';
        let modalText  = isReopen ? 'Вы действительно хотите возобновить рабочий день?' : 'Вы действительно хотите начать рабочий день?';

        let myPopup = BX.PopupWindowManager.create(popupId, null, {
            content: '<div style="padding: 20px; font-size: 15px;">' + modalText + '</div>',
            width: 600, // ширина окна
            height: 400, // высота окна
            zIndex: 100, // z-index
            offsetTop: 0,
            offsetLeft: 0,
            closeIcon: {
                // объект со стилями для иконки закрытия, при null - иконки не будет
                opacity: 1
            },
            titleBar: titleText,
            overlay: { backgroundColor: 'black', opacity: 50 },
            closeByEsc: true,
            autoHide: true, // закрытие при клике вне окна
            draggable: true, // можно двигать или нет
            resizable: true, // можно ресайзить
            min_height: 100, // минимальная высота окна
            min_width: 100, // минимальная ширина окна
            lightShadow: true, // использовать светлую тень у окна
            angle: true, // появится уголок
            buttons: [
                new BX.PopupWindowButton({
                    text: actionWord,
                    className: 'ui-btn ui-btn-success',
                    events: {
                        click: function() {
                            const btn = this;

                            BX.addClass(btn.buttonNode, 'ui-btn-wait');

                            const action = timeManData.CAN_OPEN.toLowerCase(); // 'open' или 'reopen'
                            const siteId = BX.message('SITE_ID') || 's1';
                            const sessid = BX.bitrix_sessid();

                            const requestUrl = '/bitrix/tools/timeman.php?action=' + action + '&site_id=' + siteId + '&sessid=' + sessid;

                            BX.ajax({
                                url: requestUrl,
                                method: 'GET',
                                onsuccess: function(response) {
                                    BX.removeClass(btn.buttonNode, 'ui-btn-wait');
                                    btn.popupWindow.close();
                                    let notifyText = isReopen ? 'Рабочий день возобновлен!' : 'Рабочий день начат!';
                                    if (BX.UI && BX.UI.Notification) {
                                        BX.UI.Notification.Center.notify({
                                            content: notifyText,
                                            position: "top-right",
                                            autoHideDelay: 3000
                                        });
                                    } else {
                                        alert(notifyText);
                                    }

                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1000);
                                },
                                onfailure: function(err) {
                                    BX.removeClass(btn.buttonNode, 'ui-btn-wait');
                                    alert('Ошибка сети при отправке запроса. Попробуйте еще раз.');
                                }
                            });
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: 'Отмена',
                    className: 'ui-btn ui-btn-link',
                    events: {
                        click: function() {
                            this.popupWindow.close();
                        }
                    }
                })
            ]
        });
        myPopup.show();
    }
});


