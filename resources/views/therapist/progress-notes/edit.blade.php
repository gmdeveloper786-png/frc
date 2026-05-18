@extends('layouts.app')
@section('title', 'Edit progress note')
@section('page-title', 'Edit progress note')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-frc mb-3">
            <div class="card-header-frc flex-wrap gap-2 align-items-start">
                <div class="flex-grow-1">
                    <h6 class="card-title-frc mb-0">{{ $progressNote->child?->full_name }} · {{ $progressNote->session_date?->format('d M Y') }}</h6>
                    <div class="small text-muted">You may only edit your own notes.</div>
                </div>
            </div>
            <form action="{{ route('therapist.progress-notes.update', $progressNote) }}" method="post" class="form-frc p-3">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Therapy goal</label>
                    <textarea name="therapy_goal" class="form-control" rows="2">{{ old('therapy_goal', $progressNote->therapy_goal) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes <span class="text-danger">*</span></label>
                    <textarea name="notes" class="form-control" rows="4" required maxlength="8000">{{ old('notes', $progressNote->notes) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Child response</label>
                    <textarea name="child_response" class="form-control" rows="2">{{ old('child_response', $progressNote->child_response) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Progress level <span class="text-danger">*</span></label>
                    <select name="progress_level" class="form-select" required>
                        @foreach(\App\Models\ProgressNote::PROGRESS_LEVELS as $lvl)
                            <option value="{{ $lvl }}" @selected(old('progress_level', $progressNote->progress_level) === $lvl)>{{ \App\Models\ProgressNote::labelForProgressLevel($lvl) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Parent instructions</label>
                    <textarea name="parent_instructions" class="form-control" rows="2">{{ old('parent_instructions', $progressNote->parent_instructions) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Next plan</label>
                    <textarea name="next_plan" class="form-control" rows="2">{{ old('next_plan', $progressNote->next_plan) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        @foreach(\App\Models\ProgressNote::STATUSES as $st)
                            <option value="{{ $st }}" @selected(old('status', $progressNote->status) === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn-teal">Update note</button>
                    <a href="{{ route('therapist.progress-notes.index') }}" class="btn-outline-teal" style="padding:10px 18px;text-decoration:none;">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
