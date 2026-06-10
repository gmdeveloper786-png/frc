@extends('layouts.app')
@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('content')
@php
    $settingsService = app(\App\Services\SettingService::class);
    $values = $settingsService->all();
@endphp

<form action="{{ route('settings.update') }}" method="POST" class="form-frc">
    @csrf
    @method('PUT')

    @foreach($groups as $groupTitle => $items)
        <div class="card-frc mb-3">
            <div class="card-header-frc">
                <h6 class="card-title-frc mb-0">
                    <i class="fa-solid fa-gear me-2" style="color:var(--teal);"></i>{{ $groupTitle }}
                </h6>
            </div>
            <div class="p-3 p-md-4">
                <div class="row g-3">
                    @foreach($items as $setting)
                        @if($setting->key === \App\Support\SettingKeys::CITY_SESSION_PRICES)
                            @continue
                        @endif
                        @php
                            $key = $setting->key;
                            $type = $setting->type;
                            $val = old($key, $values[$key] ?? $setting->value);
                            $label = $settingsService->label($key);
                            $required = in_array($key, [
                                'organisation_name',
                                'organisation_short_name',
                                'receipt_logo_text',
                                'high_discount_threshold',
                            ], true);
                        @endphp
                        <div class="@if($type === 'text') col-12 @elseif($type === 'boolean') col-12 @else col-md-6 @endif">
                            @if($type === 'boolean')
                                <div class="form-check" style="padding:12px 14px;background:#f6f9fc;border-radius:10px;border:1px solid var(--border-soft,#e5eaf0);">
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="{{ $key }}"
                                        id="setting_{{ $key }}"
                                        value="1"
                                        @checked(filter_var($val, FILTER_VALIDATE_BOOLEAN))
                                    >
                                    <label class="form-check-label" for="setting_{{ $key }}" style="font-weight:500;color:var(--navy);">
                                        {{ $label }}
                                    </label>
                                </div>
                            @elseif($type === 'text')
                                <label class="form-label">{{ $label }}</label>
                                <textarea name="{{ $key }}" rows="3" class="form-control @error($key) is-invalid @enderror">{{ $val }}</textarea>
                            @else
                                <label class="form-label">
                                    {{ $label }}
                                    @if($required)<span class="text-danger">*</span>@endif
                                </label>
                                <input
                                    type="{{ $type === 'number' ? 'number' : 'text' }}"
                                    name="{{ $key }}"
                                    value="{{ $val }}"
                                    class="form-control @error($key) is-invalid @enderror"
                                    @if($key === 'high_discount_threshold') min="0" max="100" step="0.01" @endif
                                >
                            @endif
                            @error($key)
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if($key === 'high_discount_threshold')
                                <div class="form-text">Discounts above this % need approval in the high discount queue. Approval Discount staff users receive email notifications.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="card-frc mb-3">
        <div class="card-header-frc">
            <h6 class="card-title-frc mb-0">
                <i class="fa-solid fa-city me-2" style="color:var(--teal);"></i> City session pricing
            </h6>
        </div>
        <div class="p-3 p-md-4">
            <p class="text-muted small mb-3">
                Default <strong>price per session (PKR)</strong> when creating or editing an enrollment. The branch’s city is used
                (e.g. all Karachi branches share one rate). This price is applied automatically and cannot be changed on the enrollment form.
            </p>
            @if(empty($pricingCities))
                <p class="mb-0 text-muted">Add cities to your branches first, then set prices here.</p>
            @else
                <div class="row g-3">
                    @foreach($pricingCities as $city)
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label">{{ $city }} (PKR / session)</label>
                            <input
                                type="number"
                                name="city_session_prices[{{ $city }}]"
                                value="{{ old('city_session_prices.'.$city, $citySessionPrices[$city] ?? '') }}"
                                class="form-control @error('city_session_prices.'.$city) is-invalid @enderror"
                                min="0"
                                step="1"
                                placeholder="0"
                            >
                            @error('city_session_prices.'.$city)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn-teal">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save settings
        </button>
    </div>
</form>
@endsection
