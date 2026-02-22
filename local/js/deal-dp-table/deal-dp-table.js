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
        const store = root.querySelector('[data-role="store"]');
        const addBtn = root.querySelector('[data-role="addRow"]');
        if (!tbody || !store || !addBtn) return true;

        const state = {
            rows: Array.isArray(cfg.rows) ? cfg.rows : [],
            doctors: null,
            procedures: null,
        };

        // Грузим справочники параллельно
        Promise.all([
            loadIblock('Doctors'),
            loadIblock('Procedure'),
        ]).then(([doctors, procedures]) => {
            state.doctors = doctors;
            state.procedures = procedures;

            renderAll();
        }).catch(err => {
            console.error('DDPT load error', err);
            state.doctors = [];
            state.procedures = [];
            renderAll();
        });

        addBtn.addEventListener('click', () => {
            state.rows.push({ doctorId: '', procedureId: '', date: '', text: '' });
            renderAll();
        });

        function renderAll() {
            tbody.innerHTML = '';
            const doctors = state.doctors || [];
            const procedures = state.procedures || [];

            state.rows.forEach((row, idx) => {
                tbody.appendChild(renderRow(row, idx, doctors, procedures));
            });

            syncStore();
        }

        function syncStore() {
            store.value = JSON.stringify(state.rows);
        }

        function renderRow(row, idx, doctors, procedures) {
            const tr = document.createElement('tr');
            tr.className = 'ddpt__tr';

            // doctor select
            const tdDoc = document.createElement('td');
            tdDoc.appendChild(makeSelect(doctors, row.doctorId, 'Выберите врача', (val) => {
                state.rows[idx].doctorId = val;
                syncStore();
            }));
            tr.appendChild(tdDoc);

            // procedure select
            /*const tdProc = document.createElement('td');
            tdProc.appendChild(makeSelect(procedures, row.procedureId, 'Выберите процедуру', (val) => {
                state.rows[idx].procedureId = val;
                syncStore();
            }));
            tr.appendChild(tdProc);*/

            // date
            const tdDate = document.createElement('td');
            const dateWrap = document.createElement('div');
            dateWrap.className = 'ui-ctl ui-ctl-textbox ui-ctl-w100';
            const dateInp = document.createElement('input');
            dateInp.className = 'ui-ctl-element';
            dateInp.type = 'date';
            dateInp.value = row.date || '';
            dateInp.addEventListener('change', () => {
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
                state.rows[idx].text = textInp.value || '';
                syncStore();
            });
            textWrap.appendChild(textInp);
            tdText.appendChild(textWrap);
            tr.appendChild(tdText);

            // remove (small cross)
            const tdDel = document.createElement('td');
            tdDel.className = 'ddpt__col-del';

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'ddpt__del';
            delBtn.title = 'Удалить строку';
            /*delBtn.innerHTML = '<span class="ui-icon ui-icon-service-close"><i></i></span>';*/
            delBtn.innerHTML = '';

            delBtn.addEventListener('click', () => {
                state.rows.splice(idx, 1);
                if (state.rows.length === 0) {
                    state.rows.push({ doctorId: '', procedureId: '', date: '', text: '' });
                }

                tbody.innerHTML = '';
                state.rows.forEach((r, i) => tbody.appendChild(renderRow(r, i, doctors, procedures)));
                syncStore();
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
            items.forEach(it => {
                html += `<option value="${esc(it.id)}">${esc(it.name)}</option>`;
            });
            sel.innerHTML = html;

            if (String(current || '') !== '') sel.value = String(current);

            sel.addEventListener('change', () => onChange(sel.value || ''));
            wrap.appendChild(sel);
            return wrap;
        }

        return true;
    }

    function loadIblock(iblockApi) {
        return BX.ajax.runComponentAction('otus:dp.table.data', 'getElements', {
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