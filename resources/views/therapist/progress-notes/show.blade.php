@extends('layouts.app')
@section('title', 'Progress note')
@section('page-title', 'Progress note')

@section('content')
@php
    $pn = $progressNote;
    $statusBadge = $pn->status === 'completed' ? 'badge-approved' : 'badge-draft';
    $childName = $pn->child?->full_name ?? '—';
    $textBlocks = [
        ['label' => 'Therapy goal', 'value' => $pn->therapy_goal],
        ['label' => 'Notes', 'value' => $pn->notes],
        ['label' => 'Child response', 'value' => $pn->child_response],
        ['label' => 'Parent instructions', 'value' => $pn->parent_instructions],
        ['label' => 'Next plan', 'value' => $pn->next_plan],
    ];
@endphp

<div class="row g-3 therapist-progress-note-show-page child-show-page">
    <div class="col-12 col-xl-10 col-xxl-9 mx-auto">
        @if(session('success'))
            <div class="alert border-0 mb-3" role="alert" style="border-radius:12px;background:rgba(13,148,136,0.12);color:var(--navy);">
                <i class="fa-solid fa-circle-check me-2" style="color:var(--teal);"></i>{{ session('success') }}
            </div>
        @endif

        <div class="card-frc child-show-profile-card">
            <div class="child-show-profile-header therapist-pn-show-header">
                <div class="therapist-pn-show-hero">
                    <div class="child-show-avatar therapist-pn-show-avatar" aria-hidden="true">
                        {{ strtoupper(substr($childName, 0, 1)) }}
                    </div>
                    <div class="therapist-pn-show-hero-text">
                        <h5 class="child-show-name mb-1">{{ $childName }}</h5>
                        <p class="therapist-pn-show-subtitle mb-2">
                            Session date · {{ $pn->session_date?->format('l, d M Y') ?? '—' }}
                        </p>
                        @if($pn->service?->name)
                            <span class="child-show-tag">{{ $pn->service->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="therapist-pn-show-header-actions">
                    <span class="badge-status {{ $statusBadge }}" style="font-size:11px;">{{ ucfirst($pn->status) }}</span>
                    <a href="{{ route('therapist.progress-notes.edit', $pn) }}" class="btn-teal text-decoration-none therapist-pn-edit-btn">
                        <i class="fa-solid fa-pen-to-square"></i> Edit note
                    </a>
                </div>
            </div>

            <div class="therapist-pn-meta-row">
                <div class="therapist-pn-meta-chip">
                    <span class="therapist-pn-meta-chip__label">Progress level</span>
                    <span class="therapist-pn-meta-chip__value">{{ \App\Models\ProgressNote::labelForProgressLevel($pn->progress_level) }}</span>
                </div>
                <div class="therapist-pn-meta-chip">
                    <span class="therapist-pn-meta-chip__label">Recorded by</span>
                    <span class="therapist-pn-meta-chip__value">{{ $pn->createdBy?->full_name ?? '—' }}</span>
                </div>
                @if($pn->updatedBy)
                    <div class="therapist-pn-meta-chip">
                        <span class="therapist-pn-meta-chip__label">Last updated</span>
                        <span class="therapist-pn-meta-chip__value">
                            {{ $pn->updatedBy->full_name }}{{ $pn->updated_at ? ' · '.$pn->updated_at->format('d M Y, H:i') : '' }}
                        </span>
                    </div>
                @endif
            </div>

            <hr class="child-show-divider">

            <div class="frc-profile-detail-grid therapist-pn-detail-grid">
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Child</div>
                    <div class="frc-profile-detail-value">{{ $childName }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Session date</div>
                    <div class="frc-profile-detail-value">{{ $pn->session_date?->format('d M Y') ?? '—' }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Service</div>
                    <div class="frc-profile-detail-value">{{ $pn->service?->name ?? '—' }}</div>
                </div>
                <div class="frc-profile-detail-item">
                    <div class="frc-profile-detail-label">Status</div>
                    <div class="frc-profile-detail-value">{{ ucfirst($pn->status) }}</div>
                </div>
            </div>

            <div class="therapist-pn-text-blocks">
                @foreach($textBlocks as $block)
                    <div class="therapist-pn-text-block">
                        <div class="therapist-pn-text-block__label">{{ $block['label'] }}</div>
                        <div class="therapist-pn-text-block__value">{{ $block['value'] ?: '—' }}</div>
                    </div>
                @endforeach
            </div>

            <div class="therapist-pn-footer-actions">
                <a href="{{ route('therapist.progress-notes.index') }}" class="btn-outline-teal text-decoration-none">
                    <i class="fa-solid fa-arrow-left"></i> Back to list
                </a>
                @if($pn->child_id)
                    <a href="{{ route('therapist.children.show', $pn->child_id) }}" class="btn-outline-teal text-decoration-none">
                        <i class="fa-solid fa-child"></i> View child profile
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
