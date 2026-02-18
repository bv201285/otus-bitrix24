class PropuskGrid {

    constructor(options = {}) {
        this._addPropuskModal = null;

        this.gridId = options.gridId;
        this.doctors = options.doctors || {};
        this.buildings = options.buildings || {};
        this.signedParams = options.signedParameters;

    }

    /**
     * Возвращает экземпляр грида Битрикс
     */
    get instance() {
        return BX.Main.gridManager.getById(this.gridId)?.instance;
    }

    /**
     * Удаление выбранных элементов
     */
    async removeSelected() {
        const grid = this.instance;
        if (!grid) return;

        const selectedIds = grid.getRows().getSelectedIds();

        if (selectedIds.length === 0) return;

        try {
            // Включаем индикатор загрузки и затемняем таблицу
            grid.getLoader().show();
            grid.tableFade();
            console.log(grid.getLoader());

            const response = await BX.ajax.runComponentAction('otus:grid.propusk', 'deleteItems', {
                mode: 'class',
                data: {
                    ids: selectedIds
                }
            });

            if (response.status === 'success') {
                grid.getRows().unselectAll();
                grid.reload();
            } else {
                const error = response.errors.map(err => err.message).join('\n');
                alert(`Ошибка: ${error}`);
                // Если ошибка, нужно вернуть таблицу в активное состояние
                grid.tableUnfade();
            }
        } catch (error) {
            console.error('Grid Action Error:', error);
            grid.tableUnfade();
        } finally {
            // Выключаем индикатор в любом случае
            grid.getLoader().hide();
        }
    }

    /**
     * Удаление одного элемента по ID
     */
    async removeOne(id) {
        if (!id) return;

        // По желанию: используем стандартное подтверждение Битрикс
        if (!confirm('Вы действительно хотите удалить этот пропуск?')) {
            return;
        }

        const grid = this.instance;
        try {
            if (grid) {
                grid.getLoader().show();
                grid.tableFade();
            }

            const response = await BX.ajax.runComponentAction('otus:grid.propusk', 'deleteItems', {
                mode: 'class',
                data: {
                    ids: [id] // Передаем как массив из одного элемента, чтобы бэкенд не менялся
                }
            });

            if (response.status === 'success') {
                if (grid) {
                    grid.reload();
                }
            } else {
                const error = response.errors.map(err => err.message).join('\n');
                alert(`Ошибка: ${error}`);
                if (grid) grid.tableUnfade();
            }
        } catch (error) {
            console.error('Grid Action Error:', error);
            if (grid) grid.tableUnfade();
        } finally {
            if (grid) grid.getLoader().hide();
        }
    }

    /**
     * Экспорт в Excel с сохранением текущих фильтров
     */
    redirectToExcel() {
        // 1. Получаем текущие параметры из строки запроса
        const urlParams = new URLSearchParams(window.location.search);

        // 2. Добавляем (или обновляем) параметр EXPORT_MODE
        urlParams.set('EXPORT_MODE', 'Y');

        // 3. Формируем новый URL, используя текущий путь (pathname) и обновленные параметры
        const exportUrl = window.location.pathname + '?' + urlParams.toString();

        // 4. Перенаправляем (браузер начнет скачивание, так как компонент отдаст Excel-заголовки)
        window.open(exportUrl, '_self');
    }

    addPropusk(ajax = true) {
        const doctorsOptionsHtml = this._renderOptions(this.doctors, 'Выберите доктора');
        const buildingsOptionsHtml = this._renderOptions(this.buildings, 'Выберите здание');

        const popup = BX.PopupWindowManager.create('propusk-add-popup', null, {
            content: '' +
                '<form content="multipart/form-data" id="propusk-add-form">' +
                '<div class="px-3 py-4 space-y-4">' +
                '<div>' +
                '<label class="block text-sm font-medium text-gray-700">Наименование</label>' +
                '<input name="TITLE" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Наименование" required>' +
                '</div>' +
                '<div>' +
                '<label class="block text-sm font-medium text-gray-700">Доктор</label>' +
                '<select name="DOCTOR_ID" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>' +
                doctorsOptionsHtml +
                '</select>' +
                '</div>' +
                '<div>' +
                '<label class="block text-sm font-medium text-gray-700">Здание</label>' +
                '<select name="BUILDINGS_ID" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>' +
                buildingsOptionsHtml +
                '</select>' +
                '</div>' +
                '</div>' +
                '<input style="display:none;" type="submit" value="Применить">' +
                '</form>',
            darkMode: false,
            buttons: [
                new BX.PopupWindowButton({
                    text: "Добавить пропуск",
                    className: 'ui-btn ui-btn-primary',
                    events: {
                        click: async () => {
                            const form = document.getElementById('propusk-add-form');

                            if (!form.checkValidity()) {
                                form.reportValidity();
                            }else{
                                try {
                                    await this.createPropusk(form, ajax);
                                    popup.destroy();
                                } catch (e) {
                                    console.error(e);
                                }
                            }
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: "Закрыть",
                    className: 'ui-btn ui-btn-light-border',
                    events: {
                        click: function () {
                            this.popupWindow.destroy();
                        }
                    }
                })
            ]
        });
        popup.show();
    }

    addPropusk2(ajax = true) {
        const doctorsOptionsHtml = this._renderOptions(this.doctors, 'Выберите доктора');
        const buildingsOptionsHtml = this._renderOptions(this.buildings, 'Выберите здание');

        // если уже создана — просто показать
        if (this._addPropuskModal) {
            this._resetAddPropuskForm(this._addPropuskModal);
            this._showModal(this._addPropuskModal);
            return;
        }

        const modal = document.createElement('div');
        modal.id = 'addPropuskModal';
        modal.className = 'fixed inset-0 z-[9999] hidden';

        modal.innerHTML = `
        <div data-role="overlay" class="absolute inset-0 bg-black/50"></div>
    
        <div class="absolute inset-0 flex items-center justify-center p-4">
          <div class="w-full max-w-lg rounded-xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b px-5 py-4">
              <div class="text-lg font-semibold text-gray-900">Добавить пропуск</div>
              <button type="button" data-role="close"
                class="rounded-md px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900">✕</button>
            </div>
    
            <form id="propusk-add-form2">
              <div class="px-5 py-4 space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Наименование</label>
                    <input name="TITLE" required type="text"
                      class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm
                             focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                      placeholder="Наименование">
                  </div>
        
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Доктор</label>
                    <select name="DOCTOR_ID" required
                      class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                      ${doctorsOptionsHtml}
                    </select>
                  </div>
        
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Здание</label>
                    <select name="BUILDINGS_ID" required
                      class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                      ${buildingsOptionsHtml}
                    </select>
                  </div>
              </div>              
            </form>
    
            <div class="flex items-center justify-end gap-2 border-t px-5 py-4">
              <button type="button" data-role="cancel"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Отмена
              </button>
              <button type="button" data-role="submit"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Сохранить
              </button>
            </div>
          </div>
        </div>
      `;

        document.body.appendChild(modal);

        const overlay = modal.querySelector('[data-role="overlay"]');
        const closeBtn = modal.querySelector('[data-role="close"]');
        const cancelBtn = modal.querySelector('[data-role="cancel"]');
        const submitBtn = modal.querySelector('[data-role="submit"]');
        const form = modal.querySelector('#propusk-add-form2');

        const close = () => this._hideModal(modal);

        overlay.addEventListener('click', close);
        closeBtn.addEventListener('click', close);
        cancelBtn.addEventListener('click', close);

        const onKeyDown = (e) => {
            if (e.key === 'Escape') close();
        };

        submitBtn.addEventListener('click', async () => {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            try {
                await this.createPropusk(form, ajax);
                close();
            } catch (e) {
                console.error(e);
            }
        });

        modal._onKeyDown = onKeyDown;

        this._addPropuskModal = modal;
        this._resetAddPropuskForm(modal);
        this._showModal(modal);
    }

    async createPropusk(form, ajax) {
        const data = Object.fromEntries(new FormData(form).entries());

        let mode = 'ajax';
        if(!ajax){
            mode = 'class';
        }

        try {
            const response = await BX.ajax.runComponentAction('otus:grid.propusk', 'addPropusk', {
                mode,
                data: {data},
                signedParameters: this.signedParams
            });

            const id = response?.data?.PROPUSK_ID;

            //console.log(response);

            BX.UI.Notification.Center.notify({
                content: `Добавлен пропуск с ID=${id}`,
            });

            const grid = this.instance;
            if (grid) grid.reload();

            return id; // успех
        } catch (reject) {
            //console.log(reject)

            // Приводим ошибку к читаемому виду (Bitrix обычно кидает объект с errors)
            const msg =
                (reject?.errors && Array.isArray(reject.errors) && reject.errors.length)
                    ? reject.errors.map(e => e.message).join('\n')
                    : (reject?.message || 'Ошибка добавления');

            alert(msg);

            // важно: пробрасываем ошибку дальше, чтобы click: async мог НЕ закрывать popup
            throw reject;
        }
    }





    _showModal(modal) {
        modal.classList.remove('hidden');
        document.addEventListener('keydown', modal._onKeyDown);
    }

    _hideModal(modal) {
        modal.classList.add('hidden');
        document.removeEventListener('keydown', modal._onKeyDown);
    }

    _resetAddPropuskForm(modal) {
        const form = modal.querySelector('#propusk-add-form2');
        if (!form) return;

        form.reset(); // сбросит input/select к первому option/пустому

        // если хотите явно поставить плейсхолдер (option value=""):
        const doctor = form.querySelector('[name="DOCTOR_ID"]');
        const building = form.querySelector('[name="BUILDINGS_ID"]');
        if (doctor) doctor.value = '';
        if (building) building.value = '';

        // убрать возможные browser validation popups/подсветки
        const title = form.querySelector('[name="TITLE"]');
        if (title) title.focus(); // опционально: фокус в первое поле
    }

    _renderOptions(map, placeholder) {
        // map: {id: name}
        let html = `<option value="">${placeholder}</option>`;
        Object.entries(map).forEach(([id, name]) => {
            html += `<option value="${this._escapeHtml(id)}">${this._escapeHtml(name)}</option>`;
        });
        return html;
    }

    _escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}
