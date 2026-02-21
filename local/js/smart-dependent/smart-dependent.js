(function () {
    console.log('SDC loaded');

    function initAllFromQueue() {
        window.SDC_InitQueue = window.SDC_InitQueue || [];

        const queue = window.SDC_InitQueue.slice();
        window.SDC_InitQueue = [];

        queue.forEach((cfg) => {
            const ok = initOne(cfg);
            if (!ok) {
                // если root ещё не в DOM — вернём в очередь
                window.SDC_InitQueue.push(cfg);
            }
        });
    }

    function initOne(cfg) {
        if (!cfg || !cfg.rootId) return true;

        const root = document.getElementById(cfg.rootId);
        if (!root) return false;

        if (root.dataset.sdcInited === 'Y') return true;
        root.dataset.sdcInited = 'Y';

        const store = root.querySelector('[data-role="store"]');
        const modeSel = root.querySelector('[data-role="mode"]');
        const cont = root.querySelector('[data-role="valueContainer"]');
        const stored = cfg.stored || {};

        if (!store || !modeSel || !cont) return true;

        render(modeSel.value || stored.mode || '', stored, cont, store);

        modeSel.addEventListener('change', () => {
            render(modeSel.value, {}, cont, store);
        });

        return true;
    }

    function render(mode, stored, cont, store) {
        cont.innerHTML = '';
        const setStore = (payload) => store.value = JSON.stringify(payload);

        if (!mode) {
            cont.innerHTML = '<div style="color:#a8adb4;font-size:12px;">Сначала выберите тип</div>';
            setStore({ mode: '', value: '' });
            return;
        }

        if (mode.indexOf('iblock:') === 0) {
            const iblockApi = mode.split(':')[1] || '';

            const select = document.createElement('select');
            select.className = 'ui-ctl-element';
            select.innerHTML = '<option value="">Загрузка...</option>';

            const wrap = document.createElement('div');
            wrap.className = 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100';
            wrap.innerHTML = '<div class="ui-ctl-after ui-ctl-icon-angle"></div>';
            wrap.appendChild(select);
            cont.appendChild(wrap);

            BX.ajax.runComponentAction('otus:smart.dependent', 'getElements', {
                mode: 'class',
                data: { iblockApi }
            }).then((res) => {
                const items = res?.data?.items || [];
                let html = '<option value="">Выберите элемент</option>';
                items.forEach(it => html += `<option value="${esc(it.id)}">${esc(it.name)}</option>`);
                select.innerHTML = html;

                if (stored && String(stored.value || '') !== '' && String(stored.iblock || '') === iblockApi) {
                    select.value = String(stored.value);
                }

                setStore({ mode, iblock: iblockApi, value: select.value || '' });
            }).catch((err) => {
                console.error('SDC getElements error', err);
                select.innerHTML = '<option value="">Ошибка</option>';
                setStore({ mode, iblock: iblockApi, value: '' });
            });

            select.addEventListener('change', () => {
                setStore({ mode, iblock: iblockApi, value: select.value || '' });
            });

            return;
        }

        const input = document.createElement('input');
        input.className = 'ui-ctl-element';
        input.value = (stored && stored.mode === mode) ? (stored.value || '') : '';

        const wrap = document.createElement('div');
        wrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100';
        wrap.appendChild(input);
        cont.appendChild(wrap);

        if (mode === 'text') { input.type = 'text'; input.placeholder = 'Введите значение'; }
        else if (mode === 'email') { input.type = 'email'; input.placeholder = 'name@example.com'; }
        else if (mode === 'url') { input.type = 'url'; input.placeholder = 'https://example.com'; }
        else { input.type = 'text'; }

        setStore({ mode, value: input.value });
        input.addEventListener('input', () => setStore({ mode, value: input.value }));
    }

    function esc(s) {
        return String(s)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    // ---- ИНИЦИАЛИЗАЦИЯ ----

    // guard на уровне глобального хэндлера
    if (!window.__sdcInited) {
        window.__sdcInited = true;
    }

    // Публичная функция, которую вызываем из HTML после push
    window.SDC_InitAll = initAllFromQueue;

    // Важно: запускаем сразу, не дожидаясь BX.ready
    initAllFromQueue();

    // И на всякий случай ещё раз на ready
    if (window.BX && BX.ready) {
        BX.ready(initAllFromQueue);
    }
})();