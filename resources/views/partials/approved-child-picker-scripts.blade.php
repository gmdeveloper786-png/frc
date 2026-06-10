@php
    $pickerMode = $pickerMode ?? 'select';
    $initialChildrenJson = ($initialChildren ?? collect())->map(static function ($child): array {
        return [
            'id'           => $child->id,
            'full_name'    => $child->full_name,
            'age'          => $child->age,
            'gender'       => $child->gender,
            'phone_number' => $child->phone_number,
        ];
    })->values();
    $approvedChildSearchUrl = route('ajax.children.approved-search');
@endphp
<script nonce="{{ $cspNonce }}">
(function () {
    const SEARCH_URL = @json($approvedChildSearchUrl);
    const INITIAL = @json($initialChildrenJson);
    const MODE = @json($pickerMode);

    function childMetaLine(c) {
        const parts = [];
        if (c.age) parts.push(c.age + 'y');
        if (c.gender) parts.push(ucFirst(c.gender));
        return parts.join(' • ');
    }

    function ucFirst(s) {
        return s ? String(s).charAt(0).toUpperCase() + String(s).slice(1) : '';
    }

    function fetchHeaders() {
        const token = document.querySelector('meta[name=csrf-token]');
        return {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token ? token.content : '',
        };
    }

    async function searchChildren(q) {
        const res = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
            credentials: 'same-origin',
            headers: fetchHeaders(),
        });
        if (!res.ok) return [];
        const data = await res.json().catch(() => ({}));
        return data.data || [];
    }

    if (MODE === 'select') {
        const selectId = @json($selectId ?? 'childSelect');
        const searchId = @json($searchId ?? 'childSearch');
        const select = document.getElementById(selectId);
        const search = document.getElementById(searchId);
        if (!select || !search) return;

        let debounce = null;

        function mergeWithSelected(list) {
            const currentId = select.value;
            const map = new Map();
            INITIAL.forEach(c => map.set(String(c.id), c));
            list.forEach(c => map.set(String(c.id), c));
            if (currentId && !map.has(String(currentId))) {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.value) {
                    map.set(String(currentId), { id: Number(currentId), full_name: opt.textContent.trim() });
                }
            }
            return Array.from(map.values()).sort((a, b) => a.full_name.localeCompare(b.full_name));
        }

        function fillSelect(children, keepValue) {
            const prev = keepValue ? select.value : '';
            select.innerHTML = '<option value="">Select Child</option>';
            children.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.full_name;
                select.appendChild(opt);
            });
            if (prev && Array.from(select.options).some(o => o.value === String(prev))) {
                select.value = String(prev);
            }
        }

        fillSelect(INITIAL, true);

        search.addEventListener('input', function () {
            clearTimeout(debounce);
            const q = search.value.trim();
            debounce = setTimeout(async function () {
                if (q.length < 2) {
                    fillSelect(INITIAL, true);
                    return;
                }
                try {
                    const list = await searchChildren(q);
                    fillSelect(mergeWithSelected(list), true);
                } catch (e) {
                    console.error(e);
                }
            }, 300);
        });
        return;
    }

    if (MODE === 'checkboxes') {
        const pickerId = @json($pickerId ?? 'approvedChildPicker');
        const root = document.getElementById(pickerId);
        if (!root) return;

        const selectedWrap = document.getElementById(pickerId + 'Selected');
        const searchInput = document.getElementById(pickerId + 'Search');
        const resultsWrap = document.getElementById(pickerId + 'Results');
        const hiddenWrap = document.getElementById(pickerId + 'Hidden');
        const inputName = root.getAttribute('data-input-name') || 'child_ids[]';
        const selected = new Map();
        INITIAL.forEach(c => selected.set(c.id, c));

        let debounce = null;

        function syncHiddenInputs() {
            if (!hiddenWrap) return;
            hiddenWrap.innerHTML = '';
            selected.forEach((c, id) => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = inputName;
                inp.value = id;
                hiddenWrap.appendChild(inp);
            });
        }

        function renderChip(c) {
            const chip = document.createElement('span');
            chip.className = 'badge';
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:6px 10px;background:var(--teal-light);color:var(--navy);border-radius:8px;font-size:12px;font-weight:500;';
            chip.innerHTML = '<span>' + escapeHtml(c.full_name) + '</span>';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Remove');
            btn.style.cssText = 'border:none;background:transparent;cursor:pointer;padding:0;line-height:1;color:var(--text-muted);';
            btn.innerHTML = '&times;';
            btn.addEventListener('click', function () {
                selected.delete(c.id);
                renderSelected();
                syncHiddenInputs();
                renderResults(lastResults);
            });
            chip.appendChild(btn);
            return chip;
        }

        function escapeHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderSelected() {
            if (!selectedWrap) return;
            selectedWrap.innerHTML = '';
            if (!selected.size) {
                selectedWrap.style.display = 'none';
                return;
            }
            selectedWrap.style.display = 'flex';
            Array.from(selected.values()).sort((a, b) => a.full_name.localeCompare(b.full_name)).forEach(c => {
                selectedWrap.appendChild(renderChip(c));
            });
        }

        let lastResults = [];

        function childCheckboxLabel(c, checked) {
            const label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 14px;border:1.5px solid ' + (checked ? 'var(--teal)' : 'var(--border-soft)') + ';background:' + (checked ? 'var(--teal-light)' : '') + ';border-radius:10px;cursor:pointer;';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = c.id;
            cb.checked = checked;
            cb.style.accentColor = 'var(--teal)';
            cb.addEventListener('change', function () {
                if (cb.checked) selected.set(c.id, c);
                else selected.delete(c.id);
                label.style.borderColor = cb.checked ? 'var(--teal)' : 'var(--border-soft)';
                label.style.background = cb.checked ? 'var(--teal-light)' : '';
                renderSelected();
                syncHiddenInputs();
            });
            const text = document.createElement('div');
            text.innerHTML = '<div style="font-size:13px;font-weight:500;">' + escapeHtml(c.full_name) + '</div>'
                + '<div style="font-size:11px;color:var(--text-muted);">' + escapeHtml(childMetaLine(c)) + '</div>';
            label.appendChild(cb);
            label.appendChild(text);
            return label;
        }

        function renderResults(list) {
            lastResults = list;
            if (!resultsWrap) return;
            resultsWrap.innerHTML = '';
            if (!list.length) {
                const p = document.createElement('p');
                p.className = 'text-muted small mb-0';
                p.style.gridColumn = '1 / -1';
                p.textContent = searchInput && searchInput.value.trim().length >= 2
                    ? 'No children found.'
                    : 'Type at least 2 characters to search.';
                resultsWrap.appendChild(p);
                return;
            }
            list.forEach(c => {
                resultsWrap.appendChild(childCheckboxLabel(c, selected.has(c.id)));
            });
        }

        renderSelected();
        syncHiddenInputs();
        renderResults([]);

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounce);
                const q = searchInput.value.trim();
                debounce = setTimeout(async function () {
                    if (q.length < 2) {
                        renderResults([]);
                        return;
                    }
                    try {
                        const list = await searchChildren(q);
                        renderResults(list);
                    } catch (e) {
                        console.error(e);
                    }
                }, 300);
            });
        }

        window.highlightChildCheck = function () {};
    }

    document.querySelectorAll('[data-child-select-sync]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (typeof onChildSelectChange === 'function') {
                onChildSelectChange();
            }
        });
    });
})();
</script>
