
class PropuskGrid {
    constructor(gridId) {
        this.gridId = gridId;
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

            /*const response = await BX.ajax.runComponentAction('otus:grid.propusk', 'deleteItems', {
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
            }*/
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


}
