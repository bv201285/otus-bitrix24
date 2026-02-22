(function () {
    function initAllFromQueue() {
        window.DDPT_InitQueue = window.DDPT_InitQueue || [];
        const queue = window.DDPT_InitQueue.slice();
        window.DDPT_InitQueue = [];

        queue.forEach(cfg => {
            const ok = initOne(cfg);
            if (!ok) window.DDPT_InitQueue.push(cfg);
        });
    }

    function initOne(cfg) {
        if (!cfg || !cfg.rootId) return true;

        const root = document.getElementById(cfg.rootId);
        if (!root) return false;

        if (root.dataset.ddptInited === 'Y') return true;
        root.dataset.ddptInited = 'Y';

        const tbody = root.querySelector('[data-role="tbody"]');
        const addBtn = root.querySelector('[data-role="addRow"]');
        if (!tbody || !addBtn) return true;

        // store может быть несколько (CRM иногда клонирует) — берём все
        const getStores = () => root.querySelectorAll('input[data-role="store"]');

        const makeId = () => String(Date.now()) + '_' + Math.random().toString(16).slice(2);

        // --- state ---
        const state = {
            rows: [],
            doctors: [],
            procedures: [],
        };

        // нормализуем входящие строки
        const inputRows = Array.isArray(cfg.rows) ? cfg.rows : [];
        state.rows = (inputRows.length ? inputRows : [{}]).map(r => ({
            __id: (r && r.__id) ? String(r.__id) : makeId(),
            doctorId: (r && r.doctorId != null) ? String(r.doctorId) : '',
            procedureId: (r && r.procedureId != null) ? String(r.procedureId) : '',
            date: (r && r.date != null) ? String(r.date) : '',
            text: (r && r.text != null) ? String(r.text) : '',
        }));

        function serializeForStore() {
            // В БД сохраняем только нужные поля, без __id
            return state.rows.map(r => ({
                doctorId: r.doctorId || '',
                procedureId: r.procedureId || '',
                date: r.date || '',
                text: r.text || ''
            }));
        }

        function syncStore() {
            const json = JSON.stringify(serializeForStore());

            const stores = root.querySelectorAll('input[data-role="store"]');
            stores.forEach((inp) => {
                inp.value = json;

                // 1) обычные события, чтобы CRM форма поняла, что поле изменилось
                try {
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (e) {}

                // 2) BX событие (на некоторых формах это важно)
                if (window.BX && BX.fireEvent) {
                    BX.fireEvent(inp, 'change');
                    BX.fireEvent(inp, 'input');
                }
            });
        }

        function findRowIndexById(id) {
            return state.rows.findIndex(r => r.__id === id);
        }

        function renderAll() {
            tbody.innerHTML = '';
            state.rows.forEach(r => tbody.appendChild(renderRow(r)));
            syncStore();
        }

        function renderRow(row) {
            const tr = document.createElement('tr');
            tr.className = 'ddpt__tr';
            tr.dataset.rowId = row.__id;

            // doctor
            const tdDoc = document.createElement('td');
            tdDoc.appendChild(makeSelect(state.doctors, row.doctorId, 'Выберите врача', (val) => {
                const idx = findRowIndexById(row.__id);
                if (idx === -1) return;
                state.rows[idx].doctorId = val;
                syncStore();
            }));
            tr.appendChild(tdDoc);

            // procedure (если нужна — раскомментируй в шаблоне и здесь)
            /*
            const tdProc = document.createElement('td');
            tdProc.appendChild(makeSelect(state.procedures, row.procedureId, 'Выберите процедуру', (val) => {
              const idx = findRowIndexById(row.__id);
              if (idx === -1) return;
              state.rows[idx].procedureId = val;
              syncStore();
            }));
            tr.appendChild(tdProc);
            */

            // date
            const tdDate = document.createElement('td');
            const dateWrap = document.createElement('div');
            dateWrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100';
            const dateInp = document.createElement('input');
            dateInp.className = 'ui-ctl-element';
            dateInp.type = 'date';
            dateInp.value = row.date || '';
            dateInp.addEventListener('change', () => {
                const idx = findRowIndexById(row.__id);
                if (idx === -1) return;
                state.rows[idx].date = dateInp.value || '';
                syncStore();
            });
            dateWrap.appendChild(dateInp);
            tdDate.appendChild(dateWrap);
            tr.appendChild(tdDate);

            // text
            const tdText = document.createElement('td');
            const textWrap = document.createElement('div');
            textWrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100';
            const textInp = document.createElement('input');
            textInp.className = 'ui-ctl-element';
            textInp.type = 'text';
            textInp.placeholder = 'Комментарий';
            textInp.value = row.text || '';
            textInp.addEventListener('input', () => {
                const idx = findRowIndexById(row.__id);
                if (idx === -1) return;
                state.rows[idx].text = textInp.value || '';
                syncStore();
            });
            textWrap.appendChild(textInp);
            tdText.appendChild(textWrap);
            tr.appendChild(tdText);

            // delete
            const tdDel = document.createElement('td');
            tdDel.className = 'ddpt__col-del';
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'ddpt__del';
            delBtn.title = 'Удалить строку';
            delBtn.innerHTML = ''; // крестик CSS
            delBtn.addEventListener('click', () => {
                const id = row.__id;
                state.rows = state.rows.filter(r => r.__id !== id);
                if (!state.rows.length) {
                    state.rows.push({ __id: makeId(), doctorId:'', procedureId:'', date:'', text:'' });
                }
                renderAll(); // важно: именно renderAll(), чтобы пересобрать и store
            });
            tdDel.appendChild(delBtn);
            tr.appendChild(tdDel);

            return tr;
        }

        function makeSelect(items, current, placeholder, onChange) {
            const wrap = document.createElement('div');
            wrap.className = 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100';
            wrap.innerHTML = '<div class="ui-ctl-after ui-ctl-icon-angle"></div>';

            const sel = document.createElement('select');
            sel.className = 'ui-ctl-element';

            let html = `<option value="">${esc(placeholder)}</option>`;
            items.forEach(it => html += `<option value="${esc(it.id)}">${esc(it.name)}</option>`);
            sel.innerHTML = html;

            if (String(current || '') !== '') sel.value = String(current);

            sel.addEventListener('change', () => onChange(sel.value || ''));
            wrap.appendChild(sel);
            return wrap;
        }

        // add row
        addBtn.addEventListener('click', () => {
            state.rows.push({ __id: makeId(), doctorId:'', procedureId:'', date:'', text:'' });
            renderAll();
        });

        // load dictionaries
        Promise.all([loadIblock('Doctors'), loadIblock('Procedure')])
            .then(([doctors, procedures]) => {
                state.doctors = Array.isArray(doctors) ? doctors : [];
                state.procedures = Array.isArray(procedures) ? procedures : [];
                renderAll();
            })
            .catch(() => {
                state.doctors = [];
                state.procedures = [];
                renderAll();
            });

        return true;
    }

    function loadIblock(iblockApi) {
        return BX.ajax.runComponentAction('otus:main.field.deal_doctors_procedures_table', 'getElements', {
            mode: 'class',
            data: { iblockApi }
        }).then(res => (res && res.data && Array.isArray(res.data.items)) ? res.data.items : []);
    }

    function esc(s) {
        return String(s)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    window.DDPT_InitAll = initAllFromQueue;
    initAllFromQueue();
    if (window.BX && BX.ready) BX.ready(initAllFromQueue);
})();