@php
    $pickerId = $pickerId ?? 'approvedChildPicker';
    $selectedIds = array_map('intval', (array) ($selectedIds ?? old('child_ids', [])));
@endphp
@push('styles')
<style>
.approved-child-picker-results {
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)) !important;
}
.approved-child-picker-disabilities {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
    max-width: 100%;
}
.approved-child-picker-disability-tag,
.approved-child-picker-disability-more {
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 10px;
    line-height: 1.3;
    padding: 2px 6px;
    border-radius: 6px;
    vertical-align: top;
}
.approved-child-picker-disability-tag {
    background: rgba(17, 81, 124, 0.08);
    color: var(--navy, #11517c);
}
.approved-child-picker-disability-more {
    background: var(--bg-light, #f4f7fa);
    color: var(--text-muted, #6b7c93);
    font-weight: 600;
    flex-shrink: 0;
}
</style>
@endpush
@if(($showLabel ?? true) && ($label ?? null) !== false)
    <label class="d-block mb-2">{{ $label ?? 'Assign Children' }} <span style="color:var(--danger)">*</span></label>
@endif
<div id="{{ $pickerId }}" class="approved-child-picker" data-input-name="child_ids[]">
    <div class="approved-child-picker-selected mb-2" id="{{ $pickerId }}Selected" style="display:flex;flex-wrap:wrap;gap:6px;min-height:0;"></div>
    <input type="search" class="form-control mb-2 approved-child-picker-search" id="{{ $pickerId }}Search" placeholder="Search by child name or GR No." autocomplete="off">
    <div class="approved-child-picker-results" id="{{ $pickerId }}Results" style="max-height:280px;overflow-y:auto;display:grid;gap:8px;">
        <p class="text-muted small mb-0" style="grid-column:1/-1;">Search to find children, or see already selected above.</p>
    </div>
    <div id="{{ $pickerId }}Hidden" class="approved-child-picker-hidden"></div>
</div>
