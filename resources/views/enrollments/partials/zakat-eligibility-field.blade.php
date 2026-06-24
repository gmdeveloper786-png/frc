@php
    $selected = old('zakat_eligibility', $selected ?? null);
    $radioName = isset($namePrefix) && $namePrefix
        ? $namePrefix . '[zakat_eligibility]'
        : 'zakat_eligibility';
    $errorKey = isset($namePrefix) && $namePrefix
        ? str_replace(['[', ']'], ['.', ''], $namePrefix) . '.zakat_eligibility'
        : 'zakat_eligibility';
@endphp
<div class="mb-3">
    <label class="d-block mb-2">Zakat eligibility <span style="color:var(--danger)">*</span></label>
    <div class="d-flex flex-column gap-2">
        @foreach(\App\Models\Enrollment::zakatEligibilityOptions() as $value => $label)
            <label class="form-check" style="padding:10px 12px;background:var(--bg-light);border-radius:10px;border:1px solid var(--border-soft,#e5eaf0);cursor:pointer;">
                <input
                    type="radio"
                    name="{{ $radioName }}"
                    value="{{ $value }}"
                    class="form-check-input"
                    @checked($selected === $value)
                    required
                >
                <span class="form-check-label" style="font-weight:500;color:var(--navy);">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error($errorKey)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
