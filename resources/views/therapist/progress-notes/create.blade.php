@extends('layouts.app')
@section('title', 'Add progress note')
@section('page-title', 'Add progress note')

@section('content')
@php
    $hasPrefilledSchedule = ! empty($prefillEnrollmentScheduleId ?? null);
    $needsOccurrencePick = ! $hasPrefilledSchedule;
    $pickDisabled = $needsOccurrencePick && ($occurrencePickOptions ?? collect())->isEmpty();
    $lockService = $hasPrefilledSchedule && ! empty($prefillService);
    $selectedServiceId = (int) old('service_id', $prefillServiceId ?? 0);
@endphp
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-frc">
            <div class="card-header-frc"><h6 class="card-title-frc mb-0">Clinical progress note</h6></div>
            <form action="{{ route('therapist.progress-notes.store') }}" method="post" class="form-frc p-3">
                @csrf
                @if(! empty($prefillEnrollmentId ?? null))
                    <input type="hidden" name="enrollment_id" value="{{ $prefillEnrollmentId }}">
                @endif
                @if($hasPrefilledSchedule)
                    <input type="hidden" name="enrollment_schedule_id" value="{{ $prefillEnrollmentScheduleId }}">
                @endif

                @if($needsOccurrencePick)
                    <div class="mb-3">
                        <label class="form-label">Completed session <span class="text-danger">*</span></label>
                        <select name="occurrence_pick" class="form-select @error('enrollment_schedule_id') is-invalid @enderror @error('occurrence_pick') is-invalid @enderror" {{ $pickDisabled ? 'disabled' : 'required' }}>
                            <option value="">Select completed session…</option>
                            @foreach($occurrencePickOptions ?? [] as $opt)
                                <option value="{{ $opt['value'] }}"
                                    data-child-id="{{ $opt['child_id'] ?? '' }}"
                                    data-service-id="{{ $opt['service_id'] ?? '' }}"
                                    @selected(old('occurrence_pick') === $opt['value'])>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                        @if($pickDisabled)
                            <div class="form-text text-muted">No sessions need documentation right now. Completed sessions appear here when documentation is missing or saved as draft.</div>
                        @endif
                        @error('enrollment_schedule_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Child <span class="text-danger">*</span></label>
                    <select name="child_id" class="form-select @error('child_id') is-invalid @enderror" required @disabled($hasPrefilledSchedule)>
                        <option value="">Select child</option>
                        @foreach($children as $c)
                            <option value="{{ $c->id }}" @selected((int) old('child_id', $selectedChildId) === (int) $c->id)>{{ $c->full_name }}</option>
                        @endforeach
                    </select>
                    @if($hasPrefilledSchedule)
                        <input type="hidden" name="child_id" value="{{ old('child_id', $selectedChildId) }}">
                    @endif
                    @error('child_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Session date <span class="text-danger">*</span></label>
                        <input type="date" name="session_date" class="form-control @error('session_date') is-invalid @enderror" value="{{ old('session_date', $prefillSessionDate ?? now()->toDateString()) }}" @disabled($needsOccurrencePick) @if($hasPrefilledSchedule) required @endif>
                        @if($needsOccurrencePick && ! $pickDisabled)
                            <div class="form-text">Filled automatically from the session you select.</div>
                        @endif
                        @error('session_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service @if($lockService)<span class="text-muted">(session)</span>@else<span class="text-muted">(optional)</span>@endif</label>
                        @if($lockService)
                            <input type="text" class="form-control" value="{{ $prefillService->name }}" readonly tabindex="-1">
                            <input type="hidden" name="service_id" value="{{ $prefillService->id }}">
                        @else
                            <select name="service_id" id="progress-note-service" class="form-select">
                                <option value="">—</option>
                                @foreach($serviceOptions ?? [] as $s)
                                    <option value="{{ $s->id }}" @selected($selectedServiceId === (int) $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Therapy goal</label>
                    <textarea name="therapy_goal" class="form-control" rows="2">{{ old('therapy_goal') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes <span class="text-danger">*</span></label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4" required maxlength="8000" placeholder="Clinical documentation for this session">{{ old('notes') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Child response</label>
                    <textarea name="child_response" class="form-control" rows="2">{{ old('child_response') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Progress level <span class="text-danger">*</span></label>
                    <select name="progress_level" class="form-select" required>
                        @foreach(\App\Models\ProgressNote::PROGRESS_LEVELS as $lvl)
                            <option value="{{ $lvl }}" @selected(old('progress_level', 'good') === $lvl)>{{ \App\Models\ProgressNote::labelForProgressLevel($lvl) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Parent instructions</label>
                    <textarea name="parent_instructions" class="form-control" rows="2">{{ old('parent_instructions') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Next plan</label>
                    <textarea name="next_plan" class="form-control" rows="2">{{ old('next_plan') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach(\App\Models\ProgressNote::STATUSES as $st)
                            <option value="{{ $st }}" @selected(old('status', 'draft') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Save as <strong>Draft</strong> to finish later (session stays in Notes Pending). <strong>Completed</strong> finalizes documentation.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn-teal" @disabled($pickDisabled && $needsOccurrencePick)>Save note</button>
                    <a href="{{ route('therapist.progress-notes.index') }}" class="btn-outline-teal" style="padding:10px 18px;text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@if($needsOccurrencePick && ! $pickDisabled)
    @push('scripts')
    <script>
    (function () {
        var sel = document.querySelector('select[name="occurrence_pick"]');
        var dt = document.querySelector('input[name="session_date"]');
        var childSel = document.querySelector('select[name="child_id"]');
        var serviceSel = document.getElementById('progress-note-service');
        if (!sel || !dt) return;

        function setSelectValue(selectEl, value) {
            if (!selectEl || value === '' || value === null || value === undefined) return;
            var v = String(value);
            if (!selectEl.querySelector('option[value="' + v + '"]')) {
                var label = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : ('Service #' + v);
                var opt = document.createElement('option');
                opt.value = v;
                opt.textContent = label.split(' · ').pop() || label;
                selectEl.appendChild(opt);
            }
            selectEl.value = v;
        }

        function sync() {
            var opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) return;
            var p = opt.value.split('|');
            if (p.length >= 2 && p[1]) dt.value = p[1];
            if (opt.dataset.serviceId) setSelectValue(serviceSel, opt.dataset.serviceId);
            if (opt.dataset.childId && childSel) childSel.value = opt.dataset.childId;
        }

        sel.addEventListener('change', sync);
        sync();
    })();
    </script>
    @endpush
@endif
@endsection
