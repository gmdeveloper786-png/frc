@php
    $pickerId = $pickerId ?? 'approvedChildPicker';
    $selectedIds = array_map('intval', (array) ($selectedIds ?? old('child_ids', [])));
@endphp
@if(($showLabel ?? true) && ($label ?? null) !== false)
    <label class="d-block mb-2">{{ $label ?? 'Assign Children (Optional)' }}</label>
@endif
<div id="{{ $pickerId }}" class="approved-child-picker" data-input-name="child_ids[]">
    <div class="approved-child-picker-selected mb-2" id="{{ $pickerId }}Selected" style="display:flex;flex-wrap:wrap;gap:6px;min-height:0;"></div>
    <input type="search" class="form-control mb-2 approved-child-picker-search" id="{{ $pickerId }}Search" placeholder="Search by child name or GR No." autocomplete="off">
    <div class="approved-child-picker-results" id="{{ $pickerId }}Results" style="max-height:240px;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">
        <p class="text-muted small mb-0" style="grid-column:1/-1;">Search to find children, or see already selected above.</p>
    </div>
    <div id="{{ $pickerId }}Hidden" class="approved-child-picker-hidden"></div>
</div>
