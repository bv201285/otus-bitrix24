(function () {

    function initAllFromQueue() {
        window.PI_InitQueue = window.PI_InitQueue || [];

        const queue = window.PI_InitQueue.slice();
        window.PI_InitQueue = [];

        queue.forEach((cfg) => {
            const ok = initOne(cfg);
            if (!ok) window.PI_InitQueue.push(cfg);
        });
    }

    function initOne(cfg) {
        if (!cfg || !cfg.rootId) return true;

        const root = document.getElementById(cfg.rootId);
        if (!root) return false;

        if (root.dataset.piInited === 'Y') return true;
        root.dataset.piInited = 'Y';

        const store = root.querySelector('[data-role="store"]');
        const channelSel = root.querySelector('[data-role="channel"]');
        const cont = root.querySelector('[data-role="valueContainer"]');
        const stored = cfg.stored || {};

        if (!store || !channelSel || !cont) return true;

        render(channelSel.value || stored.channel || '', stored, cont, store);

        channelSel.addEventListener('change', () => {
            render(channelSel.value, {}, cont, store);
        });

        return true;
    }

    function setStore(store, payload) {
        store.value = JSON.stringify(payload);
    }

    function esc(s) {
        return String(s)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function render(channel, stored, cont, store) {
        cont.innerHTML = '';

        if (!channel) {
            cont.innerHTML = '<div style="color:#a8adb4;font-size:12px;">Сначала выберите канал</div>';
            setStore(store, { channel: '', type: '', value: '' });
            return;
        }

        // Каналы без второго значения
        if (channel === 'site' || channel === 'ebase') {
            const input = document.createElement('input');
            input.className = 'ui-ctl-element';
            input.type = 'text';
            input.disabled = true;
            input.value = '';
            input.placeholder = 'Недоступно для этого канала';

            const wrap = document.createElement('div');
            wrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100';
            wrap.appendChild(input);
            cont.appendChild(wrap);

            setStore(store, { channel, type: 'none', value: '' });
            return;
        }

        // Выставка -> select marketingActivities
        if (channel === 'exhibition') {
            renderRemoteSelect({
                cont,
                store,
                component: 'otus:main.field.primary_interest',
                action: 'getMarketingActivities',
                type: 'marketingActivities',
                placeholder: 'Выберите мероприятие',
                stored,
                channel
            });
            return;
        }

        // Рекомендация -> CRM company autocomplete
        if (channel === 'recommendation') {
            renderRemoteAutocomplete({
                cont,
                store,
                component: 'otus:main.field.primary_interest',
                searchAction: 'searchCompanies',
                titleAction: 'getCompanyTitle',
                type: 'crmCompany',
                placeholder: 'Начните вводить компанию',
                stored,
                channel
            });
            return;
        }

        // Сотрудник -> user autocomplete
        if (channel === 'employee') {
            renderRemoteAutocomplete({
                cont,
                store,
                component: 'otus:main.field.primary_interest',
                searchAction: 'searchUsers',
                titleAction: 'getUserFio',
                type: 'user',
                placeholder: 'Начните вводить сотрудника',
                stored,
                channel
            });
            return;
        }

        cont.innerHTML = '<div style="color:#a8adb4;font-size:12px;">Неизвестный канал</div>';
        setStore(store, { channel, type: '', value: '' });
    }

    function renderRemoteSelect({ cont, store, component, action, type, placeholder, stored, channel }) {
        const select = document.createElement('select');
        select.className = 'ui-ctl-element';
        select.innerHTML = '<option value="">Загрузка...</option>';

        const wrap = document.createElement('div');
        wrap.className = 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100';
        wrap.innerHTML = '<div class="ui-ctl-after ui-ctl-icon-angle"></div>';
        wrap.appendChild(select);
        cont.appendChild(wrap);

        BX.ajax.runComponentAction(component, action, { mode: 'class', data: {} })
            .then((res) => {
                const items = res?.data?.items || [];
                let html = `<option value="">${esc(placeholder)}</option>`;
                items.forEach(it => html += `<option value="${esc(it.id)}">${esc(it.name)}</option>`);
                select.innerHTML = html;

                if (stored && stored.channel === channel && stored.type === type && String(stored.value || '') !== '') {
                    select.value = String(stored.value);
                }

                setStore(store, { channel, type, value: select.value || '' });
            })
            .catch((err) => {
                console.error('PI action error', action, err);
                select.innerHTML = '<option value="">Ошибка</option>';
                setStore(store, { channel, type, value: '' });
            });

        select.addEventListener('change', () => {
            setStore(store, { channel, type, value: select.value || '' });
        });
    }

    /**
     * Autocomplete:
     * - один input
     * - dropdown совпадений
     * - по выбору: input.value = name, store.value = id
     * - при инициализации если есть ID -> тянем title/fio отдельным action и заполняем input
     */
    function renderRemoteAutocomplete({
                                          cont,
                                          store,
                                          component,
                                          searchAction,
                                          titleAction,
                                          type,
                                          placeholder,
                                          stored,
                                          channel
                                      }) {
        const input = document.createElement('input');
        input.className = 'ui-ctl-element';
        input.type = 'text';
        input.placeholder = placeholder;
        input.autocomplete = 'off';

        const inputWrap = document.createElement('div');
        inputWrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100';
        inputWrap.appendChild(input);

        const dd = document.createElement('div');
        dd.className = 'pi-ac__dd';
        dd.style.display = 'none';

        const box = document.createElement('div');
        box.className = 'pi-ac';
        box.appendChild(inputWrap);
        box.appendChild(dd);
        cont.appendChild(box);

        let selectedId = '';
        let lastItems = [];
        let suppressInputHandler = false; // чтобы не триггерить поиск при программной подстановке

        function closeDd() {
            dd.style.display = 'none';
            dd.innerHTML = '';
        }

        function openDd(items) {
            lastItems = items || [];
            if (!lastItems.length) {
                closeDd();
                return;
            }

            dd.innerHTML = '';
            lastItems.forEach(it => {
                const item = document.createElement('div');
                item.className = 'pi-ac__item';
                item.textContent = it.name;

                item.addEventListener('mousedown', (e) => {
                    // mousedown, чтобы blur не схлопнул dropdown раньше клика
                    e.preventDefault();

                    selectedId = String(it.id || '');
                    suppressInputHandler = true;
                    input.value = String(it.name || '');
                    suppressInputHandler = false;

                    setStore(store, { channel, type, value: selectedId });
                    closeDd();
                });

                dd.appendChild(item);
            });

            dd.style.display = 'block';
        }

        function setValueText(text) {
            suppressInputHandler = true;
            input.value = text;
            suppressInputHandler = false;
        }

        // --- init from stored id: подтянуть title/fio ---
        if (stored && stored.channel === channel && stored.type === type && String(stored.value || '') !== '') {
            selectedId = String(stored.value);
            setStore(store, { channel, type, value: selectedId });

            // временно покажем #ID, потом заменим на имя
            setValueText('#' + selectedId);

            BX.ajax.runComponentAction(component, titleAction, {
                mode: 'class',
                data: { id: selectedId }
            }).then((res) => {
                const title = (res?.data?.title || '').trim();
                if (title) setValueText(title);
            }).catch((err) => {
                console.error('PI get title error', titleAction, err);
            });
        } else {
            setStore(store, { channel, type, value: '' });
        }

        let t = null;
        input.addEventListener('input', () => {
            if (suppressInputHandler) return;

            // если пользователь редактирует строку — сбрасываем выбранный ID
            selectedId = '';
            setStore(store, { channel, type, value: '' });

            const q = input.value.trim();
            clearTimeout(t);

            if (q.length < 2) { // минимальная длина запроса
                closeDd();
                return;
            }

            t = setTimeout(() => {
                BX.ajax.runComponentAction(component, searchAction, {
                    mode: 'class',
                    data: { q }
                }).then((res) => {
                    const items = res?.data?.items || [];
                    openDd(items);
                }).catch((err) => {
                    console.error('PI search error', searchAction, err);
                    closeDd();
                });
            }, 250);
        });

        input.addEventListener('focus', () => {
            if (lastItems && lastItems.length) openDd(lastItems);
        });

        input.addEventListener('blur', () => {
            setTimeout(() => closeDd(), 150);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDd();
        });
    }

    // ---- init ----
    window.PI_InitAll = initAllFromQueue;

    initAllFromQueue();
    if (window.BX && BX.ready) BX.ready(initAllFromQueue);

})();