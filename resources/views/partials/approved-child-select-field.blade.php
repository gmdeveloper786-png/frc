@php
    $selectId = $selectId ?? 'childSelect';
    $searchId = $searchId ?? 'childSearch';
    $fieldName = $fieldName ?? 'child_id';
    $selectedId = (int) old($fieldName, request($fieldName, 0));
@endphp
<label>Child <span style="color:var(--danger)">*</span></label>
<input type="search" id="{{ $searchId }}" class="form-control mb-2" placeholder="Search by child name (min 2 letters)…" autocomplete="off" aria-controls="{{ $selectId }}">
<select name="{{ $fieldName }}" id="{{ $selectId }}" class="form-control @error($fieldName) is-invalid @enderror" data-child-select-sync>
    <option value="">Select Child</option>
    @foreach($initialChildren as $child)
        <option value="{{ $child->id }}" @selected($selectedId === $child->id)>{{ $child->full_name }}</option>
    @endforeach
</select>
<p class="text-muted small mb-0 mt-1">Type at least 2 characters to search approved children.</p>
@error($fieldName) <div class="invalid-feedback">{{ $message }}</div> @enderror
