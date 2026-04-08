BX.ready(function () {

    let currentPrettyLabel = '';

    function esc(s) {
        return String(s).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    }

    // Сохранение в скрытый инпут
    function setFilterStore(store, payload, labelText = '') {
        currentPrettyLabel = labelText;

        let newValue = "";
        if (!payload.channel) {
            newValue = "";
            currentPrettyLabel = "";
        } else if (!payload.value) {
            newValue = `%"channel":"${payload.channel}"%`;
        } else {
            newValue = `%"channel":"${payload.channel}","type":"${payload.type}","value":"${payload.value}"%`;
        }

        // ПРОВЕРКА: Изменилось ли значение?
        const isValueChanged = (store.value !== newValue);

        // Устанавливаем новое значение
        store.value = newValue;

        // ВАЖНО: Дергаем ядро Битрикса ТОЛЬКО если значение действительно поменялось,
        // чтобы не прерывать стандартную работу пресетов (например, "Все компании")
        if (isValueChanged && window.BX) {
            BX.fireEvent(store, 'input');
            BX.fireEvent(store, 'change');
        }

        safeUpdateToolbarTag();
    }

    // МАГИЯ ДЛЯ ТУЛБАРА: Создание искусственного тега
    function updateToolbarTag() {
        const searchContainer = document.querySelector('.main-ui-filter-search');
        if (!searchContainer) return;

        // 1. Ищем родной тег Битрикса и ЖЕСТКО СКРЫВАЕМ ЕГО
        const squares = searchContainer.querySelectorAll('.main-ui-square:not(.pi-custom-square)');
        squares.forEach(sq => {
            const dataItem = sq.getAttribute('data-item') || '';
            if (dataItem.includes('UF_PRIMARY_INTEREST')) {
                // Скрываем стандартный тег, чтобы он не конфликтовал с нашим
                sq.style.setProperty('display', 'none', 'important');
            }
        });

        // 2. Управляем нашим кастомным тегом
        let customTag = searchContainer.querySelector('.pi-custom-square');

        // Убрали проверку !nativeTagFound. Теперь наш тег рисуется ВСЕГДА, когда есть значение.
        if (currentPrettyLabel) {
            if (!customTag) {
                customTag = document.createElement('div');
                customTag.className = 'main-ui-filter-search-square main-ui-square pi-custom-square';
                customTag.style.cssText = 'display: flex !important; align-items: center; justify-content: space-between; padding-right: 0;';

                // ИСПОЛЬЗУЕМ КАСТОМНЫЙ КРЕСТИК
                // Обратите внимание на stroke="#ffffff" и opacity: 0.7
                customTag.innerHTML = `
                    <div class="main-ui-square-item" style="flex: 1; padding-right: 5px;"></div>
                    <div class="pi-custom-delete" style="display: flex; align-items: center; justify-content: center; width: 20px; height: 100%; cursor: pointer; opacity: 0.7; transition: opacity 0.2s; flex-shrink: 0;">
                        <svg viewBox="0 0 10 10" width="9" height="9" style="display: block;">
                            <path d="M1 1 l8 8 m0 -8 l-8 8" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                    </div>
                `;

                const delBtn = customTag.querySelector('.pi-custom-delete');
                // Делаем крестик полностью ярким при наведении (как в стандарте)
                delBtn.onmouseenter = () => delBtn.style.opacity = '1';
                // Возвращаем легкую прозрачность, когда мышь убрана
                delBtn.onmouseleave = () => delBtn.style.opacity = '0.7';

                const searchInput = searchContainer.querySelector('.main-ui-filter-search-filter');
                if (searchInput) {
                    searchContainer.insertBefore(customTag, searchInput);
                }

                if (delBtn) {
                    const killEvent = (e) => { e.preventDefault(); e.stopPropagation(); };
                    delBtn.addEventListener('mousedown', killEvent);
                    delBtn.addEventListener('mouseup', killEvent);

                    delBtn.addEventListener('click', function(e) {
                        killEvent(e);
                        currentPrettyLabel = '';
                        customTag.remove();

                        // Даем команду Битриксу: Очисти только наше поле и примени поиск!
                        if (window.BX && BX.Main && BX.Main.filterManager) {
                            const fm = BX.Main.filterManager.getList()[0];
                            if (fm) fm.getApi().extendFilter({ 'UF_PRIMARY_INTEREST': '' });
                        }
                    });
                }
            }

            // Обновляем текст внутри нашего кастомного тега
            const itemNode = customTag.querySelector('.main-ui-square-item');
            if (itemNode) {
                const expectedText = 'Первичный интерес: ' + currentPrettyLabel;
                if (itemNode.textContent !== expectedText) itemNode.textContent = expectedText;
            }

        } else if (customTag) {
            customTag.remove();
        }
    }

    // БЕЗОПАСНЫЙ НАБЛЮДАТЕЛЬ ЗА ТУЛБАРОМ
    let tagObserver;
    const searchBox = document.querySelector('.main-ui-filter-search');

    function safeUpdateToolbarTag() {
        if (tagObserver) tagObserver.disconnect();
        updateToolbarTag();
        if (tagObserver && searchBox) {
            tagObserver.observe(searchBox, { childList: true, subtree: true, characterData: true });
        }
    }

    if (searchBox) {
        tagObserver = new MutationObserver(() => safeUpdateToolbarTag());
        tagObserver.observe(searchBox, { childList: true, subtree: true, characterData: true });
    }

    // ОЧИСТКА НАШЕГО ТЕГА, ЕСЛИ ПОЛЬЗОВАТЕЛЬ УДАЛИЛ ПРЕСЕТ ("Все компании")
    BX.addCustomEvent('BX.Main.Filter:apply', function(filterId) {
        setTimeout(() => {
            const fm = window.BX?.Main?.filterManager?.getById(filterId) || (window.BX?.Main?.filterManager?.getList() || [])[0];
            if (fm) {
                const values = fm.getFilterFieldsValues();
                // Если ядро говорит, что наше поле пустое
                if (!values['UF_PRIMARY_INTEREST']) {
                    currentPrettyLabel = '';
                    const customTag = document.querySelector('.pi-custom-square');
                    if (customTag) customTag.remove();

                    // Обнуляем UI в самом окне фильтра
                    const channelSel = document.querySelector('[data-role="filter-channel"]');
                    // ИСПРАВЛЕНИЕ: Ищем оригинальный инпут по его name, так как data-role у него нет
                    const store = document.querySelector('input[name="UF_PRIMARY_INTEREST"]');
                    const valueCont = document.querySelector('[data-role="filter-valueContainer"]');

                    if (channelSel && valueCont && store) {
                        // Если значение и так пустое, не вызываем лишних рендеров
                        if (channelSel.value !== '') {
                            channelSel.value = '';
                            renderFilter('', {}, valueCont, store);
                        }
                    }
                }
            }
        }, 100); // 100мс, чтобы ядро успело обновить свои данные
    });


    // =======================================================
    // ОТРИСОВКА ИНТЕРФЕЙСА В САМОМ ОКНЕ ФИЛЬТРА
    // =======================================================
    function renderFilter(channel, stored, cont, store) {
        cont.innerHTML = '';

        if (!channel) {
            const emptyWrap = document.createElement('div');
            emptyWrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100';
            emptyWrap.innerHTML = '<input type="text" class="ui-ctl-element" disabled placeholder="Сначала выберите канал">';
            cont.appendChild(emptyWrap);
            setFilterStore(store, { channel: '', type: '', value: '' });
            return;
        }

        const channelLabels = {
            exhibition: 'Выставка', recommendation: 'Рекомендация',
            site: 'Сайт предприятия', employee: 'Сотрудник', ebase: 'Электронная база'
        };
        const baseLabel = channelLabels[channel] || channel;

        if (channel === 'site' || channel === 'ebase') {
            const input = document.createElement('input');
            input.className = 'ui-ctl-element'; input.type = 'text'; input.disabled = true;
            input.placeholder = 'Значение не требуется';

            const wrap = document.createElement('div');
            wrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100'; wrap.appendChild(input); cont.appendChild(wrap);

            setFilterStore(store, { channel, type: 'none', value: '' }, baseLabel);
            return;
        }

        if (channel === 'exhibition') {
            renderFilterSelect({
                cont, store, component: 'sotra:main.field.primary_interest', action: 'getMarketingActivities',
                type: 'marketingActivities', placeholder: 'Любое мероприятие...', stored, channel, baseLabel
            });
            return;
        }

        if (channel === 'recommendation') {
            renderFilterAutocomplete({
                cont, store, component: 'sotra:main.field.primary_interest', searchAction: 'searchCompanies',
                titleAction: 'getCompanyTitle', type: 'crmCompany', placeholder: 'Поиск компании...', stored, channel, baseLabel
            });
            return;
        }

        if (channel === 'employee') {
            renderFilterAutocomplete({
                cont, store, component: 'sotra:main.field.primary_interest', searchAction: 'searchUsers',
                titleAction: 'getUserFio', type: 'user', placeholder: 'Поиск сотрудника...', stored, channel, baseLabel
            });
            return;
        }
    }

    function renderFilterSelect(cfg) {
        const select = document.createElement('select');
        select.className = 'ui-ctl-element'; select.innerHTML = '<option value="">Загрузка...</option>';

        const wrap = document.createElement('div');
        wrap.className = 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100';
        wrap.innerHTML = '<div class="ui-ctl-after ui-ctl-icon-angle"></div>'; wrap.appendChild(select); cfg.cont.appendChild(wrap);

        BX.ajax.runComponentAction(cfg.component, cfg.action, { mode: 'class', data: {} }).then((res) => {
            let html = `<option value="">${esc(cfg.placeholder)}</option>`;
            (res?.data?.items || []).forEach(it => html += `<option value="${esc(it.id)}">${esc(it.name)}</option>`);
            select.innerHTML = html;

            let text = cfg.baseLabel;
            if (cfg.stored && cfg.stored.channel === cfg.channel && cfg.stored.type === cfg.type && String(cfg.stored.value || '') !== '') {
                select.value = String(cfg.stored.value);
                const selText = select.options[select.selectedIndex]?.text;
                if (selText) text += ': ' + selText;
            }
            setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: select.value || '' }, text);
        }).catch(() => {
            select.innerHTML = '<option value="">Ошибка загрузки</option>';
            setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: '' }, cfg.baseLabel);
        });

        select.addEventListener('change', () => {
            let text = cfg.baseLabel;
            if (select.value) text += ': ' + select.options[select.selectedIndex]?.text;
            setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: select.value || '' }, text);
        });
    }

    function renderFilterAutocomplete(cfg) {
        const input = document.createElement('input');
        input.className = 'ui-ctl-element'; input.type = 'text'; input.placeholder = cfg.placeholder; input.autocomplete = 'off';

        const inputWrap = document.createElement('div'); inputWrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100'; inputWrap.appendChild(input);

        const dd = document.createElement('div');
        dd.style.cssText = 'display: none; position: absolute; top: calc(100% + 2px); left: 0; width: 100%; background: #fff; z-index: 9999; border: 1px solid #c6cdd3; box-shadow: 0 3px 6px rgba(0,0,0,.1); max-height: 200px; overflow-y: auto; border-radius: 2px;';

        const box = document.createElement('div'); box.style.cssText = 'position: relative; width: 100%;';
        box.appendChild(inputWrap); box.appendChild(dd); cfg.cont.appendChild(box);

        let selectedId = ''; let lastItems = []; let suppressInputHandler = false;
        function closeDd() { dd.style.display = 'none'; dd.innerHTML = ''; }
        function openDd(items) {
            lastItems = items || [];
            if (!lastItems.length) { closeDd(); return; }
            dd.innerHTML = '';
            lastItems.forEach(it => {
                const item = document.createElement('div'); item.textContent = it.name;
                item.style.cssText = 'padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f5f5f5; font-size: 13px; color: #535c69; background: #fff;';
                item.onmouseenter = () => item.style.background = '#f6f8f9'; item.onmouseleave = () => item.style.background = '#fff';

                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    selectedId = String(it.id || ''); suppressInputHandler = true; input.value = String(it.name || ''); suppressInputHandler = false;
                    setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: selectedId }, cfg.baseLabel + ': ' + it.name);
                    closeDd();
                });
                dd.appendChild(item);
            });
            dd.style.display = 'block';
        }

        if (cfg.stored && cfg.stored.channel === cfg.channel && cfg.stored.type === cfg.type && String(cfg.stored.value || '') !== '') {
            selectedId = String(cfg.stored.value);
            setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: selectedId }, cfg.baseLabel);
            suppressInputHandler = true; input.value = 'Загрузка...'; suppressInputHandler = false;

            BX.ajax.runComponentAction(cfg.component, cfg.titleAction, { mode: 'class', data: { id: selectedId } }).then((res) => {
                const title = (res?.data?.title || '').trim();
                if (title) {
                    suppressInputHandler = true; input.value = title; suppressInputHandler = false;
                    setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: selectedId }, cfg.baseLabel + ': ' + title);
                } else {
                    suppressInputHandler = true; input.value = ''; suppressInputHandler = false;
                }
            });
        } else {
            setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: '' }, cfg.baseLabel);
        }

        let t = null;
        input.addEventListener('input', () => {
            if (suppressInputHandler) return;
            selectedId = ''; setFilterStore(cfg.store, { channel: cfg.channel, type: cfg.type, value: '' }, cfg.baseLabel);
            const q = input.value.trim(); clearTimeout(t);
            if (q.length < 2) { closeDd(); return; }
            t = setTimeout(() => {
                BX.ajax.runComponentAction(cfg.component, cfg.searchAction, { mode: 'class', data: { q } })
                    .then((res) => openDd(res?.data?.items || [])).catch(() => closeDd());
            }, 250);
        });

        input.addEventListener('focus', () => { if (lastItems && lastItems.length) openDd(lastItems); });
        input.addEventListener('blur', () => setTimeout(closeDd, 150));
        input.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDd(); });
    }

    // =======================================================
    // ИНИЦИАЛИЗАЦИЯ
    // =======================================================
    function injectCustomFilterUI() {
        const fieldWrap = document.querySelector('.main-ui-filter-wield-with-label[data-name="UF_PRIMARY_INTEREST"]');
        if (!fieldWrap) return;

        const originalInput = fieldWrap.querySelector('input[type="text"][name="UF_PRIMARY_INTEREST"]');
        if (!originalInput || originalInput.dataset.piInited === 'Y') return;

        originalInput.dataset.piInited = 'Y';
        originalInput.style.display = 'none';

        const controlWrap = originalInput.closest('.main-ui-control');
        if (controlWrap) {
            controlWrap.style.overflow = 'visible';
            controlWrap.style.height = 'auto';
        }

        let storedChannel = '', storedType = '', storedValue = '';
        const val = originalInput.value || '';
        let m;
        if (m = val.match(/"channel":"([^"]+)"/)) storedChannel = m[1];
        if (m = val.match(/"type":"([^"]+)"/)) storedType = m[1];
        if (m = val.match(/"value":"([^"]+)"/)) storedValue = m[1];

        const container = document.createElement('div');
        container.style.cssText = 'display: flex; gap: 10px; width: 100%; align-items: flex-start;';

        container.innerHTML = `
            <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown" style="flex: 1;">
                <div class="ui-ctl-after ui-ctl-icon-angle"></div>
                <select class="ui-ctl-element" data-role="filter-channel">
                    <option value="">Любой канал...</option>
                    <option value="exhibition" ${storedChannel === 'exhibition' ? 'selected' : ''}>Выставка</option>
                    <option value="recommendation" ${storedChannel === 'recommendation' ? 'selected' : ''}>Рекомендация</option>
                    <option value="site" ${storedChannel === 'site' ? 'selected' : ''}>Сайт предприятия</option>
                    <option value="employee" ${storedChannel === 'employee' ? 'selected' : ''}>Сотрудник</option>
                    <option value="ebase" ${storedChannel === 'ebase' ? 'selected' : ''}>Электронная база</option>
                </select>
            </div>
            <div data-role="filter-valueContainer" style="flex: 1; min-width: 0;"></div>
        `;

        originalInput.parentNode.appendChild(container);

        const channelSel = container.querySelector('[data-role="filter-channel"]');
        const valueCont = container.querySelector('[data-role="filter-valueContainer"]');
        const stored = { channel: storedChannel, type: storedType, value: storedValue };

        renderFilter(channelSel.value || storedChannel, stored, valueCont, originalInput);

        channelSel.addEventListener('change', () => {
            renderFilter(channelSel.value, {}, valueCont, originalInput);
        });
    }

    injectCustomFilterUI();
    BX.addCustomEvent('BX.Main.Filter:show', () => setTimeout(injectCustomFilterUI, 50));

    const filterWrapper = document.querySelector('.main-ui-filter-wrapper');
    if (filterWrapper) {
        const modalObserver = new MutationObserver(() => injectCustomFilterUI());
        modalObserver.observe(filterWrapper, { childList: true, subtree: true });
    }

});